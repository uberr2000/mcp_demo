<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "=== Testing Customer Stats for 馬詩琪 ===\n";

// Test individual customer stats
$customerQuery = Order::select([
    'name',
    DB::raw('COUNT(*) as total_orders'),
    DB::raw('SUM(amount) as total_spent'),
    DB::raw('AVG(amount) as average_order_amount'),
])
->groupBy('name')
->where('name', 'like', "%馬詩琪%")
->whereDate('created_at', '>=', '2025-01-01')
->whereDate('created_at', '<=', '2025-12-31')
->where('status', 'completed');

echo "\nCustomer Query SQL: " . $customerQuery->toSql() . "\n";
echo "Customer Query Bindings: " . json_encode($customerQuery->getBindings()) . "\n";

$customerStats = $customerQuery->get();
echo "\nCustomer Stats Results:\n";
foreach ($customerStats as $customer) {
    echo "- {$customer->name}: {$customer->total_orders} orders, \${$customer->total_spent} total\n";
}

// Test overall stats with same filters
$overallQuery = Order::query()
    ->where('name', 'like', "%馬詩琪%")
    ->whereDate('created_at', '>=', '2025-01-01')
    ->whereDate('created_at', '<=', '2025-12-31')
    ->where('status', 'completed');

echo "\nOverall Query SQL: " . $overallQuery->toSql() . "\n";
echo "Overall Query Bindings: " . json_encode($overallQuery->getBindings()) . "\n";

$overallStats = $overallQuery->selectRaw('
    COUNT(DISTINCT name) as unique_customers,
    COUNT(*) as total_orders,
    SUM(amount) as total_revenue,
    AVG(amount) as average_order_value
')->first();

echo "\nOverall Stats Results (with 馬詩琪 filter):\n";
echo "- Unique customers: {$overallStats->unique_customers}\n";
echo "- Total orders: {$overallStats->total_orders}\n";
echo "- Total revenue: \${$overallStats->total_revenue}\n";
echo "- Average order value: \${$overallStats->average_order_value}\n";

// Test without filters to see all data
echo "\n=== For comparison - ALL customers stats ===\n";
$allStats = Order::selectRaw('
    COUNT(DISTINCT name) as unique_customers,
    COUNT(*) as total_orders,
    SUM(amount) as total_revenue,
    AVG(amount) as average_order_value
')->first();

echo "- All unique customers: {$allStats->unique_customers}\n";
echo "- All total orders: {$allStats->total_orders}\n";
echo "- All total revenue: \${$allStats->total_revenue}\n";
echo "- All average order value: \${$allStats->average_order_value}\n";

// Show 馬詩琪's actual completed orders
echo "\n=== 馬詩琪's completed orders in 2025 ===\n";
$orders = Order::where('name', 'like', "%馬詩琪%")
    ->whereDate('created_at', '>=', '2025-01-01')
    ->whereDate('created_at', '<=', '2025-12-31')
    ->where('status', 'completed')
    ->orderBy('created_at', 'desc')
    ->get(['id', 'amount', 'status', 'created_at']);

foreach ($orders as $order) {
    echo "- Order {$order->id}: \${$order->amount} | {$order->status} | {$order->created_at}\n";
}
