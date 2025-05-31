<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "=== 測試產品分析查詢 ===\n";

// 測試基本查詢 - 所有completed訂單
$baseQuery = Order::where('status', 'completed');
echo "Completed orders count: " . $baseQuery->count() . "\n";

// 測試產品分析查詢 - 按收入排序
$productAnalytics = Order::where('status', 'completed')
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'), 
        DB::raw('SUM(amount) as total_revenue'),
        DB::raw('AVG(amount) as average_order_value')
    ])
    ->groupBy('product_id')
    ->orderBy('total_revenue', 'desc')
    ->limit(3)
    ->get();

echo "\n=== 按收入排序的前3產品 ===\n";
foreach ($productAnalytics as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: " . $item->total_revenue . "\n";
    echo "Average Order Value: " . round($item->average_order_value, 2) . "\n";
    echo "------------------------\n";
}

// 測試按數量排序
$productAnalyticsByQty = Order::where('status', 'completed')
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'), 
        DB::raw('SUM(amount) as total_revenue'),
        DB::raw('AVG(amount) as average_order_value')
    ])
    ->groupBy('product_id')
    ->orderBy('total_quantity', 'desc')
    ->limit(3)
    ->get();

echo "\n=== 按銷售數量排序的前3產品 ===\n";
foreach ($productAnalyticsByQty as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: " . $item->total_revenue . "\n";
    echo "Average Order Value: " . round($item->average_order_value, 2) . "\n";
    echo "------------------------\n";
}

// 測試按訂單數量排序
$productAnalyticsByOrderCount = Order::where('status', 'completed')
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'), 
        DB::raw('SUM(amount) as total_revenue'),
        DB::raw('AVG(amount) as average_order_value')
    ])
    ->groupBy('product_id')
    ->orderBy('order_count', 'desc')
    ->limit(3)
    ->get();

echo "\n=== 按訂單數量排序的前3產品 ===\n";
foreach ($productAnalyticsByOrderCount as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: " . $item->total_revenue . "\n";
    echo "Average Order Value: " . round($item->average_order_value, 2) . "\n";
    echo "------------------------\n";
}
