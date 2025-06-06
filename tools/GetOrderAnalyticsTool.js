import { BaseTool } from "./BaseTool.js";
import Joi from "joi";

export class GetOrderAnalyticsTool extends BaseTool {
    get name() {
        return "get_order_analytics";
    }

    get description() {
        return "獲取訂單分析資料，包括按日期、狀態、產品的統計分析";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                analytics_type: {
                    type: "string",
                    enum: ["daily", "status", "product", "monthly"],
                    description:
                        "分析類型：daily（按日統計）、status（按狀態統計）、product（按產品統計）、monthly（按月統計）- Optional field (default: daily)",
                    default: "daily",
                },
                date_from: {
                    type: "string",
                    format: "date",
                    description: "分析開始日期 (YYYY-MM-DD) - Optional field",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description: "分析結束日期 (YYYY-MM-DD) - Optional field",
                },
                status: {
                    type: "string",
                    description:
                        '篩選特定訂單狀態（pending, completed, cancelled, all）- Optional field. Use "all" to include all statuses',
                },
                limit: {
                    type: "integer",
                    description:
                        "返回結果數量限制 - Optional field (default: 30, range: 1-100)",
                    default: 30,
                    minimum: 1,
                    maximum: 100,
                },
            },
        };
    }

    validateInput(input) {
        const schema = Joi.object({
            analytics_type: Joi.string()
                .valid("daily", "status", "product", "monthly")
                .default("daily"),
            date_from: Joi.date().allow("", null),
            date_to: Joi.date().min(Joi.ref("date_from")).allow("", null),
            status: Joi.string()
                .valid("pending", "completed", "cancelled", "all")
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

            const analyticsType = params.analytics_type || "daily";
            const limit = params.limit || 30;

            // 构建基础查询条件
            const matchStage = {};

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
            } else if (params.status === "") {
                matchStage.status = "completed";
            }

            let analytics = [];

            switch (analyticsType) {
                case "daily":
                    analytics = await this.getDailyAnalytics(matchStage, limit);
                    break;
                case "monthly":
                    analytics = await this.getMonthlyAnalytics(
                        matchStage,
                        limit
                    );
                    break;
                case "status":
                    analytics = await this.getStatusAnalytics(matchStage);
                    break;
                case "product":
                    analytics = await this.getProductAnalytics(
                        matchStage,
                        limit
                    );
                    break;
            }

            return {
                success: true,
                analytics_type: analyticsType,
                data: analytics,
            };
        } catch (error) {
            throw new Error(
                `Failed to retrieve order analytics: ${error.message}`
            );
        }
    }

    async getDailyAnalytics(matchStage, limit) {
        const pipeline = [
            { $match: matchStage },
            {
                $group: {
                    _id: {
                        $dateToString: {
                            format: "%Y-%m-%d",
                            date: "$created_at",
                        },
                    },
                    order_count: { $sum: 1 },
                    total_revenue: { $sum: "$amount" },
                    average_order_value: { $avg: "$amount" },
                    unique_customers: { $addToSet: "$name" },
                },
            },
            {
                $project: {
                    _id: 0,
                    date: "$_id",
                    order_count: 1,
                    total_revenue: 1,
                    average_order_value: 1,
                    unique_customers: { $size: "$unique_customers" },
                },
            },
            { $sort: { date: -1 } },
            { $limit: limit },
        ];

        // 这里需要替换为实际的数据库查询
        // const results = await Order.aggregate(pipeline);

        // 模拟返回数据
        const results = [];

        return results.map((item) => ({
            date: item.date,
            order_count: item.order_count,
            total_revenue: Number(item.total_revenue.toFixed(2)),
            average_order_value: Number(item.average_order_value.toFixed(2)),
            unique_customers: item.unique_customers,
        }));
    }

    async getMonthlyAnalytics(matchStage, limit) {
        const pipeline = [
            { $match: matchStage },
            {
                $group: {
                    _id: {
                        year: { $year: "$created_at" },
                        month: { $month: "$created_at" },
                    },
                    order_count: { $sum: 1 },
                    total_revenue: { $sum: "$amount" },
                    average_order_value: { $avg: "$amount" },
                    unique_customers: { $addToSet: "$name" },
                },
            },
            {
                $project: {
                    _id: 0,
                    year: "$_id.year",
                    month: "$_id.month",
                    month_name: {
                        $let: {
                            vars: {
                                months: [
                                    "January",
                                    "February",
                                    "March",
                                    "April",
                                    "May",
                                    "June",
                                    "July",
                                    "August",
                                    "September",
                                    "October",
                                    "November",
                                    "December",
                                ],
                            },
                            in: {
                                $arrayElemAt: [
                                    "$$months",
                                    { $subtract: ["$_id.month", 1] },
                                ],
                            },
                        },
                    },
                    order_count: 1,
                    total_revenue: 1,
                    average_order_value: 1,
                    unique_customers: { $size: "$unique_customers" },
                },
            },
            { $sort: { year: -1, month: -1 } },
            { $limit: limit },
        ];

        // 这里需要替换为实际的数据库查询
        // const results = await Order.aggregate(pipeline);

        // 模拟返回数据
        const results = [];

        return results.map((item) => ({
            year: item.year,
            month: item.month,
            month_name: item.month_name,
            order_count: item.order_count,
            total_revenue: Number(item.total_revenue.toFixed(2)),
            average_order_value: Number(item.average_order_value.toFixed(2)),
            unique_customers: item.unique_customers,
        }));
    }

    async getStatusAnalytics(matchStage) {
        const pipeline = [
            { $match: matchStage },
            {
                $group: {
                    _id: "$status",
                    order_count: { $sum: 1 },
                    total_revenue: { $sum: "$amount" },
                    average_order_value: { $avg: "$amount" },
                    unique_customers: { $addToSet: "$name" },
                },
            },
            {
                $project: {
                    _id: 0,
                    status: "$_id",
                    order_count: 1,
                    total_revenue: 1,
                    average_order_value: 1,
                    unique_customers: { $size: "$unique_customers" },
                },
            },
            { $sort: { order_count: -1 } },
        ];

        // 这里需要替换为实际的数据库查询
        // const results = await Order.aggregate(pipeline);

        // 模拟返回数据
        const results = [];

        return results.map((item) => ({
            status: item.status,
            order_count: item.order_count,
            total_revenue: Number(item.total_revenue.toFixed(2)),
            average_order_value: Number(item.average_order_value.toFixed(2)),
            unique_customers: item.unique_customers,
        }));
    }

    async getProductAnalytics(matchStage, limit) {
        const pipeline = [
            { $match: matchStage },
            {
                $group: {
                    _id: "$product_id",
                    order_count: { $sum: 1 },
                    total_quantity: { $sum: "$quantity" },
                    total_revenue: { $sum: "$amount" },
                    average_order_value: { $avg: "$amount" },
                },
            },
            {
                $lookup: {
                    from: "products",
                    localField: "_id",
                    foreignField: "_id",
                    as: "product",
                },
            },
            {
                $project: {
                    _id: 0,
                    product_id: "$_id",
                    product_name: { $arrayElemAt: ["$product.name", 0] },
                    order_count: 1,
                    total_quantity: 1,
                    total_revenue: 1,
                    average_order_value: 1,
                },
            },
            { $sort: { total_quantity: -1 } },
            { $limit: limit },
        ];

        // 这里需要替换为实际的数据库查询
        // const results = await Order.aggregate(pipeline);

        // 模拟返回数据
        const results = [];

        return results.map((item) => ({
            product_id: item.product_id,
            product_name: item.product_name || "Unknown",
            order_count: item.order_count,
            total_quantity: item.total_quantity,
            total_revenue: Number(item.total_revenue.toFixed(2)),
            average_order_value: Number(item.average_order_value.toFixed(2)),
        }));
    }
}
