<?php

namespace App\MCP\Tools;

use App\Contracts\MCPToolInterface;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderAnalyticsTool implements MCPToolInterface
{
    public function getName(): string
    {
        return 'get_order_analytics';
    }

    public function getDescription(): string
    {
        return '獲取訂單分析數據，包括銷售趨勢、狀態分佈、產品排行等';
    }

    public function getInputSchema(): array
    {
        return [
            'date_from' => [
                'type' => 'string',
                'description' => '開始日期 (YYYY-MM-DD)'
            ],
            'date_to' => [
                'type' => 'string',
                'description' => '結束日期 (YYYY-MM-DD)'
            ],
            'group_by' => [
                'type' => 'string',
                'enum' => ['day', 'week', 'month', 'status', 'product'],
                'description' => '分組方式',
                'default' => 'day'
            ]
        ];
    }

    public function execute(array $parameters): array
    {
        $query = Order::with('product');

        // 日期篩選
        if (!empty($parameters['date_from'])) {
            $query->whereDate('created_at', '>=', $parameters['date_from']);
        }

        if (!empty($parameters['date_to'])) {
            $query->whereDate('created_at', '<=', $parameters['date_to']);
        }

        $groupBy = $parameters['group_by'] ?? 'day';

        switch ($groupBy) {
            case 'day':
                return $this->getDailyAnalytics($query);
            case 'week':
                return $this->getWeeklyAnalytics($query);
            case 'month':
                return $this->getMonthlyAnalytics($query);
            case 'status':
                return $this->getStatusAnalytics($query);
            case 'product':
                return $this->getProductAnalytics($query);
            default:
                return $this->getDailyAnalytics($query);
        }
    }

    private function getDailyAnalytics($query): array
    {
        $orders = $query->get();
        
        $dailyStats = $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function ($dayOrders) {
            return [
                'order_count' => $dayOrders->count(),
                'total_amount' => $dayOrders->sum('amount'),
                'average_amount' => round($dayOrders->avg('amount'), 2),
                'total_quantity' => $dayOrders->sum('quantity')
            ];
        });

        return [
            'type' => 'daily_analytics',
            'period' => [
                'from' => $orders->min('created_at')?->format('Y-m-d'),
                'to' => $orders->max('created_at')?->format('Y-m-d')
            ],
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('amount'),
                'average_daily_orders' => round($orders->count() / max(1, $dailyStats->count()), 2),
                'average_daily_amount' => round($orders->sum('amount') / max(1, $dailyStats->count()), 2)
            ],
            'daily_breakdown' => $dailyStats->toArray()
        ];
    }

    private function getWeeklyAnalytics($query): array
    {
        $orders = $query->get();
        
        $weeklyStats = $orders->groupBy(function ($order) {
            return $order->created_at->startOfWeek()->format('Y-m-d');
        })->map(function ($weekOrders, $weekStart) {
            return [
                'week_start' => $weekStart,
                'order_count' => $weekOrders->count(),
                'total_amount' => $weekOrders->sum('amount'),
                'average_amount' => round($weekOrders->avg('amount'), 2),
                'total_quantity' => $weekOrders->sum('quantity')
            ];
        });

        return [
            'type' => 'weekly_analytics',
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('amount'),
                'average_weekly_orders' => round($orders->count() / max(1, $weeklyStats->count()), 2)
            ],
            'weekly_breakdown' => $weeklyStats->values()->toArray()
        ];
    }

    private function getMonthlyAnalytics($query): array
    {
        $orders = $query->get();
        
        $monthlyStats = $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m');
        })->map(function ($monthOrders, $month) {
            return [
                'month' => $month,
                'order_count' => $monthOrders->count(),
                'total_amount' => $monthOrders->sum('amount'),
                'average_amount' => round($monthOrders->avg('amount'), 2),
                'total_quantity' => $monthOrders->sum('quantity')
            ];
        });

        return [
            'type' => 'monthly_analytics',
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('amount'),
                'average_monthly_orders' => round($orders->count() / max(1, $monthlyStats->count()), 2)
            ],
            'monthly_breakdown' => $monthlyStats->toArray()
        ];
    }

    private function getStatusAnalytics($query): array
    {
        $orders = $query->get();
        
        $statusStats = $orders->groupBy('status')->map(function ($statusOrders, $status) {
            return [
                'status' => $status,
                'order_count' => $statusOrders->count(),
                'total_amount' => $statusOrders->sum('amount'),
                'percentage' => round(($statusOrders->count() / $orders->count()) * 100, 2)
            ];
        });

        return [
            'type' => 'status_analytics',
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('amount')
            ],
            'status_breakdown' => $statusStats->values()->toArray()
        ];
    }

    private function getProductAnalytics($query): array
    {
        $orders = $query->get();
        
        $productStats = $orders->groupBy('product.name')->map(function ($productOrders, $productName) {
            $product = $productOrders->first()->product;
            return [
                'product_name' => $productName,
                'product_category' => $product->category,
                'order_count' => $productOrders->count(),
                'total_quantity' => $productOrders->sum('quantity'),
                'total_amount' => $productOrders->sum('amount'),
                'average_amount' => round($productOrders->avg('amount'), 2)
            ];
        })->sortByDesc('order_count');

        return [
            'type' => 'product_analytics',
            'summary' => [
                'total_orders' => $orders->count(),
                'total_amount' => $orders->sum('amount'),
                'unique_products' => $productStats->count()
            ],
            'product_breakdown' => $productStats->values()->toArray()
        ];
    }
}
