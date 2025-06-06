import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

export class GetOrdersTool extends BaseTool {
    get name() {
        return "get_orders";
    }

    get description() {
        return "從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                transaction_id: {
                    type: "string",
                    description: "交易ID（可部分匹配）- Optional field",
                },
                customer_name: {
                    type: "string",
                    description: "客戶姓名（可部分匹配）- Optional field",
                },
                status: {
                    type: "string",
                    description:
                        '訂單狀態（pending, completed, cancelled, all）- Optional field. Use "all" to include all statuses',
                },
                product_name: {
                    type: "string",
                    description: "產品名稱（可部分匹配）- Optional field",
                },
                min_amount: {
                    type: "number",
                    description:
                        "最小金額 - Optional field (use 0 to ignore this filter)",
                },
                max_amount: {
                    type: "number",
                    description:
                        "最大金額 - Optional field (use 0 to ignore this filter)",
                },
                date_from: {
                    type: "string",
                    format: "date",
                    description: "開始日期 (YYYY-MM-DD) - Optional field",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description: "結束日期 (YYYY-MM-DD) - Optional field",
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
            transaction_id: Joi.string().allow("", null),
            customer_name: Joi.string().allow("", null),
            status: Joi.string()
                .valid("pending", "completed", "cancelled", "all")
                .allow("", null),
            product_name: Joi.string().allow("", null),
            min_amount: Joi.number().min(0).allow(null),
            max_amount: Joi.number().min(0).allow(null),
            date_from: Joi.date().allow("", null),
            date_to: Joi.date().min(Joi.ref("date_from")).allow("", null),
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

            // 这里需要实现实际的数据库查询逻辑
            // 示例实现：
            const query = {};

            if (params.transaction_id) {
                query.transaction_id = {
                    $regex: params.transaction_id,
                    $options: "i",
                };
            }

            if (params.customer_name) {
                query.name = { $regex: params.customer_name, $options: "i" };
            }

            if (params.status && params.status !== "all") {
                query.status = params.status;
            }

            if (params.product_name) {
                query["product.name"] = {
                    $regex: params.product_name,
                    $options: "i",
                };
            }

            if (params.min_amount > 0) {
                query.amount = { $gte: params.min_amount };
            }

            if (params.max_amount > 0) {
                query.amount = { ...query.amount, $lte: params.max_amount };
            }

            if (params.date_from) {
                query.created_at = { $gte: new Date(params.date_from) };
            }

            if (params.date_to) {
                query.created_at = {
                    ...query.created_at,
                    $lte: new Date(params.date_to),
                };
            }

            // 如果没有指定日期范围，默认查询最近30天
            if (!params.date_from && !params.date_to) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                query.created_at = { $gte: thirtyDaysAgo };
            }

            const limit = params.limit || 10;

            // 这里需要替换为实际的数据库查询
            // const orders = await Order.find(query)
            //     .sort({ created_at: -1 })
            //     .limit(limit)
            //     .populate('product');

            // 模拟返回数据
            const orders = [];

            return {
                success: true,
                total: orders.length,
                orders: orders.map((order) => ({
                    transaction_id: order.transaction_id,
                    customer_name: order.name,
                    product_name: order.product?.name || "Unknown",
                    quantity: order.quantity,
                    amount: order.amount,
                    status: order.status,
                    created_at: order.created_at.toISOString(),
                    updated_at: order.updated_at.toISOString(),
                })),
            };
        } catch (error) {
            throw new Error(`Failed to retrieve orders: ${error.message}`);
        }
    }
}
