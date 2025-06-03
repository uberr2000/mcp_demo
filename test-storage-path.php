<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing Storage Path Resolution...\n\n";

try {
    // Test storage paths
    echo "✅ Storage Configuration:\n";
    echo "  Default disk: " . config('filesystems.default') . "\n";
    echo "  Local disk root: " . config('filesystems.disks.local.root') . "\n";
    echo "  Storage app path: " . storage_path('app') . "\n";
    echo "  Storage app/private path: " . storage_path('app/private') . "\n";
    
    // Ensure directories exist
    if (!Storage::exists('exports')) {
        Storage::makeDirectory('exports');
        echo "  Created exports directory\n";
    }
    
    // Create test file
    $testFilePath = "exports/path_test_" . date('Y-m-d_H-i-s') . ".txt";
    Storage::put($testFilePath, 'Test content for path resolution');
    
    echo "\n✅ Path Resolution Test:\n";
    echo "  Test file path: {$testFilePath}\n";
    echo "  Storage::exists(): " . (Storage::exists($testFilePath) ? 'YES' : 'NO') . "\n";
    
    // Test different path methods
    $method1 = storage_path("app/{$testFilePath}");
    $method2 = Storage::disk('local')->path($testFilePath);
    
    echo "  Method 1 (storage_path): {$method1}\n";
    echo "  Method 1 exists: " . (file_exists($method1) ? 'YES' : 'NO') . "\n";
    
    echo "  Method 2 (Storage::path): {$method2}\n";
    echo "  Method 2 exists: " . (file_exists($method2) ? 'YES' : 'NO') . "\n";
    
    // Find which method works
    if (file_exists($method2)) {
        echo "  ✓ Method 2 (Storage::disk('local')->path()) works correctly!\n";
        
        // Test if we can read the file
        $content = file_get_contents($method2);
        echo "  ✓ File content: {$content}\n";
        
    } elseif (file_exists($method1)) {
        echo "  ✓ Method 1 (storage_path()) works correctly!\n";
    } else {
        echo "  ❌ Neither method finds the actual file\n";
        
        // List directory contents to debug
        echo "  Debugging - checking directory contents:\n";
        $privateDir = storage_path('app/private');
        $appDir = storage_path('app');
        
        if (is_dir($privateDir)) {
            echo "  Private dir contents: " . implode(', ', scandir($privateDir)) . "\n";
            if (is_dir($privateDir . '/exports')) {
                echo "  Private/exports contents: " . implode(', ', scandir($privateDir . '/exports')) . "\n";
            }
        }
        
        if (is_dir($appDir)) {
            echo "  App dir contents: " . implode(', ', scandir($appDir)) . "\n";
            if (is_dir($appDir . '/exports')) {
                echo "  App/exports contents: " . implode(', ', scandir($appDir . '/exports')) . "\n";
            }
        }
    }
    
    // Clean up
    Storage::delete($testFilePath);
    echo "  ✓ Test file cleaned up\n";
    
    echo "\n🎉 Path resolution test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
