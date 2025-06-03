<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;

// Test the SendExcelEmailTool
echo "🧪 Testing SendExcelEmailTool...\n\n";

try {
    $tool = new SendExcelEmailTool();
    
    echo "Tool Name: " . $tool->name() . "\n";
    echo "Description: " . $tool->description() . "\n\n";
    
    echo "📋 Input Schema:\n";
    print_r($tool->inputSchema());
    
    echo "\n=== Testing Orders Export ===\n";
    
    // Test orders export with filters
    $ordersResult = $tool->execute([
        'type' => 'orders',
        'email' => 'test@example.com', // Change this to your test email
        'subject' => 'Test Orders Export',
        'message' => 'This is a test export of orders data.',
        'filters' => [
            'status' => 'completed',
            'limit' => 10
        ],
        'limit' => 10
    ]);
    
    echo "Orders Export Result:\n";
    print_r($ordersResult);
    
    echo "\n=== Testing Products Export ===\n";
    
    // Test products export
    $productsResult = $tool->execute([
        'type' => 'products',
        'email' => 'test@example.com', // Change this to your test email
        'subject' => 'Test Products Export',
        'filters' => [
            'active' => true
        ],
        'limit' => 10
    ]);
    
    echo "Products Export Result:\n";
    print_r($productsResult);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test completed!\n";
