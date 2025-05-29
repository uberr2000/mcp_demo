<?php

namespace App\Services;

use App\Contracts\MCPToolInterface;
// Note: This service is deprecated in favor of the official php-mcp/laravel package
// The old tools are no longer used

class MCPService
{
    private array $tools = [];

    public function __construct()
    {
        // Commented out old tools - now using official MCP package
        // $this->registerTool(new OrderTool());
        // $this->registerTool(new ProductTool()); 
        // $this->registerTool(new CustomerStatsTool());
        // $this->registerTool(new OrderAnalyticsTool());
    }

    private function registerTool(MCPToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function getTools(): array
    {
        return array_map(function (MCPToolInterface $tool) {
            return [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $tool->getInputSchema(),
                    'required' => $this->getRequiredFields($tool->getInputSchema())
                ]
            ];
        }, $this->tools);
    }

    public function executeTool(string $toolName, array $parameters): array
    {
        if (!isset($this->tools[$toolName])) {
            throw new \InvalidArgumentException("Tool '{$toolName}' not found");
        }

        $tool = $this->tools[$toolName];
        return $tool->execute($parameters);
    }

    private function getRequiredFields(array $schema): array
    {
        $required = [];
        foreach ($schema as $field => $config) {
            if (isset($config['required']) && $config['required']) {
                $required[] = $field;
            }
        }
        return $required;
    }

    public function getCapabilities(): array
    {
        return [
            'experimental' => [],
            'logging' => [],
            'prompts' => [],
            'resources' => [],
            'tools' => $this->getTools()
        ];
    }

    /**
     * 處理 MCP initialize 請求
     */
    public function initialize(array $params): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => [],
                'resources' => [],
                'prompts' => []
            ],
            'serverInfo' => [
                'name' => 'Laravel MCP Demo',
                'version' => '1.0.0'
            ]
        ];
    }

    /**
     * 調用工具 (MCP 格式)
     */
    public function callTool(string $toolName, array $arguments): array
    {
        try {
            $result = $this->executeTool($toolName, $arguments);
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Error: ' . $e->getMessage()
                    ]
                ],
                'isError' => true
            ];
        }
    }

    /**
     * 取得 MCP 格式的工具列表
     */
    public function getToolsList(): array
    {
        return [
            'tools' => array_map(function (MCPToolInterface $tool) {
                return [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => $tool->getInputSchema(),
                        'required' => $this->getRequiredFields($tool->getInputSchema())
                    ]
                ];
            }, $this->tools)
        ];
    }
}
