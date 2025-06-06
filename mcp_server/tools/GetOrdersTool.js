import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

export class GetOrdersTool extends BaseTool {
    get name() {
        return "get_orders";
    }

    get description() {
        return "從資料庫獲取訂單資訊，可以根據交易ID、客戶名稱、狀態等進行查詢";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                transaction_id: {
                    type: "string",
                    description: "交易ID - Optional field",
                },
                customer_name: {
                    type: "string",
                    description: "客戶姓名 - Optional field",
                },
                status: {
                    type: "string",
                    enum: [
                        "pending",
                        "processing",
                        "completed",
                        "cancelled",
                        "refunded",
                    ],
                    description: "訂單狀態 - Optional field",
                },
                product_name: {
                    type: "string",
                    description: "產品名稱 - Optional field",
                },
                min_amount: {
                    type: "number",
                    description: "最小金額 - Optional field",
                },
                max_amount: {
                    type: "number",
                    description: "最大金額 - Optional field",
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
                .valid(
                    "pending",
                    "processing",
                    "completed",
                    "cancelled",
                    "refunded"
                )
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

            // 构建查询条件
            const query = {};

            if (params.transaction_id) {
                query.transaction_id = params.transaction_id;
            }

            if (params.customer_name) {
                query.name = { $regex: params.customer_name, $options: "i" };
            }

            if (params.status) {
                query.status = params.status;
            }

            if (params.product_name) {
                query["product.name"] = {
                    $regex: params.product_name,
                    $options: "i",
                };
            }

            if (params.min_amount !== undefined) {
                query.amount = { $gte: params.min_amount };
            }

            if (params.max_amount !== undefined) {
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
            //     .populate('product')
            //     .sort({ created_at: -1 })
            //     .limit(limit);

            // 模拟返回数据
            const orders = [];

            return {
                success: true,
                total: orders.length,
                orders: orders.map((order) => ({
                    id: order._id,
                    transaction_id: order.transaction_id,
                    name: order.name,
                    email: order.email,
                    phone: order.phone,
                    address: order.address,
                    product: {
                        id: order.product?._id,
                        name: order.product?.name,
                        price: order.product?.price,
                    },
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
