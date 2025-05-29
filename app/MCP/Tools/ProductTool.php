<?php

namespace App\MCP\Tools;

use App\Contracts\MCPToolInterface;
use App\Models\Product;

class ProductTool implements MCPToolInterface
{
    public function getName(): string
    {
        return 'get_products';
    }

    public function getDescription(): string
    {
        return '從資料庫獲取產品資訊，可以根據產品名稱、類別進行查詢';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => '產品名稱，例如: 可口可樂'
                ],
                'category' => [
                    'type' => 'string',
                    'description' => '產品類別，例如: 飲料、零食、雪糕'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => '返回結果數量限制，預設為10',
                    'default' => 10
                ]
            ]
        ];
    }

    public function execute(array $parameters): array
    {
        $query = Product::query();

        // 根據參數篩選
        if (!empty($parameters['name'])) {
            $query->where('name', 'like', '%' . $parameters['name'] . '%');
        }

        if (!empty($parameters['category'])) {
            $query->where('category', 'like', '%' . $parameters['category'] . '%');
        }

        $limit = $parameters['limit'] ?? 10;
        $products = $query->limit($limit)->get();

        return [
            'total' => $products->count(),
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'category' => $product->category,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $product->updated_at->format('Y-m-d H:i:s')
                ];
            })->toArray()
        ];
    }
}
