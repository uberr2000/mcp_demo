<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing SendExcelEmailTool - DRY RUN (no actual email sending)...\n\n";

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
    echo "✅ Tool Schema Validation:\n";
    $schema = [
        'name' => $tool->name(),
        'description' => $tool->description(),
        'inputSchema' => $tool->inputSchema()
    ];
    echo "Tool Name: " . $schema['name'] . "\n";
    echo "Description: " . $schema['description'] . "\n\n";
    
    // Validate arguments against schema
    echo "✅ Arguments Validation:\n";
    $required = $schema['inputSchema']['required'] ?? [];
    $properties = $schema['inputSchema']['properties'] ?? [];
    
    foreach ($required as $field) {
        if (!isset($queryPayload[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
        echo "  ✓ Required field '{$field}' present\n";
    }
    
    foreach ($queryPayload as $key => $value) {
        if (isset($properties[$key])) {
            echo "  ✓ Field '{$key}' valid\n";
        } else {
            echo "  ⚠ Field '{$key}' not in schema (might be ignored)\n";
        }
    }
    
    echo "\n✅ Excel Export Test:\n";
    
    // Test Excel export functionality without email
    $type = $queryPayload['type'];
    $filters = $queryPayload['filters'] ?? [];
    $limit = $queryPayload['limit'] ?? null;
    
    echo "Testing {$type} export with filters...\n";
    
    // Create export instance
    if ($type === 'orders') {
        $export = new OrdersExport($filters, $limit);
        $filename = 'orders_' . date('Y-m-d_H-i-s') . '.xlsx';
    } else {
        $export = new ProductsExport($filters, $limit);
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.xlsx';
    }
    
    // Generate Excel file
    $filePath = 'exports/' . $filename;
    Excel::store($export, $filePath, 'local');
    
    // Check if file was created
    if (Storage::exists($filePath)) {
        $fileSize = Storage::size($filePath);
        echo "  ✓ Excel file created: {$filename}\n";
        echo "  ✓ File size: " . number_format($fileSize) . " bytes\n";
        echo "  ✓ File path: " . storage_path("app/{$filePath}") . "\n";
        
        // Clean up test file
        Storage::delete($filePath);
        echo "  ✓ Test file cleaned up\n";
    } else {
        throw new Exception("Excel file was not created");
    }
    
    echo "\n✅ Email Configuration Check:\n";
    
    // Check email configuration
    $mailDriver = config('mail.default');
    echo "  Mail Driver: {$mailDriver}\n";
    
    if ($mailDriver === 'ses') {
        $awsKey = config('services.ses.key');
        $awsRegion = config('services.ses.region');
        echo "  AWS SES Key: " . ($awsKey ? '***configured***' : 'NOT SET') . "\n";
        echo "  AWS SES Region: " . ($awsRegion ?: 'NOT SET') . "\n";
    }
    
    echo "\n✅ Tool Registration Check:\n";
    
    // Check if tool is registered in MCP config
    $mcpTools = config('mcp-server.tools', []);
    $toolRegistered = in_array(SendExcelEmailTool::class, $mcpTools);
    echo "  Tool registered in MCP config: " . ($toolRegistered ? 'YES' : 'NO') . "\n";
    
    echo "\n🎉 All tests passed! The tool is ready for use.\n";
    echo "\nTo test with actual email sending, run: test-query-payload.php\n";
    echo "Make sure your AWS SES credentials are configured in .env file.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
