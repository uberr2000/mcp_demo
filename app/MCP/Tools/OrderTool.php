<?php

namespace App\MCP\Tools;

use App\Contracts\MCPToolInterface;
use App\Models\Order;

class OrderTool implements MCPToolInterface
{
    public function getName(): string
    {
        return 'get_orders';
    }

    public function getDescription(): string
    {
        return '從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢';
    }    public function getInputSchema(): array
    {
        return [
            'transaction_id' => [
                'type' => 'string',
                'description' => '交易編號，例如: TXN000001'
            ],
            'customer_name' => [
                'type' => 'string',
                'description' => '客戶姓名，例如: 陳大明'
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['pending', 'processing', 'completed', 'cancelled', 'refunded'],
                'description' => '訂單狀態'
            ],
            'product_name' => [
                'type' => 'string',
                'description' => '產品名稱，例如: 可口可樂'
            ],
            'min_amount' => [
                'type' => 'number',
                'description' => '最小訂單金額'
            ],
            'max_amount' => [
                'type' => 'number',
                'description' => '最大訂單金額'
            ],
            'date_from' => [
                'type' => 'string',
                'description' => '開始日期 (YYYY-MM-DD)'
            ],
            'date_to' => [
                'type' => 'string',
                'description' => '結束日期 (YYYY-MM-DD)'
            ],
            'limit' => [
                'type' => 'integer',
                'description' => '返回結果數量限制，預設為10',
                'default' => 10
            ],
            'sort_by' => [
                'type' => 'string',
                'enum' => ['created_at', 'amount', 'transaction_id'],
                'description' => '排序欄位',
                'default' => 'created_at'
            ],
            'sort_direction' => [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
                'description' => '排序方向',
                'default' => 'desc'
            ]
        ];
    }    public function execute(array $parameters): array
    {
        $query = Order::with('product');

        // 根據參數篩選
        if (!empty($parameters['transaction_id'])) {
            $query->where('transaction_id', $parameters['transaction_id']);
        }

        if (!empty($parameters['customer_name'])) {
            $query->where('name', 'like', '%' . $parameters['customer_name'] . '%');
        }

        if (!empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }

        if (!empty($parameters['product_name'])) {
            $query->whereHas('product', function ($q) use ($parameters) {
                $q->where('name', 'like', '%' . $parameters['product_name'] . '%');
            });
        }

        if (!empty($parameters['min_amount'])) {
            $query->where('amount', '>=', $parameters['min_amount']);
        }

        if (!empty($parameters['max_amount'])) {
            $query->where('amount', '<=', $parameters['max_amount']);
        }

        if (!empty($parameters['date_from'])) {
            $query->whereDate('created_at', '>=', $parameters['date_from']);
        }

        if (!empty($parameters['date_to'])) {
            $query->whereDate('created_at', '<=', $parameters['date_to']);
        }

        // 排序
        $sortBy = $parameters['sort_by'] ?? 'created_at';
        $sortDirection = $parameters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $limit = $parameters['limit'] ?? 10;
        $orders = $query->limit($limit)->get();

        // 計算總數 (不受 limit 限制)
        $totalQuery = Order::with('product');
        if (!empty($parameters['transaction_id'])) {
            $totalQuery->where('transaction_id', $parameters['transaction_id']);
        }
        if (!empty($parameters['customer_name'])) {
            $totalQuery->where('name', 'like', '%' . $parameters['customer_name'] . '%');
        }
        if (!empty($parameters['status'])) {
            $totalQuery->where('status', $parameters['status']);
        }
        if (!empty($parameters['product_name'])) {
            $totalQuery->whereHas('product', function ($q) use ($parameters) {
                $q->where('name', 'like', '%' . $parameters['product_name'] . '%');
            });
        }
        if (!empty($parameters['min_amount'])) {
            $totalQuery->where('amount', '>=', $parameters['min_amount']);
        }
        if (!empty($parameters['max_amount'])) {
            $totalQuery->where('amount', '<=', $parameters['max_amount']);
        }
        if (!empty($parameters['date_from'])) {
            $totalQuery->whereDate('created_at', '>=', $parameters['date_from']);
        }
        if (!empty($parameters['date_to'])) {
            $totalQuery->whereDate('created_at', '<=', $parameters['date_to']);
        }
        $totalCount = $totalQuery->count();

        return [
            'total_found' => $totalCount,
            'returned_count' => $orders->count(),
            'limit' => $limit,
            'query_parameters' => $parameters,
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'customer_name' => $order->name,
                    'amount' => $order->amount,
                    'status' => $order->status,
                    'quantity' => $order->quantity,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    'product' => [
                        'id' => $order->product->id,
                        'name' => $order->product->name,
                        'description' => $order->product->description,
                        'price' => $order->product->price,
                        'category' => $order->product->category
                    ]
                ];
            })->toArray()
        ];
    }
}
