<?php

namespace App\Http\Controllers;

use App\Services\MCPService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MCPController extends Controller
{
    private MCPService $mcpService;    public function __construct(MCPService $mcpService)
    {
        $this->mcpService = $mcpService;
    }

    public function initialize(Request $request): JsonResponse
    {
        return response()->json([
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => 'Laravel Orders MCP Server',
                'version' => '1.0.0'
            ],
            'capabilities' => $this->mcpService->getCapabilities()
        ]);
    }

    public function listTools(Request $request): JsonResponse
    {
        return response()->json([
            'tools' => $this->mcpService->getTools()
        ]);
    }

    public function callTool(Request $request): JsonResponse
    {
        try {
            $toolName = $request->input('name');
            $arguments = $request->input('arguments', []);

            if (!$toolName) {
                return response()->json([
                    'error' => [
                        'code' => -32602,
                        'message' => 'Tool name is required'
                    ]
                ], 400);
            }

            $result = $this->mcpService->executeTool($toolName, $arguments);

            return response()->json([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT)
                    ]
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => [
                    'code' => -32601,
                    'message' => $e->getMessage()
                ]
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString()
        ]);
    }

    public function getServerInfo(Request $request): JsonResponse
    {
        return response()->json([
            'name' => 'Laravel Orders MCP Server',
            'version' => '1.0.0',
            'description' => 'MCP server for querying orders and products data',
            'tools' => $this->mcpService->getTools(),
            'endpoints' => [
                'initialize' => route('mcp.initialize'),
                'tools/list' => route('mcp.tools.list'),
                'tools/call' => route('mcp.tools.call'),
                'ping' => route('mcp.ping')
            ]
        ]);
    }
}
