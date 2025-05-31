<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\MCP\Tools\GetOrderAnalyticsTool;

echo "=== Testing GetOrderAnalyticsTool with fixed logic ===\n";

$tool = new GetOrderAnalyticsTool();
$result = $tool->execute([
    'analytics_type' => 'product',
    'status' => 'completed', 
    'limit' => 1
]);

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== Testing with limit 3 for comparison ===\n";
$result3 = $tool->execute([
    'analytics_type' => 'product',
    'status' => 'completed',
    'limit' => 3
]);

echo json_encode($result3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
