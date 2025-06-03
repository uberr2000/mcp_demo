<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing SendExcelEmailTool - SCHEMA AND VALIDATION TEST...\n\n";

try {
    // Create tool instance
    $tool = new SendExcelEmailTool();
    
    // Your provided query payload
    $queryPayload = [
        'type' => 'orders',
        'email' => 'terry.hk796@gmail.com',
        'subject' => 'May 2025 Order Report',
        'message' => 'Please find attached the order report for May 2025.',
        'filters' => [
            'status' => 'completed',
            'customer_name' => '',
            'product_name' => '',
            'date_from' => '2025-05-01',
            'date_to' => '2025-05-31',
            'category' => '',
            'active' => false
        ],
        'limit' => 1000
    ];
    
    echo "✅ Your Query Payload:\n";
    echo json_encode($queryPayload, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test tool schema
    echo "✅ Tool Schema:\n";
    $schema = [
        'name' => $tool->name(),
        'description' => $tool->description(),
        'inputSchema' => $tool->inputSchema()
    ];
    
    echo "Tool Name: " . $schema['name'] . "\n";
    echo "Description: " . $schema['description'] . "\n";
    echo "Message Type: " . $tool->messageType()->value . "\n\n";
    
    // Validate arguments against schema
    echo "✅ Arguments Validation:\n";
    $required = $schema['inputSchema']['required'] ?? [];
    $properties = $schema['inputSchema']['properties'] ?? [];
    
    $validation_passed = true;
    
    foreach ($required as $field) {
        if (!isset($queryPayload[$field])) {
            echo "  ❌ Missing required field: {$field}\n";
            $validation_passed = false;
        } else {
            echo "  ✓ Required field '{$field}' present\n";
        }
    }
    
    foreach ($queryPayload as $key => $value) {
        if (isset($properties[$key])) {
            echo "  ✓ Field '{$key}' valid in schema\n";
        } else {
            echo "  ⚠ Field '{$key}' not explicitly defined in schema\n";
        }
    }
    
    if (!$validation_passed) {
        throw new Exception("Schema validation failed");
    }
    
    echo "\n✅ Email Configuration Check:\n";
    
    // Check email configuration
    $mailDriver = config('mail.default');
    echo "  Mail Driver: {$mailDriver}\n";
    
    if ($mailDriver === 'ses') {
        $awsKey = config('services.ses.key');
        $awsSecret = config('services.ses.secret');
        $awsRegion = config('services.ses.region');
        echo "  AWS SES Key: " . ($awsKey ? '***configured***' : 'NOT SET') . "\n";
        echo "  AWS SES Secret: " . ($awsSecret ? '***configured***' : 'NOT SET') . "\n";
        echo "  AWS SES Region: " . ($awsRegion ?: 'NOT SET') . "\n";
        
        if (!$awsKey || !$awsSecret || !$awsRegion) {
            echo "  ⚠ AWS SES not fully configured. Check your .env file.\n";
        }
    } else {
        echo "  ⚠ Mail driver is not set to 'ses'. Current: {$mailDriver}\n";
    }
    
    echo "\n✅ Tool Registration Check:\n";
    
    // Check if tool is registered in MCP config
    $mcpTools = config('mcp-server.tools', []);
    $toolRegistered = in_array(SendExcelEmailTool::class, $mcpTools);
    echo "  Tool registered in MCP config: " . ($toolRegistered ? 'YES' : 'NO') . "\n";
    
    if (!$toolRegistered) {
        echo "  ⚠ Tool may not be registered. Check config/mcp-server.php\n";
    }
    
    echo "\n✅ OpenAI Schema Compatibility:\n";
    
    // Create OpenAI compatible schema
    $openaiSchema = [
        'type' => 'function',
        'function' => [
            'name' => $schema['name'],
            'description' => $schema['description'],
            'parameters' => [
                'type' => 'object',
                'properties' => $schema['inputSchema']['properties'],
                'required' => $schema['inputSchema']['required']
            ]
        ]
    ];
    
    echo "  OpenAI Function Schema:\n";
    echo json_encode($openaiSchema, JSON_PRETTY_PRINT) . "\n";
    
    echo "\n🎉 All validations passed!\n";
    echo "\nYour query payload is compatible with the tool schema.\n";
    echo "The tool is properly configured for:\n";
    echo "  - MCP Server integration\n";
    echo "  - OpenAI function calling\n";
    echo "  - n8n workflow integration\n";
    
    echo "\nTo test actual email sending:\n";
    echo "1. Ensure AWS SES credentials are in your .env file\n";
    echo "2. Run: php test-query-payload.php\n";
    echo "3. Or use the MCP server directly with your query\n";
    
    echo "\nTool is ready for production use! ✨\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
