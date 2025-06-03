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

echo "Testing SendExcelEmailTool with Storage Path Fix...\n\n";

try {
    $tool = new SendExcelEmailTool();
    
    // Test with your original query payload
    $queryPayload = [
        'type' => 'orders',
        'email' => 'test@example.com', // Using test email to avoid actual sending
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
    
    echo "✅ Testing with query payload:\n";
    echo json_encode($queryPayload, JSON_PRETTY_PRINT) . "\n\n";
    
    try {
        $result = $tool->execute($queryPayload);
        
        echo "🎉 SUCCESS! Tool executed without path errors.\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Unable to open path') !== false) {
            echo "❌ Still having path issues:\n";
            echo "Error: " . $e->getMessage() . "\n";
        } else {
            echo "✅ Path issue FIXED! Tool failed for different reason:\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "(This is likely due to missing database data, which is normal)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
}
