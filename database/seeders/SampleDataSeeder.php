<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run()
    {
        // Create sample products
        $laptop = Product::firstOrCreate([
            'name' => 'Laptop'
        ], [
            'description' => 'High-performance laptop',
            'price' => 999.99,
            'stock_quantity' => 10,
            'category' => 'electronics'
        ]);

        $mouse = Product::firstOrCreate([
            'name' => 'Wireless Mouse'
        ], [
            'description' => 'Ergonomic wireless mouse',
            'price' => 29.99,
            'stock_quantity' => 50,
            'category' => 'accessories'
        ]);

        $keyboard = Product::firstOrCreate([
            'name' => 'Mechanical Keyboard'
        ], [
            'description' => 'RGB mechanical keyboard',
            'price' => 149.99,
            'stock_quantity' => 25,
            'category' => 'accessories'
        ]);

        // Create sample orders
        Order::firstOrCreate([
            'transaction_id' => 'TXN001'
        ], [
            'name' => 'John Doe',
            'amount' => 999.99,
            'status' => 'completed',
            'product_id' => $laptop->id,
            'quantity' => 1
        ]);

        Order::firstOrCreate([
            'transaction_id' => 'TXN002'
        ], [
            'name' => 'Jane Smith',
            'amount' => 29.99,
            'status' => 'pending',
            'product_id' => $mouse->id,
            'quantity' => 1
        ]);

        Order::firstOrCreate([
            'transaction_id' => 'TXN003'
        ], [
            'name' => 'John Doe',
            'amount' => 59.98,
            'status' => 'completed',
            'product_id' => $mouse->id,
            'quantity' => 2
        ]);

        Order::firstOrCreate([
            'transaction_id' => 'TXN004'
        ], [
            'name' => 'Alice Johnson',
            'amount' => 149.99,
            'status' => 'processing',
            'product_id' => $keyboard->id,
            'quantity' => 1
        ]);

        Order::firstOrCreate([
            'transaction_id' => 'TXN005'
        ], [
            'name' => 'Bob Wilson',
            'amount' => 299.97,
            'status' => 'completed',
            'product_id' => $keyboard->id,
            'quantity' => 2
        ]);

        Order::firstOrCreate([
            'transaction_id' => 'TXN006'
        ], [
            'name' => 'Jane Smith',
            'amount' => 149.99,
            'status' => 'cancelled',
            'product_id' => $keyboard->id,
            'quantity' => 1
        ]);

        $this->command->info('Sample data created successfully!');
    }
}
