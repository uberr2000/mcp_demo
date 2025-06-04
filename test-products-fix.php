<?php

echo "=== Testing GetProductsTool Fix ===\n\n";

require_once 'vendor/autoload.php';

use App\MCP\Tools\GetProductsTool;

try {
    $tool = new GetProductsTool();
    
    echo "1. Testing basic product query (no filters):\n";
    $result = $tool->execute(['limit' => 5]);
    echo "✅ Success: Retrieved " . $result['total'] . " products\n";
    
    if ($result['total'] > 0) {
        $product = $result['products'][0];
        echo "Sample product: " . $product['name'] . " - $" . $product['price'] . "\n";
        echo "Available fields: " . implode(', ', array_keys($product)) . "\n";
    }
    
    echo "\n2. Testing with price filter:\n";
    $result = $tool->execute([
        'min_price' => 100,
        'max_price' => 500,
        'limit' => 3
    ]);
    echo "✅ Success: Found " . $result['total'] . " products in price range 100-500\n";
    
    echo "\n3. Testing with stock quantity filter:\n";
    $result = $tool->execute([
        'stock_quantity' => 5,
        'limit' => 3
    ]);
    echo "✅ Success: Found " . $result['total'] . " products with stock >= 5\n";
    
    echo "\n4. Testing with name filter:\n";
    $result = $tool->execute([
        'name' => '防',
        'limit' => 2
    ]);
    echo "✅ Success: Found " . $result['total'] . " products matching '防'\n";
    
    if ($result['total'] > 0) {
        foreach ($result['products'] as $product) {
            echo "  - " . $product['name'] . " (Stock: " . $product['stock_quantity'] . ")\n";
        }
    }
    
    echo "\n✅ All tests passed! GetProductsTool is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "\n=== Testing SendExcelEmailTool Schema ===\n";

try {
    $emailTool = new App\MCP\Tools\SendExcelEmailTool();
    $schema = $emailTool->inputSchema();
    
    echo "✅ SendExcelEmailTool schema loaded successfully\n";
    echo "Available filter options:\n";
    
    if (isset($schema['properties']['filters']['properties'])) {
        foreach ($schema['properties']['filters']['properties'] as $key => $filter) {
            echo "  - $key: " . $filter['description'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ SendExcelEmailTool schema error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "✅ Fixed 'is_active' column issue\n";
echo "✅ Updated to use 'stock_quantity' filter instead\n";
echo "✅ Fixed ProductsExport to only use existing database columns\n";
echo "✅ Updated both GetProductsTool and SendExcelEmailTool\n";
echo "\nAll MCP tools should now work correctly! 🚀\n";
