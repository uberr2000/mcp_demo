<?php

namespace App\Services;

use App\Contracts\MCPToolInterface;
use App\MCP\Tools\OrderTool;
use App\MCP\Tools\ProductTool;
use App\MCP\Tools\CustomerStatsTool;
use App\MCP\Tools\OrderAnalyticsTool;

class MCPService
{
    private array $tools = [];    public function __construct()
    {
        $this->registerTool(new OrderTool());
        $this->registerTool(new ProductTool());
        $this->registerTool(new CustomerStatsTool());
        $this->registerTool(new OrderAnalyticsTool());
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
}
