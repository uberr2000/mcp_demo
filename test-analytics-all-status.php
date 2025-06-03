<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\MCP\Tools\GetOrderAnalyticsTool;

echo "=== Testing GetOrderAnalyticsTool with 'all' status ===\n";

$tool = new GetOrderAnalyticsTool();

echo "\n1. Testing status analytics with 'all' status:\n";
try {
    $result = $tool->execute([
        'analytics_type' => 'status',
        'status' => 'all',
        'limit' => 10
    ]);
    
    echo "✓ Status analytics with 'all' status successful:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "✗ Status analytics with 'all' status failed: " . $e->getMessage() . "\n";
}

echo "\n\n2. Testing product analytics with 'all' status:\n";
try {
    $result = $tool->execute([
        'analytics_type' => 'product',
        'status' => 'all',
        'limit' => 5
    ]);
    
    echo "✓ Product analytics with 'all' status successful:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "✗ Product analytics with 'all' status failed: " . $e->getMessage() . "\n";
}

echo "\n\n3. Testing daily analytics with 'all' status:\n";
try {
    $result = $tool->execute([
        'analytics_type' => 'daily',
        'status' => 'all',
        'limit' => 5
    ]);
    
    echo "✓ Daily analytics with 'all' status successful:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "✗ Daily analytics with 'all' status failed: " . $e->getMessage() . "\n";
}

echo "\n\n4. Comparing results: specific status vs 'all' status:\n";
try {
    // Get orders with completed status only
    $completedResult = $tool->execute([
        'analytics_type' => 'status',
        'status' => 'completed'
    ]);
    
    // Get orders with all statuses
    $allResult = $tool->execute([
        'analytics_type' => 'status',
        'status' => 'all'
    ]);
    
    echo "Completed status only:\n";
    echo json_encode($completedResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    echo "\n\nAll statuses:\n";
    echo json_encode($allResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Verify that 'all' includes more data than just 'completed'
    $completedCount = count($completedResult['data']);
    $allCount = count($allResult['data']);
    
    if ($allCount >= $completedCount) {
        echo "\n✓ 'All' status returns more or equal data than specific status ($allCount >= $completedCount)\n";
    } else {
        echo "\n✗ 'All' status returns less data than specific status ($allCount < $completedCount)\n";
    }
    
} catch (Exception $e) {
    echo "✗ Comparison test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
