import { BaseTool } from "./BaseTool.js";
import Joi from "joi";
import db from "../database.js";

export class GetOrderAnalyticsTool extends BaseTool {
    get name() {
        return "get_order_analytics";
    }

    get description() {
        return "獲取訂單分析數據，包括每日訂單量、月度訂單量、訂單狀態分布、產品銷售分析等";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                analytics_type: {
                    type: "string",
                    enum: ["daily", "monthly", "status", "product"],
                    description:
                        "分析類型：daily(每日)、monthly(月度)、status(狀態)、product(產品) - Required field",
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
                        "返回結果數量限制 - Optional field (default: 30, max: 100)",
                    default: 30,
                    minimum: 1,
                    maximum: 100,
                },
            },
            required: ["analytics_type"],
        };
    }

    validateInput(input) {
        const schema = Joi.object({
            analytics_type: Joi.string()
                .valid("daily", "monthly", "status", "product")
                .required(),
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
            limit: Joi.number().integer().min(1).max(100).default(30),
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

            // 构建基础查询条件
            const whereConditions = [];
            const queryParams = [];

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

            const limit = params.limit || 30;

            let analytics;
            switch (params.analytics_type) {
                case "daily":
                    analytics = await this.getDailyAnalytics(whereClause, queryParams, limit);
                    break;
                case "monthly":
                    analytics = await this.getMonthlyAnalytics(whereClause, queryParams, limit);
                    break;
                case "status":
                    analytics = await this.getStatusAnalytics(whereClause, queryParams);
                    break;
                case "product":
                    analytics = await this.getProductAnalytics(whereClause, queryParams, limit);
                    break;
                default:
                    throw new Error("Invalid analytics type");
            }

            return {
                success: true,
                analytics_type: params.analytics_type,
                data: analytics,
            };
        } catch (error) {
            throw new Error(
                `Failed to retrieve order analytics: ${error.message}`
            );
        }
    }

    async getDailyAnalytics(whereClause, queryParams, limit) {
        try {
            const sql = `
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM orders 
                ${whereClause}
                GROUP BY DATE(created_at) 
                ORDER BY date DESC 
                LIMIT ?
            `;
            
            const results = await db.query(sql, [...queryParams, limit]);
            return results.map(row => ({
                date: row.date,
                order_count: row.order_count,
                total_amount: parseFloat(row.total_amount) || 0,
                avg_amount: parseFloat(row.avg_amount) || 0
            }));
        } catch (error) {
            console.error('Error in getDailyAnalytics:', error);
            throw error;
        }
    }

    async getMonthlyAnalytics(whereClause, queryParams, limit) {
        try {
            const sql = `
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as order_count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM orders 
                ${whereClause}
                GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                ORDER BY month DESC 
                LIMIT ?
            `;
            
            const results = await db.query(sql, [...queryParams, limit]);
            return results.map(row => ({
                month: row.month,
                order_count: row.order_count,
                total_amount: parseFloat(row.total_amount) || 0,
                avg_amount: parseFloat(row.avg_amount) || 0
            }));
        } catch (error) {
            console.error('Error in getMonthlyAnalytics:', error);
            throw error;
        }
    }

    async getStatusAnalytics(whereClause, queryParams) {
        try {
            const sql = `
                SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM orders 
                ${whereClause}
                GROUP BY status 
                ORDER BY count DESC
            `;
            
            const results = await db.query(sql, queryParams);
            return results.map(row => ({
                status: row.status,
                count: row.count,
                total_amount: parseFloat(row.total_amount) || 0,
                avg_amount: parseFloat(row.avg_amount) || 0
            }));
        } catch (error) {
            console.error('Error in getStatusAnalytics:', error);
            throw error;
        }
    }

    async getProductAnalytics(whereClause, queryParams, limit) {
        try {
            // 修改 WHERE 子句以使用表别名
            let modifiedWhereClause = whereClause.replace(/created_at/g, 'o.created_at');
            modifiedWhereClause = modifiedWhereClause.replace(/status/g, 'o.status');
            
            // Join orders with products to get product analytics
            const sql = `
                SELECT 
                    p.name as product_name,
                    p.id as product_id,
                    COUNT(o.id) as order_count,
                    SUM(o.quantity) as total_quantity,
                    SUM(o.amount) as total_amount,
                    AVG(o.amount) as avg_order_amount
                FROM orders o
                INNER JOIN products p ON o.product_id = p.id
                ${modifiedWhereClause}
                GROUP BY p.id, p.name 
                ORDER BY total_amount DESC 
                LIMIT ?
            `;
            
            const results = await db.query(sql, [...queryParams, limit]);
            return results.map(row => ({
                product_id: row.product_id,
                product_name: row.product_name,
                order_count: row.order_count,
                total_quantity: row.total_quantity,
                total_amount: parseFloat(row.total_amount) || 0,
                avg_order_amount: parseFloat(row.avg_order_amount) || 0
            }));
        } catch (error) {
            console.error('Error in getProductAnalytics:', error);
            throw error;
        }
    }
}
