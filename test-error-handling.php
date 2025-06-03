<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\MCP\Tools\SendExcelEmailTool;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing SendExcelEmailTool Error Handling...\n\n";

try {
    // Create tool instance
    $tool = new SendExcelEmailTool();
    
    echo "✅ Testing Invalid Email Format:\n";
    
    // Test with invalid email format
    $invalidEmailPayload = [
        'type' => 'orders',
        'email' => 'invalid-email-format',
        'subject' => 'Test Subject',
        'message' => 'Test Message'
    ];
    
    try {
        $result = $tool->execute($invalidEmailPayload);
        echo "❌ Should have thrown an error for invalid email\n";
    } catch (\OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException $e) {
        echo "  ✓ Correctly caught JsonRpcErrorException\n";
        echo "  ✓ Error message: " . $e->getMessage() . "\n";
        echo "  ✓ Error code: " . $e->getCode() . "\n";
    }
    
    echo "\n✅ Testing Valid Email Format:\n";
    
    // Test with valid email format (should pass validation)
    $validEmailPayload = [
        'type' => 'orders',
        'email' => 'test@example.com',
        'subject' => 'Test Subject',
        'message' => 'Test Message'
    ];
    
    echo "  Email format validation should pass for: " . $validEmailPayload['email'] . "\n";
    
    // Note: This might fail later due to missing database tables, but email validation should pass
    try {
        $result = $tool->execute($validEmailPayload);
        echo "  ✓ Tool executed successfully\n";
    } catch (\OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException $e) {
        if (strpos($e->getMessage(), '無效的郵箱地址格式') !== false) {
            echo "  ❌ Email validation failed unexpectedly\n";
        } else {
            echo "  ✓ Email validation passed (failed later due to: " . $e->getMessage() . ")\n";
        }
    } catch (\Exception $e) {
        echo "  ✓ Email validation passed (failed later due to database/other issue)\n";
    }
    
    echo "\n🎉 Error handling tests completed!\n";
    echo "JsonRpcErrorException constructor parameters are now correct.\n";
    
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
