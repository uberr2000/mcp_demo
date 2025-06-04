<?php

echo "=== All MCP Tools Schemas ===\n\n";

require_once 'vendor/autoload.php';

// Import all MCP tools
use App\MCP\Tools\GetOrdersTool;
use App\MCP\Tools\GetProductsTool;
use App\MCP\Tools\GetCustomerStatsTool;
use App\MCP\Tools\GetOrderAnalyticsTool;
use App\MCP\Tools\SendExcelEmailTool;

$tools = [
    new GetOrdersTool(),
    new GetProductsTool(),
    new GetCustomerStatsTool(),
    new GetOrderAnalyticsTool(),
    new SendExcelEmailTool(),
];

$allSchemas = [];

foreach ($tools as $tool) {
    $schema = [
        'name' => $tool->name(),
        'description' => $tool->description(),
        'inputSchema' => $tool->inputSchema()
    ];
    
    $allSchemas[] = $schema;
    
    echo "## " . strtoupper($tool->name()) . "\n";
    echo "**Description:** " . $tool->description() . "\n\n";
    echo "**Schema:**\n";
    echo "```json\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "```\n\n";
    echo "---\n\n";
}

echo "\n=== Complete JSON Schema for All Tools ===\n";
echo "```json\n";
echo json_encode($allSchemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n```\n";

echo "\n=== Summary ===\n";
echo "Total MCP Tools: " . count($tools) . "\n";
foreach ($tools as $tool) {
    echo "- " . $tool->name() . " (supports 'all' status: " . 
         (strpos(json_encode($tool->inputSchema()), '"all"') !== false ? "✅" : "❌") . ")\n";
}

echo "\nAll tools are ready for n8n integration! 🚀\n";
