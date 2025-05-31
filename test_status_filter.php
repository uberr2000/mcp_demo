<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "=== Testing status filtering in product analytics ===\n";

// Test without status filter
$allQuery = Order::query();
$allCount = $allQuery->count();
echo "Total orders: $allCount\n";

// Test with completed status filter
$completedQuery = Order::where('status', 'completed');
$completedCount = $completedQuery->count();
echo "Completed orders: $completedCount\n";

// Test with pending status filter
$pendingQuery = Order::where('status', 'pending');
$pendingCount = $pendingQuery->count();
echo "Pending orders: $pendingCount\n";

// Test the product analytics with completed status filter
$productAnalyticsCompleted = Order::where('status', 'completed')
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'),
        DB::raw('SUM(amount) as total_revenue')
    ])
    ->groupBy('product_id')
    ->orderBy('total_quantity', 'desc')
    ->limit(1)
    ->get();

echo "\nTop product with COMPLETED status:\n";
foreach ($productAnalyticsCompleted as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: $" . round($item->total_revenue, 2) . "\n";
}

// Test the product analytics with pending status filter
$productAnalyticsPending = Order::where('status', 'pending')
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'),
        DB::raw('SUM(amount) as total_revenue')
    ])
    ->groupBy('product_id')
    ->orderBy('total_quantity', 'desc')
    ->limit(1)
    ->get();

echo "\nTop product with PENDING status:\n";
foreach ($productAnalyticsPending as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: $" . round($item->total_revenue, 2) . "\n";
}

// Test all products (no status filter)
$productAnalyticsAll = Order::query()
    ->with('product')
    ->select([
        'product_id',
        DB::raw('COUNT(*) as order_count'),
        DB::raw('SUM(quantity) as total_quantity'),
        DB::raw('SUM(amount) as total_revenue')
    ])
    ->groupBy('product_id')
    ->orderBy('total_quantity', 'desc')
    ->limit(1)
    ->get();

echo "\nTop product with NO status filter:\n";
foreach ($productAnalyticsAll as $item) {
    echo "Product ID: " . $item->product_id . "\n";
    echo "Product Name: " . ($item->product->name ?? 'Unknown') . "\n";
    echo "Order Count: " . $item->order_count . "\n";
    echo "Total Quantity: " . $item->total_quantity . "\n";
    echo "Total Revenue: $" . round($item->total_revenue, 2) . "\n";
}
