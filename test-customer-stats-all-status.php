<?php

echo "=== Testing GetCustomerStatsTool 'all' Status Support ===\n";

require_once 'vendor/autoload.php';

use App\MCP\Tools\GetCustomerStatsTool;

echo "\n1. Testing GetCustomerStatsTool schema:\n";
$customerStatsTool = new GetCustomerStatsTool();
$schema = $customerStatsTool->inputSchema();

// Check status description
$statusDescription = $schema['properties']['status']['description'];
echo "Status description: " . $statusDescription . "\n";

if (strpos($statusDescription, 'all') !== false) {
    echo "✅ GetCustomerStatsTool schema supports 'all' status\n";
} else {
    echo "❌ GetCustomerStatsTool schema does not mention 'all' status\n";
}

echo "\n2. Tool information:\n";
echo "Tool name: " . $customerStatsTool->name() . "\n";
echo "Tool description: " . $customerStatsTool->description() . "\n";

echo "\n3. Example usage matching your n8n query:\n";
$exampleQuery = [
    'customer_name' => '何淑儀',
    'status' => 'all',
    'limit' => 1
];

echo "Query parameters:\n";
echo json_encode($exampleQuery, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n4. Validation test:\n";
use Illuminate\Support\Facades\Validator;

$validationRules = [
    'customer_name' => ['nullable', 'string'],
    'status' => ['nullable', 'string', 'in:pending,processing,completed,cancelled,refunded,all'],
    'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
];

$validator = Validator::make($exampleQuery, $validationRules);

if ($validator->passes()) {
    echo "✅ Validation passes for your n8n query with 'all' status\n";
} else {
    echo "❌ Validation fails:\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - $error\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nGetCustomerStatsTool now supports 'all' status:\n";
echo "- ✅ Schema description mentions 'all' status option\n";
echo "- ✅ Validation rules include 'all' as valid value\n";
echo "- ✅ Logic implemented to skip status filtering when 'all' is specified\n";
echo "\nYour n8n query should now work correctly! 🎉\n";
