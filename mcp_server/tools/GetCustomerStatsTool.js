import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

export class GetCustomerStatsTool extends BaseTool {
    get name() {
        return "get_customer_stats";
    }

    get description() {
        return "獲取客戶統計信息，包括訂單數量、總消費金額、平均訂單金額等";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                customer_name: {
                    type: "string",
                    description: "客戶姓名 - Optional field",
                },
                date_from: {
                    type: "string",
                    format: "date",
                    description:
                        "開始日期 (YYYY-MM-DD format) - Optional field",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description:
                        "結束日期 (YYYY-MM-DD format) - Optional field",
                },
                status: {
                    type: "string",
                    enum: [
                        "pending",
                        "processing",
                        "completed",
                        "cancelled",
                        "refunded",
                        "all",
                    ],
                    description: "訂單狀態篩選 - Optional field",
                },
                limit: {
                    type: "integer",
                    description:
                        "返回結果數量限制 - Optional field (default: 10, range: 1-100)",
                    default: 10,
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
            limit: Joi.number().integer().min(1).max(100).default(10),
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

            if (params.status && params.status !== "all") {
                matchStage.status = params.status;
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

            const limit = params.limit || 10;

            // 这里需要替换为实际的数据库聚合查询
            // const stats = await Order.aggregate([
            //     { $match: matchStage },
            //     {
            //         $group: {
            //             _id: '$name',
            //             total_orders: { $sum: 1 },
            //             total_spending: { $sum: '$amount' },
            //             avg_order_amount: { $avg: '$amount' },
            //             first_order_date: { $min: '$created_at' },
            //             last_order_date: { $max: '$created_at' }
            //         }
            //     },
            //     { $sort: { total_spending: -1 } },
            //     { $limit: limit }
            // ]);

            // 模拟返回数据
            const stats = [];

            return {
                success: true,
                total: stats.length,
                stats: stats.map((stat) => ({
                    customer_name: stat._id,
                    total_orders: stat.total_orders,
                    total_spending: stat.total_spending,
                    avg_order_amount: stat.avg_order_amount,
                    first_order_date: stat.first_order_date.toISOString(),
                    last_order_date: stat.last_order_date.toISOString(),
                })),
            };
        } catch (error) {
            throw new Error(
                `Failed to retrieve customer stats: ${error.message}`
            );
        }
    }
}
