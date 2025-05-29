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
    }

    /**
     * MCP SSE 端點 - 提供 Server-Sent Events 協議支援
     */
    public function sse(Request $request)
    {
        // 設置 SSE 標頭
        $response = new StreamedResponse(function() use ($request) {
            // 解析 JSON-RPC 請求
            $jsonRpcRequest = $request->getContent();
            $requestData = json_decode($jsonRpcRequest, true);

            if (!$requestData) {
                $this->sendSSEMessage([
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32700,
                        'message' => 'Parse error'
                    ],
                    'id' => null
                ]);
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
                ]);
            } catch (\Exception $e) {
                Log::error('MCP SSE 錯誤', [
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
                ]);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }

    /**
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
    }

    /**
     * 發送 SSE 消息
     */
    private function sendSSEMessage(array $data)
    {
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
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
