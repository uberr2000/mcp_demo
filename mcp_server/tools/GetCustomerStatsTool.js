import { BaseTool } from "./BaseTool.js";
import Joi from "joi";
import db from "../database.js";

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
            const whereConditions = [];
            const queryParams = [];

            if (params.customer_name) {
                whereConditions.push("name LIKE ?");
                queryParams.push(`%${params.customer_name}%`);
            }

            if (params.status && params.status !== "all") {
                whereConditions.push("status = ?");
                queryParams.push(params.status);
            }

            if (params.date_from) {
                whereConditions.push("created_at >= ?");
                queryParams.push(params.date_from);
            }

            if (params.date_to) {
                whereConditions.push("created_at <= ?");
                queryParams.push(params.date_to);
            }

            const whereClause = whereConditions.length > 0 
                ? "WHERE " + whereConditions.join(" AND ") 
                : "";

            const limit = params.limit || 10;

            // 使用真实的MySQL聚合查询
            const sql = `
                SELECT 
                    name as customer_name,
                    COUNT(*) as total_orders,
                    SUM(amount) as total_spending,
                    AVG(amount) as avg_order_amount,
                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                FROM orders
                ${whereClause}
                GROUP BY name
                ORDER BY total_spending DESC
                LIMIT ?
            `;

            const stats = await db.query(sql, [...queryParams, limit]);

            return {
                success: true,
                total: stats.length,
                stats: stats.map((stat) => ({
                    customer_name: stat.customer_name,
                    total_orders: stat.total_orders,
                    total_spending: parseFloat(stat.total_spending) || 0,
                    avg_order_amount: parseFloat(stat.avg_order_amount) || 0,
                    first_order_date: stat.first_order_date,
                    last_order_date: stat.last_order_date,
                })),
            };
        } catch (error) {
            throw new Error(
                `Failed to retrieve customer stats: ${error.message}`
            );
        }
    }
}
