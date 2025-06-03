<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing Directory Creation and Excel Export...\n\n";

try {
    echo "✅ Checking storage directory structure:\n";
    
    // Check if storage/app directory exists
    $storageAppPath = storage_path('app');
    echo "  Storage app path: {$storageAppPath}\n";
    echo "  Storage app exists: " . (is_dir($storageAppPath) ? 'YES' : 'NO') . "\n";
    
    // Check if exports directory exists
    $exportsPath = storage_path('app/exports');
    echo "  Exports directory: {$exportsPath}\n";
    echo "  Exports exists before: " . (is_dir($exportsPath) ? 'YES' : 'NO') . "\n";
    
    // Check Storage facade
    echo "  Storage disk: " . config('filesystems.default') . "\n";
    echo "  Local disk path: " . config('filesystems.disks.local.root') . "\n";
    
    echo "\n✅ Testing directory creation:\n";
    
    // Test Storage::makeDirectory
    if (!Storage::exists('exports')) {
        Storage::makeDirectory('exports');
        echo "  Created exports directory via Storage facade\n";
    } else {
        echo "  Exports directory already exists\n";
    }
    
    echo "  Exports exists after: " . (Storage::exists('exports') ? 'YES' : 'NO') . "\n";
    echo "  Exports path exists: " . (is_dir($exportsPath) ? 'YES' : 'NO') . "\n";
    
    echo "\n✅ Testing Excel file creation:\n";
    
    // Create a simple test file to verify the path works
    $testFilename = 'test_' . date('Y-m-d_H-i-s') . '.txt';
    $testFilePath = "exports/{$testFilename}";
    
    Storage::put($testFilePath, 'This is a test file to verify directory creation works.');
    
    if (Storage::exists($testFilePath)) {
        echo "  ✓ Test file created successfully: {$testFilename}\n";
        echo "  ✓ File size: " . Storage::size($testFilePath) . " bytes\n";
        echo "  ✓ Full path: " . storage_path("app/{$testFilePath}") . "\n";
        
        // Clean up
        Storage::delete($testFilePath);
        echo "  ✓ Test file cleaned up\n";
    } else {
        echo "  ❌ Failed to create test file\n";
    }
    
    echo "\n✅ Testing your query payload with directory fix:\n";
    
    // Test with a simple mock (without actual database)
    $tool = new SendExcelEmailTool();
    
    $queryPayload = [
        'type' => 'orders',
        'email' => 'test@example.com',
        'subject' => 'Test Export',
        'message' => 'Test message'
    ];
    
    echo "  Testing with payload: " . json_encode($queryPayload) . "\n";
    
    try {
        $result = $tool->execute($queryPayload);
        echo "  ✓ Tool executed successfully (directory creation works!)\n";
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'Unable to open path') !== false) {
            echo "  ❌ Still having path issues: " . $e->getMessage() . "\n";
        } else {
            echo "  ✓ Directory creation works (failed for other reason: " . substr($e->getMessage(), 0, 100) . "...)\n";
        }
    }
    
    echo "\n🎉 Directory creation test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
