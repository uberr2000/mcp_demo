<?php

// Diagnostic script to check MCP setup
echo "=== MCP Laravel Diagnostic ===\n";

// Check if composer dependencies are installed
echo "1. Checking composer dependencies...\n";
if (file_exists('vendor/php-mcp/laravel/src/McpServiceProvider.php')) {
    echo "✅ php-mcp/laravel package found\n";
} else {
    echo "❌ php-mcp/laravel package NOT found - run 'composer install'\n";
}

// Check if MCP config is published
echo "\n2. Checking MCP configuration...\n";
if (file_exists('config/mcp.php')) {
    echo "✅ MCP config file exists\n";
} else {
    echo "❌ MCP config file missing - run 'php artisan vendor:publish --tag=mcp-config'\n";
}

// Check if tool files exist
echo "\n3. Checking MCP tool files...\n";
$toolFiles = [
    'app/Mcp/Tools/GetOrdersTool.php',
    'app/Mcp/Tools/GetProductsTool.php', 
    'app/Mcp/Tools/GetCustomerStatsTool.php',
    'app/Mcp/Tools/GetOrderAnalyticsTool.php'
];

foreach ($toolFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Check if classes can be autoloaded
echo "\n4. Checking class autoloading...\n";
try {
    if (class_exists('App\\Mcp\\Tools\\GetOrdersTool')) {
        echo "✅ GetOrdersTool class can be autoloaded\n";
    } else {
        echo "❌ GetOrdersTool class cannot be autoloaded - run 'composer dump-autoload'\n";
    }
} catch (Exception $e) {
    echo "❌ Error autoloading classes: " . $e->getMessage() . "\n";
}

// Check Laravel app key
echo "\n5. Checking Laravel setup...\n";
if (env('APP_KEY')) {
    echo "✅ Laravel APP_KEY is set\n";
} else {
    echo "❌ Laravel APP_KEY missing - run 'php artisan key:generate'\n";
}

// Check database connection
echo "\n6. Checking database connection...\n";
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== End Diagnostic ===\n";
echo "\nIf any items show ❌, fix them and then run 'php artisan mcp:discover'\n";
