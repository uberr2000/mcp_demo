<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => '可口可樂',
                'description' => '經典汽水飲料',
                'price' => 15.50,
                'stock_quantity' => 100,
                'category' => '飲料'
            ],
            [
                'name' => '樂事薯片',
                'description' => '原味薯片',
                'price' => 12.80,
                'stock_quantity' => 80,
                'category' => '零食'
            ],
            [
                'name' => '哈根達斯雪糕',
                'description' => '雲呢拿味雪糕',
                'price' => 45.00,
                'stock_quantity' => 30,
                'category' => '雪糕'
            ],
            [
                'name' => '百事可樂',
                'description' => '百事汽水',
                'price' => 14.90,
                'stock_quantity' => 120,
                'category' => '飲料'
            ],
            [
                'name' => '品客薯片',
                'description' => '酸忌廉洋蔥味',
                'price' => 18.50,
                'stock_quantity' => 60,
                'category' => '零食'
            ],
            [
                'name' => '明治雪糕',
                'description' => '朱古力味雪糕',
                'price' => 25.00,
                'stock_quantity' => 40,
                'category' => '雪糕'
            ],
            [
                'name' => '芬達橙汁',
                'description' => '橙味汽水',
                'price' => 13.50,
                'stock_quantity' => 90,
                'category' => '飲料'
            ],
            [
                'name' => '奇多芝士條',
                'description' => '芝士味玉米條',
                'price' => 16.80,
                'stock_quantity' => 70,
                'category' => '零食'
            ],
            [
                'name' => '和路雪雪糕',
                'description' => '士多啤梨味雪糕',
                'price' => 22.50,
                'stock_quantity' => 35,
                'category' => '雪糕'
            ],
            [
                'name' => '雪碧',
                'description' => '檸檬汽水',
                'price' => 14.50,
                'stock_quantity' => 110,
                'category' => '飲料'
            ]
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
