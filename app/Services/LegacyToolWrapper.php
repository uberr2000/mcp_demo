<?php

namespace App\Services;

use App\Contracts\MCPToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class LegacyToolWrapper implements MCPToolInterface
{
    private ToolInterface $modernTool;

    public function __construct(ToolInterface $modernTool)
    {
        $this->modernTool = $modernTool;
    }

    public function getName(): string
    {
        return $this->modernTool->name();
    }

    public function getDescription(): string
    {
        return $this->modernTool->description();
    }

    public function getInputSchema(): array
    {
        return $this->modernTool->inputSchema();
    }    public function execute(array $parameters): array
    {
        try {
            $result = $this->modernTool->execute($parameters);
            
            // Convert modern tool result to legacy format
            if (is_array($result) && isset($result['content'])) {
                // Already in MCP format
                return $result;
            }
            
            // Convert simple result to MCP format
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
