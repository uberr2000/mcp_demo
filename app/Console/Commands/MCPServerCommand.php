<?php

namespace App\Console\Commands;

use App\Services\MCPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MCPServerCommand extends Command
{    protected $signature = 'mcp:server {--debug : Enable debug output}';
    protected $description = 'Start MCP Server using stdio transport';

    private MCPService $mcpService;

    public function __construct(MCPService $mcpService)
    {
        parent::__construct();
        $this->mcpService = $mcpService;
    }    public function handle()
    {
        $debug = $this->option('debug');
        
        if ($debug) {
            $this->info('Starting MCP Server with stdio transport...');
            $this->info('Waiting for JSON-RPC requests...');
            $this->info('Send JSON-RPC messages to stdin. Example:');
            $this->info('{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}');
            $this->info('Press Ctrl+C to stop.');
        }

        while (true) {
            // 讀取一行輸入
            $input = fgets(STDIN);
            
            if ($input === false) {
                // EOF reached
                break;
            }
              $input = trim($input);
            
            // Remove BOM if present
            $input = preg_replace('/^\xEF\xBB\xBF/', '', $input);
            
            if (empty($input)) {
                continue;
            }
            
            if ($debug) {
                $this->info("Received input: " . $input);
            }

            try {
                $request = json_decode($input, true);
                
                if (!$request || json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'Parse error: ' . json_last_error_msg();
                    if ($debug) $this->error($error);
                    $this->sendError(-32700, $error, null);
                    continue;
                }

                $method = $request['method'] ?? '';
                $params = $request['params'] ?? [];
                $id = $request['id'] ?? null;

                if ($debug) {
                    $this->info("Processing method: {$method}");
                }

                $result = $this->handleMethod($method, $params);
                $this->sendResult($result, $id);

                if ($debug) {
                    $this->info("Sent response for method: {$method}");
                }                // For piped input, exit after processing one request
                if (!$debug) {
                    // On Windows, we'll just process one request for piped input
                    if (PHP_OS_FAMILY === 'Windows' || !function_exists('posix_isatty') || !posix_isatty(STDIN)) {
                        break;
                    }
                }

            } catch (\Exception $e) {
                $errorMsg = 'Internal error: ' . $e->getMessage();
                if ($debug) {
                    $this->error($errorMsg);
                    $this->error($e->getTraceAsString());
                }
                
                Log::error('MCP Server 錯誤', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->sendError(-32603, $errorMsg, $request['id'] ?? null);
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
        if (ob_get_level()) {
            ob_flush();
        }
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
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
