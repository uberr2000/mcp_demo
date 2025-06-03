<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up Facade Application instance
Facade::setFacadeApplication($app);

echo "Testing Excel Generation with Mock Order Data...\n\n";

try {
    // Ensure exports directory exists
    if (!Storage::exists('exports')) {
        Storage::makeDirectory('exports');
    }
    
    // Create mock data that matches the expected structure from OrdersExport
    $mockOrdersData = [
        [
            'transaction_id' => 'ORD-2025-001',
            'name' => 'John Doe',  // This is the customer name
            'product' => [
                'name' => 'Premium Widget',
                'category' => 'Electronics'
            ],
            'quantity' => 2,
            'amount' => 199.99,
            'status' => 'completed',
            'created_at' => '2025-05-15 10:30:00'
        ],
        [
            'transaction_id' => 'ORD-2025-002',
            'name' => 'Jane Smith',
            'product' => [
                'name' => 'Smart Device',
                'category' => 'Electronics'
            ],
            'quantity' => 1,
            'amount' => 299.99,
            'status' => 'completed',
            'created_at' => '2025-05-20 14:15:00'
        ]
    ];
    
    echo "✅ Mock data created with " . count($mockOrdersData) . " orders\n";
    
    // Test Excel export
    $filename = 'test_orders_' . date('Y-m-d_H-i-s') . '.xlsx';
    $filePath = "exports/{$filename}";
    
    echo "\n✅ Creating Excel file: {$filename}\n";
    
    try {
        $export = new OrdersExport($mockOrdersData);
        Excel::store($export, $filePath, 'local');
        
        echo "  ✓ Excel::store() completed\n";
        
        if (Storage::exists($filePath)) {
            $fileSize = Storage::size($filePath);
            $fullPath = storage_path("app/{$filePath}");
            
            echo "  ✓ File created successfully\n";
            echo "  ✓ File size: " . number_format($fileSize) . " bytes\n";
            echo "  ✓ Full path: {$fullPath}\n";
            echo "  ✓ File exists on disk: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            
            // Test if the file can be read
            if (file_exists($fullPath) && is_readable($fullPath)) {
                echo "  ✓ File is readable\n";
                
                // Test email attachment simulation
                echo "\n✅ Testing email attachment:\n";
                echo "  Attachment path: {$fullPath}\n";
                echo "  MIME type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\n";
                echo "  Attachment name: {$filename}\n";
                
                echo "\n🎉 Excel generation test PASSED!\n";
                echo "The file creation works properly with correct data structure.\n";
                
            } else {
                echo "  ❌ File exists in Storage but not readable on disk\n";
            }
            
            // Clean up
            Storage::delete($filePath);
            echo "  ✓ Test file cleaned up\n";
            
        } else {
            echo "  ❌ Excel file was not created\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Excel creation failed: " . $e->getMessage() . "\n";
        echo "  Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n✅ Data Structure Analysis:\n";
    echo "The issue might be that getOrdersData() returns empty array or wrong structure.\n";
    echo "Expected structure for OrdersExport:\n";
    echo json_encode($mockOrdersData[0], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
