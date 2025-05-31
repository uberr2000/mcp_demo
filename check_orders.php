<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

echo "Checking orders in database:\n";
echo "Total orders: " . Order::count() . "\n";

if (Order::count() > 0) {
    echo "\nFirst 5 orders:\n";
    $orders = Order::select('id', 'transaction_id', 'name', 'amount', 'created_at')
                   ->orderBy('created_at', 'desc')
                   ->take(5)
                   ->get();
      foreach ($orders as $order) {
        echo "ID: {$order->id}, TxID: {$order->transaction_id}, Customer: {$order->name}, Amount: $" . $order->amount . ", Date: {$order->created_at}\n";
    }
    
    echo "\nDate range:\n";
    $earliest = Order::orderBy('created_at')->first();
    $latest = Order::orderBy('created_at', 'desc')->first();
    echo "Earliest: {$earliest->created_at}\n";
    echo "Latest: {$latest->created_at}\n";
}
