<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use PhpMcp\Server\Attributes\McpTool;

class GetCustomerStatsTool
{
    #[McpTool(
        name: 'get_customer_stats',
        description: '獲取客戶統計資訊，包括訂單數量、總消費金額、平均訂單金額等'
    )]
    public function getCustomerStats(?string $customer_name = null): array
    {
        $query = Order::query();

        if ($customer_name) {
            $query->where('customer_name', 'like', "%{$customer_name}%");
        }

        // Get customer statistics
        $stats = $query->select([
            'customer_name',
            'customer_email',
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(total_amount) as total_spent'),
            DB::raw('AVG(total_amount) as avg_order_amount'),
            DB::raw('MAX(total_amount) as highest_order'),
            DB::raw('MIN(total_amount) as lowest_order'),
            DB::raw('MAX(created_at) as last_order_date'),
            DB::raw('MIN(created_at) as first_order_date')
        ])
        ->groupBy('customer_name', 'customer_email')
        ->orderBy('total_spent', 'desc')
        ->limit(20)
        ->get();

        // Get status distribution for the customers
        $statusStats = $query->select([
            'customer_name',
            'status',
            DB::raw('COUNT(*) as count')
        ])
        ->groupBy('customer_name', 'status')
        ->get()
        ->groupBy('customer_name');        $result = [
            'total_customers' => $stats->count(),
            'customers' => $stats->map(function ($customer) use ($statusStats) {
                $customerStatusStats = $statusStats->get($customer->customer_name, collect());
                
                return [
                    'customer_name' => $customer->customer_name,
                    'customer_email' => $customer->customer_email,
                    'total_orders' => $customer->total_orders,
                    'total_spent' => round($customer->total_spent, 2),
                    'avg_order_amount' => round($customer->avg_order_amount, 2),
                    'highest_order' => round($customer->highest_order, 2),
                    'lowest_order' => round($customer->lowest_order, 2),
                    'first_order_date' => $customer->first_order_date,
                    'last_order_date' => $customer->last_order_date,
                    'order_status_breakdown' => $customerStatusStats->map(function ($statusGroup) {
                        return [
                            'status' => $statusGroup->first()->status,
                            'count' => $statusGroup->sum('count')
                        ];
                    })->values()->toArray()
                ];
            })->toArray()
        ];

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
