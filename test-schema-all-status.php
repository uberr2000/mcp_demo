<?php

// Simple test without Laravel bootstrap issues
echo "=== Testing Order Status 'all' Functionality ===\n";

// Test the input schema validation
echo "1. Checking input schema allows 'all' status:\n";

require_once 'vendor/autoload.php';

// Instead of bootstrapping Laravel, let's just check the schema
use App\MCP\Tools\GetOrderAnalyticsTool;

$tool = new GetOrderAnalyticsTool();
$schema = $tool->inputSchema();

echo "Status property in schema:\n";
print_r($schema['properties']['status']);

// Check if 'all' is mentioned in the description
if (strpos($schema['properties']['status']['description'], 'all') !== false) {
    echo "✓ Schema mentions 'all' status in description\n";
} else {
    echo "✗ Schema does not mention 'all' status in description\n";
}

echo "\n=== Schema Test Complete ===\n";
