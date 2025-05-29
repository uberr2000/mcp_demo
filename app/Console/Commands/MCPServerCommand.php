<?php

namespace App\Console\Commands;

use App\Services\MCPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MCPServerCommand extends Command
{
    protected $signature = 'mcp:server';
    protected $description = 'Start MCP Server using stdio transport';

    private MCPService $mcpService;

    public function __construct(MCPService $mcpService)
    {
        parent::__construct();
        $this->mcpService = $mcpService;
    }    public function handle()
    {
        // 設置 stdio 模式
        while (true) {
            $input = stream_get_contents(STDIN);
            
            if (empty($input)) {
                // 嘗試逐行讀取
                $input = trim(fgets(STDIN));
                if (empty($input)) {
                    continue;
                }
            } else {
                $input = trim($input);
            }

            try {
                $request = json_decode($input, true);
                
                if (!$request || json_last_error() !== JSON_ERROR_NONE) {
                    $this->sendError(-32700, 'Parse error: ' . json_last_error_msg(), null);
                    continue;
                }

                $method = $request['method'] ?? '';
                $params = $request['params'] ?? [];
                $id = $request['id'] ?? null;

                $result = $this->handleMethod($method, $params);
                $this->sendResult($result, $id);

                // 如果是單次調用就退出
                if (!empty($input)) {
                    break;
                }

            } catch (\Exception $e) {
                Log::error('MCP Server 錯誤', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->sendError(-32603, 'Internal error: ' . $e->getMessage(), $request['id'] ?? null);
            }
        }
    }

    private function handleMethod(string $method, array $params)
    {
        switch ($method) {
            case 'initialize':
                return $this->mcpService->initialize($params);

            case 'tools/list':
                return $this->mcpService->getToolsList();

            case 'tools/call':
                $name = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                return $this->mcpService->callTool($name, $arguments);

            case 'ping':
                return ['status' => 'pong'];

            case 'notifications/initialized':
                return ['status' => 'ok'];

            default:
                throw new \Exception("Unknown method: {$method}");
        }
    }

    private function sendResult($result, $id)
    {
        $response = [
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id
        ];

        echo json_encode($response) . "\n";
        flush();
    }

    private function sendError(int $code, string $message, $id)
    {
        $response = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'id' => $id
        ];

        echo json_encode($response) . "\n";
        flush();
    }
}
