import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

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

            // 构建查询条件
            const query = {};

            if (params.name) {
                query.name = { $regex: params.name, $options: "i" };
            }

            if (params.category) {
                query.category = { $regex: params.category, $options: "i" };
            }

            if (params.min_price !== undefined) {
                query.price = { $gte: params.min_price };
            }

            if (params.max_price !== undefined) {
                query.price = { ...query.price, $lte: params.max_price };
            }

            if (params.stock_quantity !== undefined) {
                query.stock_quantity = { $gte: params.stock_quantity };
            }

            const limit = params.limit || 10;

            // 这里需要替换为实际的数据库查询
            // const products = await Product.find(query)
            //     .sort({ name: 1 })
            //     .limit(limit);

            // 模拟返回数据
            const products = [];

            return {
                success: true,
                total: products.length,
                products: products.map((product) => ({
                    id: product._id,
                    name: product.name,
                    description: product.description,
                    price: product.price,
                    stock_quantity: product.stock_quantity,
                    category: product.category,
                    created_at: product.created_at.toISOString(),
                    updated_at: product.updated_at.toISOString(),
                })),
            };
        } catch (error) {
            throw new Error(`Failed to retrieve products: ${error.message}`);
        }
    }
}
