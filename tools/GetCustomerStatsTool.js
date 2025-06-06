import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

export class GetCustomerStatsTool extends BaseTool {
    get name() {
        return "get_customer_stats";
    }

    get description() {
        return "Get customer statistics including order count, total spending, average order amount, etc.";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                customer_name: {
                    type: "string",
                    description:
                        "Customer name (partial match supported) - Optional field",
                },
                date_from: {
                    type: "string",
                    format: "date",
                    description:
                        "Statistics start date (YYYY-MM-DD) - Optional field",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description:
                        "Statistics end date (YYYY-MM-DD) - Optional field",
                },
                status: {
                    type: "string",
                    description:
                        'Order status filter (pending, processing, completed, cancelled, refunded, all) - Optional field. Use "all" to include all statuses',
                },
                limit: {
                    type: "integer",
                    description:
                        "Limit number of customers returned - Optional field (default: 20, range: 1-100)",
                    default: 20,
                    minimum: 1,
                    maximum: 100,
                },
            },
        };
    }

    validateInput(input) {
        const schema = Joi.object({
            customer_name: Joi.string().allow("", null),
            date_from: Joi.date().allow("", null),
            date_to: Joi.date().min(Joi.ref("date_from")).allow("", null),
            status: Joi.string()
                .valid(
                    "pending",
                    "processing",
                    "completed",
                    "cancelled",
                    "refunded",
                    "all"
                )
                .allow("", null),
            limit: Joi.number().integer().min(1).max(100).default(20),
        });

        const { error } = schema.validate(input);
        if (error) {
            throw new Error(`Validation error: ${error.message}`);
        }
        return true;
    }

    async execute(params) {
        try {
            this.validateInput(params);

            // 构建查询条件
            const matchStage = {};

            if (params.customer_name) {
                matchStage.name = {
                    $regex: params.customer_name,
                    $options: "i",
                };
            }

            if (params.date_from) {
                matchStage.created_at = { $gte: new Date(params.date_from) };
            }

            if (params.date_to) {
                matchStage.created_at = {
                    ...matchStage.created_at,
                    $lte: new Date(params.date_to),
                };
            }

            if (params.status && params.status !== "all") {
                matchStage.status = params.status;
            }

            // 客户统计聚合查询
            const customerStatsPipeline = [
                { $match: matchStage },
                {
                    $group: {
                        _id: "$name",
                        total_orders: { $sum: 1 },
                        total_spent: { $sum: "$amount" },
                        average_order_amount: { $avg: "$amount" },
                        last_order_date: { $max: "$created_at" },
                        first_order_date: { $min: "$created_at" },
                    },
                },
                { $sort: { total_spent: -1 } },
                { $limit: params.limit || 20 },
            ];

            // 总体统计聚合查询
            const overallStatsPipeline = [
                { $match: matchStage },
                {
                    $group: {
                        _id: null,
                        unique_customers: { $addToSet: "$name" },
                        total_orders: { $sum: 1 },
                        total_revenue: { $sum: "$amount" },
                        average_order_value: { $avg: "$amount" },
                    },
                },
                {
                    $project: {
                        _id: 0,
                        unique_customers: { $size: "$unique_customers" },
                        total_orders: 1,
                        total_revenue: 1,
                        average_order_value: 1,
                    },
                },
            ];

            // 这里需要替换为实际的数据库查询
            // const customerStats = await Order.aggregate(customerStatsPipeline);
            // const overallStats = await Order.aggregate(overallStatsPipeline);

            // 模拟返回数据
            const customerStats = [];
            const overallStats = [
                {
                    unique_customers: 0,
                    total_orders: 0,
                    total_revenue: 0,
                    average_order_value: 0,
                },
            ];

            return {
                success: true,
                overall_statistics: {
                    unique_customers: overallStats[0].unique_customers,
                    total_orders: overallStats[0].total_orders,
                    total_revenue: Number(
                        overallStats[0].total_revenue.toFixed(2)
                    ),
                    average_order_value: Number(
                        overallStats[0].average_order_value.toFixed(2)
                    ),
                },
                customer_count: customerStats.length,
                customers: customerStats.map((customer) => ({
                    customer_name: customer._id,
                    total_orders: customer.total_orders,
                    total_spent: Number(customer.total_spent.toFixed(2)),
                    average_order_amount: Number(
                        customer.average_order_amount.toFixed(2)
                    ),
                    first_order_date: customer.first_order_date,
                    last_order_date: customer.last_order_date,
                })),
            };
        } catch (error) {
            throw new Error(
                `Failed to retrieve customer statistics: ${error.message}`
            );
        }
    }
}
