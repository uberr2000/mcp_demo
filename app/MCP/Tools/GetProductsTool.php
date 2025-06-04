<?php

namespace App\MCP\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GetProductsTool implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function name(): string
    {
        return 'get_products';
    }

    public function description(): string
    {
        return '從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢';
    }    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => '產品名稱（可部分匹配）- Optional field',
                ],
                'category' => [
                    'type' => 'string',
                    'description' => '產品類別 - Optional field',
                ],
                'min_price' => [
                    'type' => 'number',
                    'description' => '最小價格 - Optional field',
                ],
                'max_price' => [
                    'type' => 'number',
                    'description' => '最大價格 - Optional field',
                ],                'stock_quantity' => [
                    'type' => 'integer',
                    'description' => '庫存數量篩選 - Optional field',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => '返回結果數量限制 - Optional field (default: 10, range: 1-100)',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {        $validator = Validator::make($arguments, [
            'name' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: $validator->errors()->toJson(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }

        try {
            $query = Product::query();

            if (!empty($arguments['name'])) {
                $query->where('name', 'like', "%{$arguments['name']}%");
            }

            if (!empty($arguments['category'])) {
                $query->where('category', 'like', "%{$arguments['category']}%");
            }

            if (isset($arguments['min_price'])) {
                $query->where('price', '>=', $arguments['min_price']);
            }            if (isset($arguments['max_price'])) {
                $query->where('price', '<=', $arguments['max_price']);
            }

            if (isset($arguments['stock_quantity'])) {
                $query->where('stock_quantity', '>=', $arguments['stock_quantity']);
            }

            $limit = $arguments['limit'] ?? 10;
            $products = $query->orderBy('name')
                             ->limit($limit)
                             ->get();

            return [
                'success' => true,
                'total' => $products->count(),
                'products' => $products->map(function ($product) {                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'stock_quantity' => $product->stock_quantity,
                        'category' => $product->category,
                        'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $product->updated_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            throw new JsonRpcErrorException(
                message: "Failed to retrieve products: " . $e->getMessage(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }
}
