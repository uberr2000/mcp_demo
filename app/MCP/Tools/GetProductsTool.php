<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use PhpMcp\Server\Attributes\McpTool;

class GetProductsTool
{
    #[McpTool(
        name: 'get_products',
        description: '從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢'
    )]
    public function getProducts(
        ?string $name = null,
        ?string $category = null,
        ?float $min_price = null,
        ?float $max_price = null,
        ?bool $in_stock = null,
        int $limit = 10
    ): array {
        $query = Product::query();

        if ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($category) {
            $query->where('category', 'like', "%{$category}%");
        }

        if ($min_price !== null) {
            $query->where('price', '>=', $min_price);
        }

        if ($max_price !== null) {
            $query->where('price', '<=', $max_price);
        }

        if ($in_stock !== null) {
            if ($in_stock) {
                $query->where('stock_quantity', '>', 0);
            } else {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        $products = $query->orderBy('name')
                         ->limit($limit)
                         ->get();        $result = [
            'total' => $products->count(),
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'in_stock' => $product->stock_quantity > 0,
                    'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        ];

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
