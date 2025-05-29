<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use PhpMcp\Server\Attributes\McpTool;

class GetOrderAnalyticsTool
{
    #[McpTool(
        name: 'get_order_analytics',
        description: '獲取訂單分析資料，包括按日期、狀態、產品的統計分析'
    )]
    public function getOrderAnalytics(
        ?string $date_from = null,
        ?string $date_to = null,
        ?string $group_by = 'date'
    ): string {
        $query = Order::with('product');

        if ($date_from) {
            $query->whereDate('created_at', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('created_at', '<=', $date_to);
        }

        $analytics = [];

        // Overall statistics
        $overallStats = $query->select([
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(total_amount) as total_revenue'),
            DB::raw('AVG(total_amount) as avg_order_value'),
            DB::raw('SUM(quantity) as total_items_sold')
        ])->first();

        $analytics['overall'] = [
            'total_orders' => $overallStats->total_orders,
            'total_revenue' => round($overallStats->total_revenue, 2),
            'avg_order_value' => round($overallStats->avg_order_value, 2),
            'total_items_sold' => $overallStats->total_items_sold,
        ];

        // Group by analysis
        switch ($group_by) {
            case 'date':
                $groupedData = $query->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('AVG(total_amount) as avg_order_value')
                ])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->get();
                
                $analytics['by_date'] = $groupedData->toArray();
                break;

            case 'status':
                $groupedData = $query->select([
                    'status',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('AVG(total_amount) as avg_order_value')
                ])
                ->groupBy('status')
                ->orderBy('order_count', 'desc')
                ->get();
                
                $analytics['by_status'] = $groupedData->toArray();
                break;

            case 'product':
                $groupedData = $query->join('products', 'orders.product_id', '=', 'products.id')
                ->select([
                    'products.name as product_name',
                    'products.category',
                    DB::raw('COUNT(orders.id) as order_count'),
                    DB::raw('SUM(orders.quantity) as total_quantity'),
                    DB::raw('SUM(orders.total_amount) as revenue'),
                    DB::raw('AVG(orders.total_amount) as avg_order_value')
                ])
                ->groupBy('products.id', 'products.name', 'products.category')
                ->orderBy('revenue', 'desc')
                ->get();
                
                $analytics['by_product'] = $groupedData->toArray();
                break;
        }

        // Top customers
        $topCustomers = $query->select([
            'customer_name',
            'customer_email',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(total_amount) as total_spent')
        ])
        ->groupBy('customer_name', 'customer_email')
        ->orderBy('total_spent', 'desc')
        ->limit(5)
        ->get();

        $analytics['top_customers'] = $topCustomers->toArray();

        return json_encode($analytics, JSON_UNESCAPED_UNICODE);
    }
}
