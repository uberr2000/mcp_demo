import { BaseTool } from "./BaseTool.js";
import Joi from "joi";
import db from "../database.js";

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

            // 構建SQL查詢
            let sql = "SELECT o.id, o.transaction_id, o.name, o.quantity, o.amount, o.status, o.created_at, o.updated_at, p.id as product_id, p.name as product_name, p.price as product_price FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE 1=1";
            
            const queryParams = [];

            // 添加查詢條件
            if (params.transaction_id) {
                sql += ' AND o.transaction_id = ?';
                queryParams.push(params.transaction_id);
            }

            if (params.customer_name) {
                sql += ' AND o.name LIKE ?';
                queryParams.push(`%${params.customer_name}%`);
            }

            if (params.status) {
                sql += ' AND o.status = ?';
                queryParams.push(params.status);
            }

            if (params.product_name) {
                sql += ' AND p.name LIKE ?';
                queryParams.push(`%${params.product_name}%`);
            }

            if (params.min_amount !== undefined) {
                sql += ' AND o.amount >= ?';
                queryParams.push(params.min_amount);
            }

            if (params.max_amount !== undefined) {
                sql += ' AND o.amount <= ?';
                queryParams.push(params.max_amount);
            }

            if (params.date_from) {
                sql += ' AND o.created_at >= ?';
                queryParams.push(params.date_from);
            }

            if (params.date_to) {
                sql += ' AND o.created_at <= ?';
                queryParams.push(params.date_to + ' 23:59:59');
            }

            // 如果沒有指定日期範圍，默認查詢最近30天
            if (!params.date_from && !params.date_to) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                sql += ' AND o.created_at >= ?';
                queryParams.push(thirtyDaysAgo.toISOString().split('T')[0]);
            }

            // 添加排序和限制
            sql += ' ORDER BY o.created_at DESC';
            
            const limit = parseInt(params.limit || 10);
            sql += ' LIMIT ?';
            queryParams.push(limit);

            console.log('Executing SQL:', sql);
            console.log('With params:', queryParams);

            // 執行查詢
            const orders = await db.query(sql, queryParams);

            return {
                success: true,
                total: orders.length,
                orders: orders.map((order) => ({
                    id: order.id,
                    transaction_id: order.transaction_id,
                    name: order.name,
                    product: {
                        id: order.product_id,
                        name: order.product_name,
                        price: order.product_price,
                    },
                    quantity: order.quantity,
                    amount: order.amount,
                    status: order.status,
                    created_at: order.created_at,
                    updated_at: order.updated_at,
                })),
            };
        } catch (error) {
            console.error('GetOrdersTool execution error:', error);
            throw new Error(`Failed to retrieve orders: ${error.message}`);
        }
    }
}
