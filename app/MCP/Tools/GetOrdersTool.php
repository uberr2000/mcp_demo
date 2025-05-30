<?php

namespace App\MCP\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GetOrdersTool implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function name(): string
    {
        return 'get_orders';
    }

    public function description(): string
    {
        return '從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'transaction_id' => [
                    'type' => 'string',
                    'description' => '交易ID（可部分匹配）',
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => '客戶姓名（可部分匹配）',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => '訂單狀態（pending, completed, cancelled）',
                ],
                'product_name' => [
                    'type' => 'string',
                    'description' => '產品名稱（可部分匹配）',
                ],
                'min_amount' => [
                    'type' => 'number',
                    'description' => '最小金額',
                ],
                'max_amount' => [
                    'type' => 'number',
                    'description' => '最大金額',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '開始日期 (YYYY-MM-DD)',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '結束日期 (YYYY-MM-DD)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => '返回結果數量限制',
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
    {
        $validator = Validator::make($arguments, [
            'transaction_id' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled'],
            'product_name' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: $validator->errors()->toJson(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }

        try {
            $query = Order::with('product');

            if (!empty($arguments['transaction_id'])) {
                $query->where('transaction_id', 'like', "%{$arguments['transaction_id']}%");
            }            if (!empty($arguments['customer_name'])) {
                $query->where('name', 'like', "%{$arguments['customer_name']}%");
            }

            if (!empty($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            if (!empty($arguments['product_name'])) {
                $query->whereHas('product', function ($q) use ($arguments) {
                    $q->where('name', 'like', "%{$arguments['product_name']}%");
                });
            }            if (isset($arguments['min_amount'])) {
                $query->where('amount', '>=', $arguments['min_amount']);
            }

            if (isset($arguments['max_amount'])) {
                $query->where('amount', '<=', $arguments['max_amount']);
            }

            if (!empty($arguments['date_from'])) {
                $query->whereDate('created_at', '>=', $arguments['date_from']);
            }

            if (!empty($arguments['date_to'])) {
                $query->whereDate('created_at', '<=', $arguments['date_to']);
            }

            $limit = $arguments['limit'] ?? 10;
            $orders = $query->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();

            return [
                'success' => true,
                'total' => $orders->count(),                'orders' => $orders->map(function ($order) {
                    return [
                        'transaction_id' => $order->transaction_id,
                        'customer_name' => $order->name,
                        'product_name' => $order->product->name ?? 'Unknown',
                        'quantity' => $order->quantity,
                        'amount' => $order->amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            throw new JsonRpcErrorException(
                message: "Failed to retrieve orders: " . $e->getMessage(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }
}
