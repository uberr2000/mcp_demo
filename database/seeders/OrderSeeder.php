<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = \App\Models\Product::all();
        $statuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        $customerNames = [
            '陳大明', '李小芳', '王志強', '張美麗', '劉家豪',
            '黃詩雅', '林建華', '吳雅文', '鄭志明', '何淑儀',
            '梁偉強', '蔡美玲', '羅家輝', '馬詩琪', '徐志偉'
        ];

        for ($i = 1; $i <= 500; $i++) {
            $product = $products->random();
            $quantity = rand(1, 5);
            $amount = $product->price * $quantity;
            
            \App\Models\Order::create([
                'transaction_id' => 'TXN' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => $customerNames[array_rand($customerNames)],
                'amount' => $amount,
                'status' => $statuses[array_rand($statuses)],
                'product_id' => $product->id,
                'quantity' => $quantity,
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                'updated_at' => now()->subDays(rand(0, 5))->subHours(rand(0, 23))->subMinutes(rand(0, 59))
            ]);
        }
    }
}
