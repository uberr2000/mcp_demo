<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

echo "Testing the problematic query parameters:\n";

$arguments = [
    'transaction_id' => '',
    'customer_name' => '',
    'status' => '',
    'product_name' => '',
    'min_amount' => 0,
    'max_amount' => 0,
    'date_from' => '2025-05-27',
    'date_to' => '2025-05-30',
    'limit' => 3
];

echo "Query parameters:\n";
print_r($arguments);

$query = Order::with('product');

// Apply the same logic as in GetOrdersTool
if (isset($arguments['min_amount']) && $arguments['min_amount'] > 0) {
    $query->where('amount', '>=', $arguments['min_amount']);
    echo "Added min_amount filter: " . $arguments['min_amount'] . "\n";
}

if (isset($arguments['max_amount']) && $arguments['max_amount'] > 0) {
    $query->where('amount', '<=', $arguments['max_amount']);
    echo "Added max_amount filter: " . $arguments['max_amount'] . "\n";
}

if (!empty($arguments['date_from'])) {
    $query->whereDate('created_at', '>=', $arguments['date_from']);
    echo "Added date_from filter: " . $arguments['date_from'] . "\n";
}

if (!empty($arguments['date_to'])) {
    $query->whereDate('created_at', '<=', $arguments['date_to']);
    echo "Added date_to filter: " . $arguments['date_to'] . "\n";
}

echo "\nSQL Query: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";

$orders = $query->orderBy('created_at', 'desc')->limit(3)->get();

echo "\nOrders found: " . $orders->count() . "\n";

if ($orders->count() > 0) {
    foreach ($orders as $order) {
        echo "ID: {$order->id}, Customer: {$order->name}, Amount: $" . $order->amount . ", Date: {$order->created_at}\n";
    }
}
