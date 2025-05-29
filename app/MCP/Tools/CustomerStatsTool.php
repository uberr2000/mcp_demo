<?php

namespace App\MCP\Tools;

use App\Contracts\MCPToolInterface;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CustomerStatsTool implements MCPToolInterface
{
    public function getName(): string
    {
        return 'get_customer_stats';
    }

    public function getDescription(): string
    {
        return '獲取客戶訂單統計資訊，包括訂單數量、總金額、平均訂單價值等';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => '客戶姓名，例如: 陳大明'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['pending', 'processing', 'completed', 'cancelled', 'refunded'],
                    'description' => '訂單狀態篩選'
                ]
            ]
        ];
    }

    public function execute(array $parameters): array
    {
        $query = Order::with('product');

        if (!empty($parameters['customer_name'])) {
            $query->where('name', 'like', '%' . $parameters['customer_name'] . '%');
        }

        if (!empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            return [
                'message' => '找不到符合條件的訂單',
                'total_orders' => 0,
                'total_amount' => 0,
                'average_order_value' => 0
            ];
        }

        // 統計資料
        $totalOrders = $orders->count();
        $totalAmount = $orders->sum('amount');
        $averageOrderValue = $totalAmount / $totalOrders;

        // 按狀態分組
        $statusStats = $orders->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('amount')
            ];
        });

        // 最受歡迎的產品
        $productStats = $orders->groupBy('product.name')->map(function ($group) {
            return [
                'product_name' => $group->first()->product->name,
                'order_count' => $group->count(),
                'total_quantity' => $group->sum('quantity'),
                'total_amount' => $group->sum('amount')
            ];
        })->sortByDesc('order_count')->take(5);

        return [
            'customer_name' => $parameters['customer_name'] ?? '所有客戶',
            'total_orders' => $totalOrders,
            'total_amount' => round($totalAmount, 2),
            'average_order_value' => round($averageOrderValue, 2),
            'status_breakdown' => $statusStats->toArray(),
            'top_products' => $productStats->values()->toArray(),
            'date_range' => [
                'earliest_order' => $orders->min('created_at'),
                'latest_order' => $orders->max('created_at')
            ]
        ];
    }
}
