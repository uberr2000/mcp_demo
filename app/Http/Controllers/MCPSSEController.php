<?php

namespace App\Http\Controllers;

use App\Services\MCPService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MCPSSEController extends Controller
{
    private $mcpService;

    public function __construct(MCPService $mcpService)
    {
        $this->mcpService = $mcpService;
    }    /**
     * MCP SSE 端點 - 提供 Server-Sent Events 協議支援
     */
    public function sse(Request $request)
    {
        // 設置執行時間和記憶體限制
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        
        // 忽略用戶端中斷訊號，但我們會手動檢測
        ignore_user_abort(false);

        $connectionId = uniqid('mcp_sse_', true);
        Log::info("SSE Connection establishing", [
            'connection_id' => $connectionId,
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $response = new StreamedResponse(function() use ($request, $connectionId) {
            try {
                // 設置連線處理
                $this->setupConnection($connectionId);
                
                // 初始化處理請求數據
                $this->processInitialRequest($request, $connectionId);
                
                // 開始保持連線循環
                $this->maintainConnection($connectionId);
                
            } catch (\Exception $e) {
                Log::error('SSE Stream Error', [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                try {
                    $this->sendSSEMessage([
                        'jsonrpc' => '2.0',
                        'error' => [
                            'code' => -32603,
                            'message' => 'Stream error',
                            'data' => $e->getMessage()
                        ],
                        'id' => null
                    ], 'error');
                } catch (\Exception $sendError) {
                    Log::error('Failed to send error message', [
                        'connection_id' => $connectionId,
                        'original_error' => $e->getMessage(),
                        'send_error' => $sendError->getMessage()
                    ]);
                }
            } finally {
                Log::info("SSE Connection terminated", [
                    'connection_id' => $connectionId,
                    'memory_peak' => memory_get_peak_usage(true)
                ]);
                
                // 強制清理
                $this->forceCloseConnection($connectionId);
            }
        });

        // 設置響應標頭
        $response->headers->set('Content-Type', 'text/event-stream; charset=utf-8');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, Cache-Control');
        $response->headers->set('X-Accel-Buffering', 'no'); // 禁用 Nginx 緩衝
        $response->headers->set('Transfer-Encoding', 'chunked');

        return $response;
    }

    /**
     * 設置連線初始狀態
     */
    private function setupConnection(string $connectionId): void
    {
        // 禁用輸出緩衝
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // 設置輸出緩衝為立即輸出
        ob_implicit_flush(true);
        
        Log::info("Connection setup completed", ['connection_id' => $connectionId]);
    }

    /**
     * 處理初始請求數據
     */
    private function processInitialRequest(Request $request, string $connectionId)
    {
        // 解析 JSON-RPC 請求
        $jsonRpcRequest = $request->getContent();
        
        if (empty($jsonRpcRequest)) {
            // 如果沒有初始請求數據，發送歡迎消息
            $this->sendSSEMessage([
                'jsonrpc' => '2.0',
                'result' => [
                    'status' => 'connected',
                    'connection_id' => $connectionId,
                    'server' => 'MCP Laravel Server',
                    'version' => '1.0.0'
                ],
                'id' => 'welcome'
            ], 'welcome');
            return;
        }

        $requestData = json_decode($jsonRpcRequest, true);

        if (!$requestData) {
            $this->sendSSEMessage([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error'
                ],
                'id' => null
            ], 'error');
            return;
        }

        $method = $requestData['method'] ?? '';
        $params = $requestData['params'] ?? [];
        $id = $requestData['id'] ?? null;

        try {
            $result = $this->handleMCPMethod($method, $params);
            
            $this->sendSSEMessage([
                'jsonrpc' => '2.0',
                'result' => $result,
                'id' => $id
            ], 'response');
        } catch (\Exception $e) {
            Log::error('MCP SSE 請求錯誤', [
                'connection_id' => $connectionId,
                'method' => $method,
                'error' => $e->getMessage()
            ]);

            $this->sendSSEMessage([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error',
                    'data' => $e->getMessage()
                ],
                'id' => $id
            ], 'error');
        }
    }    /**
     * 維持 SSE 連線並檢測客戶端斷開
     */
    private function maintainConnection(string $connectionId)
    {
        $startTime = time();
        $pingCount = 0;
        $maxConnectionTime = 3600; // 1小時最大連線時間
        $pingInterval = 15; // 15秒發送一次心跳
        $forceCloseOnDisconnect = true; // 強制關閉連線
        $consecutiveFailures = 0;
        $maxConsecutiveFailures = 3;

        Log::info("Starting connection maintenance loop", [
            'connection_id' => $connectionId,
            'max_connection_time' => $maxConnectionTime,
            'ping_interval' => $pingInterval
        ]);

        while (true) {
            try {
                // 多重檢查連線狀態
                if ($this->isConnectionClosed($connectionId)) {
                    Log::info("Connection detected as closed", ['connection_id' => $connectionId]);
                    break;
                }

                // 檢查是否超過最大連線時間
                if ((time() - $startTime) > $maxConnectionTime) {
                    Log::info("Connection timeout reached", [
                        'connection_id' => $connectionId,
                        'duration' => time() - $startTime
                    ]);
                    
                    try {
                        $this->sendSSEMessage([
                            'event' => 'timeout',
                            'data' => 'Connection timeout reached'
                        ], 'timeout');
                    } catch (\Exception $e) {
                        Log::warning("Failed to send timeout message", [
                            'connection_id' => $connectionId,
                            'error' => $e->getMessage()
                        ]);
                    }
                    break;
                }

                // 發送心跳包
                $pingCount++;
                $pingSuccess = $this->sendPingMessage($connectionId, $pingCount, time() - $startTime);
                
                if (!$pingSuccess) {
                    $consecutiveFailures++;
                    Log::warning("Ping failed", [
                        'connection_id' => $connectionId,
                        'consecutive_failures' => $consecutiveFailures
                    ]);
                    
                    if ($consecutiveFailures >= $maxConsecutiveFailures) {
                        Log::info("Max consecutive failures reached, closing connection", [
                            'connection_id' => $connectionId,
                            'failures' => $consecutiveFailures
                        ]);
                        break;
                    }
                } else {
                    $consecutiveFailures = 0; // 重置失敗計數
                }

                // 再次檢查連線狀態
                if ($this->isConnectionClosed($connectionId)) {
                    Log::info("Connection closed after ping", ['connection_id' => $connectionId]);
                    break;
                }

                // 等待下一次心跳
                $this->waitWithConnectionCheck($pingInterval, $connectionId);
                
            } catch (\Exception $e) {
                Log::error("Error in connection maintenance loop", [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                break;
            }
        }

        // 強制清理連線資源
        if ($forceCloseOnDisconnect) {
            $this->forceCloseConnection($connectionId);
        }
    }

    /**
     * 檢查連線是否已關閉
     */
    private function isConnectionClosed(string $connectionId): bool
    {
        // 檢查 PHP 內建的連線狀態
        if (connection_aborted()) {
            Log::debug("Connection aborted detected", ['connection_id' => $connectionId]);
            return true;
        }

        // 檢查連線狀態
        if (connection_status() !== CONNECTION_NORMAL) {
            Log::debug("Connection status abnormal", [
                'connection_id' => $connectionId,
                'status' => connection_status()
            ]);
            return true;
        }

        // 嘗試寫入小量數據來檢測連線
        try {
            echo ": heartbeat-check\n";
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
            
            // 檢查是否在寫入後連線被中斷
            if (connection_aborted()) {
                Log::debug("Connection aborted after heartbeat check", ['connection_id' => $connectionId]);
                return true;
            }
        } catch (\Exception $e) {
            Log::debug("Exception during connection check", [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);
            return true;
        }

        return false;
    }

    /**
     * 發送 ping 消息
     */
    private function sendPingMessage(string $connectionId, int $pingCount, int $uptime): bool
    {
        try {
            $this->sendSSEMessage([
                'event' => 'ping',
                'data' => [
                    'timestamp' => time(),
                    'ping_count' => $pingCount,
                    'connection_id' => $connectionId,
                    'uptime' => $uptime,
                    'server_time' => date('Y-m-d H:i:s'),
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true)
                ]
            ], 'ping');
            
            Log::debug("Ping sent successfully", [
                'connection_id' => $connectionId,
                'ping_count' => $pingCount
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to send ping", [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 等待指定時間並檢查連線
     */
    private function waitWithConnectionCheck(int $seconds, string $connectionId): void
    {
        $checkInterval = 1; // 每秒檢查一次
        $elapsed = 0;
        
        while ($elapsed < $seconds) {
            sleep($checkInterval);
            $elapsed += $checkInterval;
            
            // 在等待期間定期檢查連線
            if ($this->isConnectionClosed($connectionId)) {
                Log::debug("Connection closed during wait", [
                    'connection_id' => $connectionId,
                    'elapsed' => $elapsed
                ]);
                throw new \Exception("Connection closed during wait");
            }
        }
    }

    /**
     * 強制關閉連線並清理資源
     */
    private function forceCloseConnection(string $connectionId): void
    {
        try {
            Log::info("Forcing connection closure", ['connection_id' => $connectionId]);
            
            // 發送關閉消息（如果可能）
            try {
                $this->sendSSEMessage([
                    'event' => 'close',
                    'data' => [
                        'reason' => 'Server initiated closure',
                        'connection_id' => $connectionId,
                        'timestamp' => time()
                    ]
                ], 'close');
            } catch (\Exception $e) {
                Log::debug("Could not send close message", [
                    'connection_id' => $connectionId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // 清理輸出緩衝區
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // 關閉連線
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            
            Log::info("Connection forcefully closed", ['connection_id' => $connectionId]);
            
        } catch (\Exception $e) {
            Log::error("Error during force close", [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);
        }
    }/**
     * 處理 MCP 方法
     */
    private function handleMCPMethod(string $method, array $params)
    {
        switch ($method) {
            case 'initialize':
                return $this->mcpService->initialize($params);

            case 'tools/list':
                return $this->mcpService->getTools();

            case 'tools/call':
                $toolName = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                return $this->mcpService->callTool($toolName, $arguments);

            case 'ping':
                return ['status' => 'pong'];

            case 'notifications/initialized':
                return ['status' => 'ok'];

            default:
                throw new \Exception("Unknown method: {$method}");
        }
    }    /**
     * 發送 SSE 消息
     */
    private function sendSSEMessage(array $data, string $event = 'message')
    {
        // 先檢查連線狀態
        if (connection_aborted()) {
            throw new \Exception('Client connection already aborted before message send');
        }

        try {
            // 發送事件類型
            echo "event: {$event}\n";
            
            // 發送數據
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                throw new \Exception('Failed to encode data to JSON');
            }
            
            echo "data: {$jsonData}\n";
            
            // 發送 ID（用於重連）
            if (isset($data['id'])) {
                echo "id: " . $data['id'] . "\n";
            }
            
            // 結束消息
            echo "\n";
            
            // 強制輸出
            if (ob_get_level()) {
                @ob_flush();
            }
            @flush();

        } catch (\Exception $e) {
            Log::error('Error sending SSE message', [
                'error' => $e->getMessage(),
                'event' => $event
            ]);
            throw $e;
        }

        // 再次檢查連線狀態
        if (connection_aborted()) {
            throw new \Exception('Client connection aborted during message send');
        }
    }

    /**
     * MCP WebSocket 端點（備用）
     */
    public function websocket(Request $request)
    {
        return response()->json([
            'error' => 'WebSocket not implemented yet. Please use SSE endpoint.',
            'sse_endpoint' => route('mcp.sse')
        ], 501);
    }

    /**
     * MCP 標準 stdio 端點信息
     */
    public function stdio(Request $request)
    {
        return response()->json([
            'protocol' => 'mcp',
            'version' => '1.0.0',
            'transport' => 'stdio',
            'capabilities' => [
                'tools' => true,
                'resources' => false,
                'prompts' => false
            ],
            'endpoints' => [
                'sse' => route('mcp.sse'),
                'websocket' => route('mcp.websocket'),
                'info' => route('mcp.info')
            ]
        ]);
    }
}
