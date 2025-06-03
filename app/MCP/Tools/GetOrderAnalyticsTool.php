<?php

namespace App\MCP\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GetOrderAnalyticsTool implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function name(): string
    {
        return 'get_order_analytics';
    }

    public function description(): string
    {
        return '獲取訂單分析資料，包括按日期、狀態、產品的統計分析';
    }    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'analytics_type' => [
                    'type' => 'string',
                    'enum' => ['daily', 'status', 'product', 'monthly'],
                    'description' => '分析類型：daily（按日統計）、status（按狀態統計）、product（按產品統計）、monthly（按月統計）- Optional field (default: daily)',
                    'default' => 'daily',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '分析開始日期 (YYYY-MM-DD) - Optional field',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => '分析結束日期 (YYYY-MM-DD) - Optional field',
                ],                'status' => [
                    'type' => 'string',
                    'description' => '篩選特定訂單狀態（pending, completed, cancelled, all）- Optional field. Use "all" to include all statuses',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => '返回結果數量限制 - Optional field (default: 30, range: 1-100)',
                    'default' => 30,
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
            'analytics_type' => ['nullable', 'string', 'in:daily,status,product,monthly'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: $validator->errors()->toJson(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }        try {
            $analyticsType = $arguments['analytics_type'] ?? 'daily';
            $limit = $arguments['limit'] ?? 30;
            
            \Log::info('GetOrderAnalyticsTool arguments:', $arguments);
            \Log::info('GetOrderAnalyticsTool status value type: ' . gettype($arguments['status'] ?? 'not set'));
            \Log::info('GetOrderAnalyticsTool status value: ', ['status' => $arguments['status'] ?? 'not set']);

            $baseQuery = Order::query();

            // Apply date filters
            if (!empty($arguments['date_from'])) {
                $baseQuery->whereDate('created_at', '>=', $arguments['date_from']);
                \Log::info('Applied date_from filter: ' . $arguments['date_from']);
            }            if (!empty($arguments['date_to'])) {
                $baseQuery->whereDate('created_at', '<=', $arguments['date_to']);
                \Log::info('Applied date_to filter: ' . $arguments['date_to']);
            }            if (isset($arguments['status']) && $arguments['status'] !== '' && $arguments['status'] !== null) {
                if ($arguments['status'] === 'all') {
                    // Don't apply any status filter when "all" is specified
                    \Log::info('Status filter set to "all" - no status filtering applied');
                } else {
                    $baseQuery->where('status', $arguments['status']);
                    \Log::info('Applied status filter: ' . $arguments['status']);
                }
            } elseif (isset($arguments['status']) && $arguments['status'] === '') {
                // Default to 'completed' when status is empty string
                $baseQuery->where('status', 'completed');
                \Log::info('Applied default status filter: completed (because status was empty string)');
            } else {
                \Log::info('Status filter not applied. Status value: ' . json_encode($arguments['status'] ?? 'not set'));
            }
            
            \Log::info('Base query before analytics - SQL: ' . $baseQuery->toSql());
            \Log::info('Base query before analytics - Bindings: ', $baseQuery->getBindings());

            $analytics = [];

            switch ($analyticsType) {
                case 'daily':
                    $analytics = $this->getDailyAnalytics($baseQuery, $limit);
                    break;
                case 'monthly':
                    $analytics = $this->getMonthlyAnalytics($baseQuery, $limit);
                    break;
                case 'status':
                    $analytics = $this->getStatusAnalytics($baseQuery);
                    break;
                case 'product':
                    $analytics = $this->getProductAnalytics($baseQuery, $limit);
                    break;
            }

            return [
                'success' => true,
                'analytics_type' => $analyticsType,
                'data' => $analytics,
            ];
        } catch (\Exception $e) {
            throw new JsonRpcErrorException(
                message: "Failed to retrieve order analytics: " . $e->getMessage(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }

    private function getDailyAnalytics($query, $limit): array
    {
        return $query->select([            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('AVG(amount) as average_order_value'),
            DB::raw('COUNT(DISTINCT name) as unique_customers')
        ])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($item) {
            return [
                'date' => $item->date,
                'order_count' => $item->order_count,
                'total_revenue' => round($item->total_revenue, 2),
                'average_order_value' => round($item->average_order_value, 2),
                'unique_customers' => $item->unique_customers,
            ];
        })
        ->toArray();
    }

    private function getMonthlyAnalytics($query, $limit): array
    {
        return $query->select([
            DB::raw('YEAR(created_at) as year'),            
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('AVG(amount) as average_order_value'),
            DB::raw('COUNT(DISTINCT name) as unique_customers')
        ])
        ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->limit($limit)
        ->get()
        ->map(function ($item) {
            return [
                'year' => $item->year,
                'month' => $item->month,
                'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                'order_count' => $item->order_count,
                'total_revenue' => round($item->total_revenue, 2),
                'average_order_value' => round($item->average_order_value, 2),
                'unique_customers' => $item->unique_customers,
            ];
        })
        ->toArray();
    }

    private function getStatusAnalytics($query): array
    {        return $query->select([
            'status',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('AVG(amount) as average_order_value'),
            DB::raw('COUNT(DISTINCT name) as unique_customers')
        ])
        ->groupBy('status')
        ->orderBy('order_count', 'desc')
        ->get()
        ->map(function ($item) {
            return [
                'status' => $item->status,
                'order_count' => $item->order_count,
                'total_revenue' => round($item->total_revenue, 2),
                'average_order_value' => round($item->average_order_value, 2),
                'unique_customers' => $item->unique_customers,
            ];
        })
        ->toArray();
    }    private function getProductAnalytics($query, $limit): array
    {
        // Add debug logging to confirm status filter is applied
        \Log::info('GetProductAnalytics - Query SQL: ' . $query->toSql());
        \Log::info('GetProductAnalytics - Query bindings: ', $query->getBindings());
        
        return $query->with('product')
        ->select([
            'product_id',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('AVG(amount) as average_order_value')
        ])
        ->groupBy('product_id')
        ->orderBy('total_quantity', 'desc')  // Changed from total_revenue to total_quantity for "most sold products"
        ->limit($limit)
        ->get()
        ->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'order_count' => $item->order_count,
                'total_quantity' => $item->total_quantity,
                'total_revenue' => round($item->total_revenue, 2),
                'average_order_value' => round($item->average_order_value, 2),
            ];
        })
        ->toArray();
    }
}
