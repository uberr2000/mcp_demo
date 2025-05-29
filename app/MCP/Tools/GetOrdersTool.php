<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use PhpMcp\Server\Attributes\McpTool;

class GetOrdersTool
{
    #[McpTool(
        name: 'get_orders',
        description: '從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢'
    )]
    public function getOrders(
        ?string $transaction_id = null,
        ?string $customer_name = null,
        ?string $status = null,
        ?string $product_name = null,
        ?float $min_amount = null,
        ?float $max_amount = null,
        ?string $date_from = null,
        ?string $date_to = null,
        int $limit = 10
    ): array {
        $query = Order::with('product');

        if ($transaction_id) {
            $query->where('transaction_id', 'like', "%{$transaction_id}%");
        }

        if ($customer_name) {
            $query->where('customer_name', 'like', "%{$customer_name}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($product_name) {
            $query->whereHas('product', function ($q) use ($product_name) {
                $q->where('name', 'like', "%{$product_name}%");
            });
        }

        if ($min_amount !== null) {
            $query->where('total_amount', '>=', $min_amount);
        }

        if ($max_amount !== null) {
            $query->where('total_amount', '<=', $max_amount);
        }

        if ($date_from) {
            $query->whereDate('created_at', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('created_at', '<=', $date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')
                       ->limit($limit)
                       ->get();        $result = [
            'total' => $orders->count(),
            'orders' => $orders->map(function ($order) {
                return [
                    'transaction_id' => $order->transaction_id,
                    'customer_name' => $order->customer_name,
                    'customer_email' => $order->customer_email,
                    'product_name' => $order->product->name ?? 'Unknown',
                    'quantity' => $order->quantity,
                    'unit_price' => $order->unit_price,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray()
        ];

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
