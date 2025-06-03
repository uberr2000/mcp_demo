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
    }    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'transaction_id' => [
                    'type' => 'string',
                    'description' => '交易ID（可部分匹配）- Optional field',
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => '客戶姓名（可部分匹配）- Optional field',
                ],                'status' => [
                    'type' => 'string',
                    'description' => '訂單狀態（pending, completed, cancelled, all）- Optional field. Use "all" to include all statuses',
                ],
                'product_name' => [
                    'type' => 'string',
                    'description' => '產品名稱（可部分匹配）- Optional field',
                ],
                'min_amount' => [
                    'type' => 'number',
                    'description' => '最小金額 - Optional field (use 0 to ignore this filter)',
                ],
                'max_amount' => [
                    'type' => 'number',
                    'description' => '最大金額 - Optional field (use 0 to ignore this filter)',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '開始日期 (YYYY-MM-DD) - Optional field',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '結束日期 (YYYY-MM-DD) - Optional field',
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
    {
        $validator = Validator::make($arguments, [
            'transaction_id' => ['nullable', 'string'],
            'customer_name' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled,all'],
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
            );        }        try {
            \Log::info('GetOrdersTool arguments:', $arguments);
            
            $query = Order::with('product');

            if (!empty($arguments['transaction_id'])) {
                $query->where('transaction_id', 'like', "%{$arguments['transaction_id']}%");
                \Log::info('Added transaction_id filter: ' . $arguments['transaction_id']);
            }            if (!empty($arguments['customer_name'])) {
                $query->where('name', 'like', "%{$arguments['customer_name']}%");
                \Log::info('Added customer_name filter: ' . $arguments['customer_name']);
            }            if (!empty($arguments['status'])) {
                if ($arguments['status'] === 'all') {
                    // Don't apply any status filter when "all" is specified
                    \Log::info('Status filter set to "all" - no status filtering applied');
                } else {
                    $query->where('status', $arguments['status']);
                    \Log::info('Added status filter: ' . $arguments['status']);
                }
            }

            if (!empty($arguments['product_name'])) {
                $query->whereHas('product', function ($q) use ($arguments) {
                    $q->where('name', 'like', "%{$arguments['product_name']}%");
                });
                \Log::info('Added product_name filter: ' . $arguments['product_name']);
            }
            
            // Fix the amount filtering logic - only apply if value is greater than 0
            if (isset($arguments['min_amount']) && $arguments['min_amount'] > 0) {
                $query->where('amount', '>=', $arguments['min_amount']);
                \Log::info('Added min_amount filter: ' . $arguments['min_amount']);
            }

            if (isset($arguments['max_amount']) && $arguments['max_amount'] > 0) {
                $query->where('amount', '<=', $arguments['max_amount']);
                \Log::info('Added max_amount filter: ' . $arguments['max_amount']);
            }

            if (!empty($arguments['date_from'])) {
                $query->whereDate('created_at', '>=', $arguments['date_from']);
                \Log::info('Added date_from filter: ' . $arguments['date_from']);
            }

            if (!empty($arguments['date_to'])) {
                $query->whereDate('created_at', '<=', $arguments['date_to']);
                \Log::info('Added date_to filter: ' . $arguments['date_to']);
            }            $limit = $arguments['limit'] ?? 10;
            
            if (empty($arguments['date_from']) && empty($arguments['date_to'])) {
                // 如果沒有指定日期，默認查詢最近30天
                $arguments['date_from'] = now()->subDays(30)->format('Y-m-d');
                $arguments['date_to'] = now()->format('Y-m-d');
            }

            \Log::info('Final query SQL: ' . $query->toSql());
            \Log::info('Query bindings: ', $query->getBindings());
            
            $orders = $query->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();

            \Log::info('Orders found: ' . $orders->count());

            return [
                'success' => true,
                'total' => $orders->count(),                
                'orders' => $orders->map(function ($order) {
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
