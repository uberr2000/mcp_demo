import { BaseTool } from "./BaseTool.js";
import Joi from "joi";
import db from "../database.js";

export class GetProductsTool extends BaseTool {
    get name() {
        return "get_products";
    }

    get description() {
        return "從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                name: {
                    type: "string",
                    description: "產品名稱（可部分匹配）- Optional field",
                },
                category: {
                    type: "string",
                    description: "產品類別 - Optional field",
                },
                min_price: {
                    type: "number",
                    description: "最小價格 - Optional field",
                },
                max_price: {
                    type: "number",
                    description: "最大價格 - Optional field",
                },
                stock_quantity: {
                    type: "integer",
                    description: "庫存數量篩選 - Optional field",
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
            name: Joi.string().allow("", null),
            category: Joi.string().allow("", null),
            min_price: Joi.number().min(0).allow(null),
            max_price: Joi.number().min(0).allow(null),
            stock_quantity: Joi.number().integer().min(0).allow(null),
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
            let sql = "SELECT id, name, description, price, stock_quantity, category, created_at, updated_at FROM products WHERE 1=1";
            
            const queryParams = [];

            // 添加查詢條件
            if (params.name) {
                sql += ' AND name LIKE ?';
                queryParams.push(`%${params.name}%`);
            }

            if (params.category) {
                sql += ' AND category LIKE ?';
                queryParams.push(`%${params.category}%`);
            }

            if (params.min_price !== undefined) {
                sql += ' AND price >= ?';
                queryParams.push(params.min_price);
            }

            if (params.max_price !== undefined) {
                sql += ' AND price <= ?';
                queryParams.push(params.max_price);
            }

            if (params.stock_quantity !== undefined) {
                sql += ' AND stock_quantity >= ?';
                queryParams.push(params.stock_quantity);
            }

            // 添加排序和限制
            sql += ' ORDER BY name ASC';
            
            const limit = parseInt(params.limit || 10);
            sql += ' LIMIT ?';
            queryParams.push(limit);

            console.log('Executing SQL:', sql);
            console.log('With params:', queryParams);

            // 執行查詢
            const products = await db.query(sql, queryParams);

            return {
                success: true,
                total: products.length,
                products: products.map((product) => ({
                    id: product.id,
                    name: product.name,
                    description: product.description,
                    price: product.price,
                    stock_quantity: product.stock_quantity,
                    category: product.category,
                    created_at: product.created_at,
                    updated_at: product.updated_at,
                })),
            };
        } catch (error) {
            console.error('GetProductsTool execution error:', error);
            throw new Error(`Failed to retrieve products: ${error.message}`);
        }
    }
}
