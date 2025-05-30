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
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer name (partial match supported)',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Statistics start date (YYYY-MM-DD)',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Statistics end date (YYYY-MM-DD)',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Order status filter (pending, processing, completed, cancelled, refunded)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limit number of customers returned',
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
            'status' => ['nullable', 'string', 'in:pending,processing,completed,cancelled,refunded'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);        if ($validator->fails()) {
            throw new JsonRpcErrorException(
                message: $validator->errors()->toJson(),
                code: JsonRpcErrorCode::INVALID_REQUEST
            );
        }

        try {            $query = Order::select([
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
            }

            if (!empty($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            $limit = $arguments['limit'] ?? 20;
            $customerStats = $query->groupBy('name')
                                  ->orderBy('total_spent', 'desc')
                                  ->limit($limit)
                                  ->get();

            // Calculate overall statistics
            $overallStats = Order::selectRaw('
                COUNT(DISTINCT name) as unique_customers,
                COUNT(*) as total_orders,
                SUM(amount) as total_revenue,
                AVG(amount) as average_order_value
            ')->first();

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
