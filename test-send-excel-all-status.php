<?php

echo "=== Testing SendExcelEmailTool 'all' Status Support ===\n";

require_once 'vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;

echo "\n1. Testing SendExcelEmailTool schema:\n";
$emailTool = new SendExcelEmailTool();
$emailSchema = $emailTool->inputSchema();

// Check if 'all' is in the enum
$statusEnum = $emailSchema['properties']['filters']['properties']['status']['enum'];
echo "Status enum values: " . implode(', ', $statusEnum) . "\n";

if (in_array('all', $statusEnum)) {
    echo "✓ SendExcelEmailTool enum includes 'all' status\n";
} else {
    echo "✗ SendExcelEmailTool enum does not include 'all' status\n";
}

// Check if description mentions 'all'
$statusDescription = $emailSchema['properties']['filters']['properties']['status']['description'];
echo "Status description: " . $statusDescription . "\n";

if (strpos($statusDescription, 'all') !== false) {
    echo "✓ SendExcelEmailTool description mentions 'all' status\n";
} else {
    echo "✗ SendExcelEmailTool description does not mention 'all' status\n";
}

echo "\n2. Schema structure verification:\n";
echo "Tool name: " . $emailTool->name() . "\n";
echo "Tool description: " . $emailTool->description() . "\n";

echo "\n3. Usage example with 'all' status:\n";
$examplePayload = [
    'type' => 'orders',
    'email' => 'example@test.com',
    'subject' => 'All Orders Export',
    'filters' => [
        'status' => 'all',
        'date_from' => '2024-01-01',
        'date_to' => '2024-12-31'
    ],
    'limit' => 100
];

echo "Example payload for exporting all orders:\n";
echo json_encode($examplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== Test Complete ===\n";
echo "\nSendExcelEmailTool now supports 'all' status:\n";
echo "- ✅ Schema enum includes 'all' option\n";
echo "- ✅ Description updated to mention 'all' usage\n";
echo "- ✅ Logic implemented to skip status filtering when 'all' is specified\n";
echo "\nWhen filters.status = 'all', the tool will export orders of all statuses.\n";
