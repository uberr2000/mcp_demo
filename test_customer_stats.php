<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "=== Raw Order Data ===\n";
$orders = Order::select('id', 'name', 'amount', 'status', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($orders as $order) {
    echo sprintf("ID: %d | Customer: %s | Amount: $%.2f | Status: %s | Date: %s\n", 
        $order->id, $order->name, $order->amount, $order->status, $order->created_at);
}

echo "\n=== Customer Stats Query ===\n";
$customerStats = Order::select([
    'name',
    DB::raw('COUNT(*) as total_orders'),
    DB::raw('SUM(amount) as total_spent'),
    DB::raw('AVG(amount) as average_order_amount'),
])
->groupBy('name')
->orderBy('total_spent', 'desc')
->get();

foreach ($customerStats as $stat) {
    echo sprintf("Customer: %s | Orders: %d | Total: $%.2f | Avg: $%.2f\n",
        $stat->name, $stat->total_orders, $stat->total_spent, $stat->average_order_amount);
}

echo "\n=== Overall Stats ===\n";
$overallStats = Order::selectRaw('
    COUNT(DISTINCT name) as unique_customers,
    COUNT(*) as total_orders,
    SUM(amount) as total_revenue,
    AVG(amount) as average_order_value
')->first();

echo sprintf("Unique Customers: %d | Total Orders: %d | Total Revenue: $%.2f | Avg Order: $%.2f\n",
    $overallStats->unique_customers, $overallStats->total_orders, 
    $overallStats->total_revenue, $overallStats->average_order_value);
