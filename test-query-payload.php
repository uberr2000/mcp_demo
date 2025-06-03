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

echo "Testing SendExcelEmailTool with sample query payload...\n\n";

try {
    // Create tool instance
    $tool = new SendExcelEmailTool();
    
    // Test query payload as provided by user
    $queryPayload = [
        'type' => 'orders',
        'email' => 'test@example.com',
        'subject' => 'Monthly Orders Report',
        'message' => 'Please find attached the monthly orders report.',
        'filters' => [
            'status' => 'completed',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31'
        ],
        'limit' => 100
    ];
    
    echo "Query Payload:\n";
    echo json_encode($queryPayload, JSON_PRETTY_PRINT) . "\n\n";
      // Test tool schema
    echo "Tool Schema:\n";
    $schema = [
        'name' => $tool->name(),
        'description' => $tool->description(),
        'inputSchema' => $tool->inputSchema()
    ];
    echo json_encode($schema, JSON_PRETTY_PRINT) . "\n\n";
    
    // Validate arguments against schema
    echo "Validating arguments against schema...\n";
    $required = $schema['inputSchema']['required'] ?? [];
    $properties = $schema['inputSchema']['properties'] ?? [];
    
    foreach ($required as $field) {
        if (!isset($queryPayload[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    foreach ($queryPayload as $key => $value) {
        if (!isset($properties[$key])) {
            echo "Warning: Field '{$key}' not in schema\n";
        }
    }
    
    echo "Arguments validation passed!\n\n";
    
    // Test tool execution (dry run - comment out actual email sending)
    echo "Testing tool execution...\n";
    
    // Note: This would actually send an email if AWS SES is configured
    // For testing purposes, you might want to comment out the actual execution
    // and just validate the tool setup
    
    echo "WARNING: This will attempt to send an actual email via AWS SES!\n";
    echo "Make sure your .env file has correct AWS SES configuration.\n";
    echo "Press Enter to continue or Ctrl+C to abort...\n";
    readline();
    
    $result = $tool->execute($queryPayload);
    
    echo "Tool execution result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($result['content'][0]['text'])) {
        $resultData = json_decode($result['content'][0]['text'], true);
        if ($resultData && $resultData['success']) {
            echo "✅ Tool executed successfully!\n";
            echo "Email sent to: {$resultData['email']}\n";
            echo "Filename: {$resultData['filename']}\n";
            echo "Records: {$resultData['records']}\n";
        } else {
            echo "❌ Tool execution failed\n";
            if (isset($resultData['error'])) {
                echo "Error: {$resultData['error']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
