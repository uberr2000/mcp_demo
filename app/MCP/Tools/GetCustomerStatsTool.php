<?php

namespace App\MCP\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GetCustomerStatsTool implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function name(): string
    {
        return 'get_customer_stats';
    }    public function description(): string
    {
        return 'Get customer statistics including order count, total spending, average order amount, etc.';
    }    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer name (partial match supported) - Optional field',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Statistics start date (YYYY-MM-DD) - Optional field',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Statistics end date (YYYY-MM-DD) - Optional field',
                ],                'status' => [
                    'type' => 'string',
                    'description' => 'Order status filter (pending, processing, completed, cancelled, refunded, all) - Optional field. Use "all" to include all statuses',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limit number of customers returned - Optional field (default: 20, range: 1-100)',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
        ];
    }

    public function annotations(): array
    {
        return [];
    }    public function execute(array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'customer_name' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'in:pending,processing,completed,cancelled,refunded,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: $validator->errors()->toJson(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }        try {
            \Log::info('GetCustomerStatsTool arguments:', $arguments);
            
            $query = Order::select([
                'name',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(amount) as total_spent'),
                DB::raw('AVG(amount) as average_order_amount'),
                DB::raw('MAX(created_at) as last_order_date'),
                DB::raw('MIN(created_at) as first_order_date')
            ])
                ->groupBy('name');

            if (!empty($arguments['customer_name'])) {
                $query->where('name', 'like', "%{$arguments['customer_name']}%");
            }

            if (!empty($arguments['date_from'])) {
                $query->whereDate('created_at', '>=', $arguments['date_from']);
            }

            if (!empty($arguments['date_to'])) {
                $query->whereDate('created_at', '<=', $arguments['date_to']);
            }            if (!empty($arguments['status'])) {
                if ($arguments['status'] === 'all') {
                    // Don't apply any status filter when "all" is specified
                    \Log::info('Status filter set to "all" - no status filtering applied');
                } else {
                    $query->where('status', $arguments['status']);
                    \Log::info('Applied status filter: ' . $arguments['status']);
                }
            }$limit = $arguments['limit'] ?? 20;
            
            \Log::info('Customer stats query SQL: ' . $query->toSql());
            \Log::info('Customer stats query bindings: ', $query->getBindings());
            
            $customerStats = $query->orderBy('total_spent', 'desc')
                                  ->limit($limit)
                                  ->get();

            \Log::info('Customer stats found: ' . $customerStats->count());
            \Log::info('Customer stats data: ', $customerStats->toArray());

            // Calculate overall statistics with same filters
            $overallQuery = Order::query();
            
            if (!empty($arguments['customer_name'])) {
                $overallQuery->where('name', 'like', "%{$arguments['customer_name']}%");
            }

            if (!empty($arguments['date_from'])) {
                $overallQuery->whereDate('created_at', '>=', $arguments['date_from']);
            }

            if (!empty($arguments['date_to'])) {
                $overallQuery->whereDate('created_at', '<=', $arguments['date_to']);
            }            if (!empty($arguments['status'])) {
                if ($arguments['status'] === 'all') {
                    // Don't apply any status filter when "all" is specified
                    \Log::info('Overall stats: Status filter set to "all" - no status filtering applied');
                } else {
                    $overallQuery->where('status', $arguments['status']);
                    \Log::info('Overall stats: Applied status filter: ' . $arguments['status']);
                }
            }
            
            $overallStats = $overallQuery->selectRaw('
                COUNT(DISTINCT name) as unique_customers,
                COUNT(*) as total_orders,
                SUM(amount) as total_revenue,
                AVG(amount) as average_order_value
            ')->first();

            \Log::info('Overall stats: ', $overallStats->toArray());

            return [
                'success' => true,                'overall_statistics' => [
                    'unique_customers' => $overallStats->unique_customers,
                    'total_orders' => $overallStats->total_orders,
                    'total_revenue' => round($overallStats->total_revenue, 2),
                    'average_order_value' => round($overallStats->average_order_value, 2),
                ],
                'customer_count' => $customerStats->count(),                'customers' => $customerStats->map(function ($customer) {
                    return [
                        'customer_name' => $customer->name,
                        'total_orders' => $customer->total_orders,
                        'total_spent' => round($customer->total_spent, 2),
                        'average_order_amount' => round($customer->average_order_amount, 2),
                        'first_order_date' => $customer->first_order_date,
                        'last_order_date' => $customer->last_order_date,
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            throw new JsonRpcErrorException(
                message: "Failed to retrieve customer statistics: " . $e->getMessage(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }
}
