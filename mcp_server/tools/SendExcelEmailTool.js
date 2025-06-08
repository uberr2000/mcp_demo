import { BaseTool } from "./BaseTool.js";
import Joi from "joi";
import ExcelJS from "exceljs";
import nodemailer from "nodemailer";
import AWS from "aws-sdk";
import fs from "fs/promises";
import path from "path";

export class SendExcelEmailTool extends BaseTool {
    get name() {
        return "send_excel_email";
    }

    get description() {
        return "生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱";
    }

    get inputSchema() {
        return {
            type: "object",
            properties: {
                type: {
                    type: "string",
                    enum: ["orders", "products"],
                    description:
                        "要導出的數據類型：orders(訂單) 或 products(產品) - Required field",
                },
                email: {
                    type: "string",
                    format: "email",
                    description: "接收Excel文件的郵箱地址",
                },
                subject: {
                    type: "string",
                    description:
                        "郵件主題 - Optional field (default: 系統自動生成)",
                },
                message: {
                    type: "string",
                    description:
                        "郵件內容 - Optional field (default: 系統自動生成)",
                },
                filters: {
                    type: "object",
                    description: "篩選條件 - Optional field",
                    properties: {
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
                            description:
                                '訂單狀態篩選（僅適用於訂單導出）- Use "all" to include all statuses',
                        },
                        customer_name: {
                            type: "string",
                            description: "客戶姓名篩選（僅適用於訂單導出）",
                        },
                        product_name: {
                            type: "string",
                            description: "產品名稱篩選",
                        },
                        date_from: {
                            type: "string",
                            format: "date",
                            description: "開始日期 (YYYY-MM-DD format)",
                        },
                        date_to: {
                            type: "string",
                            format: "date",
                            description: "結束日期 (YYYY-MM-DD format)",
                        },
                        category: {
                            type: "string",
                            description: "產品類別篩選（僅適用於產品導出）",
                        },
                        stock_quantity: {
                            type: "integer",
                            description: "庫存數量篩選（僅適用於產品導出）",
                        },
                    },
                },
                limit: {
                    type: "integer",
                    minimum: 1,
                    maximum: 10000,
                    description:
                        "導出記錄數量限制 - Optional field (default: 1000, max: 10000)",
                },
            },
            required: ["type", "email"],
        };
    }

    validateInput(input) {
        const schema = Joi.object({
            type: Joi.string().valid("orders", "products").required(),
            email: Joi.string().email().required(),
            subject: Joi.string().allow("", null),
            message: Joi.string().allow("", null),
            filters: Joi.object({
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
                customer_name: Joi.string().allow("", null),
                product_name: Joi.string().allow("", null),
                date_from: Joi.date().allow("", null),
                date_to: Joi.date().min(Joi.ref("date_from")).allow("", null),
                category: Joi.string().allow("", null),
                stock_quantity: Joi.number().integer().min(0).allow(null),
            }).allow(null),
            limit: Joi.number().integer().min(1).max(10000).default(1000),
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

            const {
                type,
                email,
                subject,
                message,
                filters = {},
                limit = 1000,
            } = params;

            // 生成文件名
            const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
            const uniqueId = Math.random().toString(36).substring(2, 15);
            const filename = `${type}_export_${timestamp}_${uniqueId}.xlsx`;
            const filePath = path.join(process.cwd(), "exports", filename);

            // 確保導出目錄存在
            await fs.mkdir(path.join(process.cwd(), "exports"), {
                recursive: true,
            });

            // 獲取數據
            let data;
            if (type === "orders") {
                data = await this.getOrdersData(filters, limit);
            } else {
                data = await this.getProductsData(filters, limit);
            }

            // 生成 Excel 文件
            await this.generateExcelFile(data, type, filePath);

            // 發送郵件
            const defaultSubject = `${
                type === "orders" ? "訂單" : "產品"
            }數據導出 - ${timestamp}`;
            const defaultMessage = `附件包含您請求的${
                type === "orders" ? "訂單" : "產品"
            }數據導出文件。\n\n導出時間：${timestamp}\n記錄數量：${
                data.length
            }`;

            await this.sendEmail(
                email,
                subject || defaultSubject,
                message || defaultMessage,
                filePath,
                filename
            );

            // 清理臨時文件
            await fs.unlink(filePath);

            return {
                success: true,
                message: `Excel 文件已成功發送到 ${email}`,
                data: {
                    type,
                    email,
                    filename,
                    records_count: data.length,
                    export_time: timestamp,
                    subject: subject || defaultSubject,
                },
            };
        } catch (error) {
            throw new Error(`發送郵件失敗：${error.message}`);
        }
    }

    async getOrdersData(filters, limit) {
        const query = {};

        if (filters.status && filters.status !== "all") {
            query.status = filters.status;
        }

        if (filters.customer_name) {
            query.name = { $regex: filters.customer_name, $options: "i" };
        }

        if (filters.product_name) {
            query["product.name"] = {
                $regex: filters.product_name,
                $options: "i",
            };
        }

        if (filters.date_from) {
            query.created_at = { $gte: new Date(filters.date_from) };
        }

        if (filters.date_to) {
            query.created_at = {
                ...query.created_at,
                $lte: new Date(filters.date_to),
            };
        }

        // 这里需要替换为实际的数据库查询
        // const orders = await Order.find(query)
        //     .populate('product')
        //     .sort({ created_at: -1 })
        //     .limit(limit);

        // 模拟返回数据
        return [];
    }

    async getProductsData(filters, limit) {
        const query = {};

        if (filters.product_name) {
            query.name = { $regex: filters.product_name, $options: "i" };
        }

        if (filters.category) {
            query.category = { $regex: filters.category, $options: "i" };
        }

        if (filters.stock_quantity !== undefined) {
            query.stock_quantity = { $gte: filters.stock_quantity };
        }

        // 这里需要替换为实际的数据库查询
        // const products = await Product.find(query)
        //     .sort({ name: 1 })
        //     .limit(limit);

        // 模拟返回数据
        return [];
    }

    async generateExcelFile(data, type, filePath) {
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet(
            type === "orders" ? "Orders" : "Products"
        );

        if (type === "orders") {
            worksheet.columns = [
                { header: "訂單ID", key: "id", width: 20 },
                { header: "客戶姓名", key: "customer_name", width: 20 },
                { header: "產品名稱", key: "product_name", width: 30 },
                { header: "數量", key: "quantity", width: 10 },
                { header: "金額", key: "amount", width: 15 },
                { header: "狀態", key: "status", width: 15 },
                { header: "創建時間", key: "created_at", width: 20 },
            ];

            data.forEach((order) => {
                worksheet.addRow({
                    id: order.id,
                    customer_name: order.name,
                    product_name: order.product?.name || "Unknown",
                    quantity: order.quantity,
                    amount: order.amount,
                    status: order.status,
                    created_at: new Date(order.created_at).toLocaleString(),
                });
            });
        } else {
            worksheet.columns = [
                { header: "產品ID", key: "id", width: 20 },
                { header: "產品名稱", key: "name", width: 30 },
                { header: "描述", key: "description", width: 40 },
                { header: "價格", key: "price", width: 15 },
                { header: "庫存", key: "stock_quantity", width: 10 },
                { header: "類別", key: "category", width: 20 },
                { header: "創建時間", key: "created_at", width: 20 },
            ];

            data.forEach((product) => {
                worksheet.addRow({
                    id: product.id,
                    name: product.name,
                    description: product.description,
                    price: product.price,
                    stock_quantity: product.stock_quantity,
                    category: product.category,
                    created_at: new Date(product.created_at).toLocaleString(),
                });
            });
        }

        await workbook.xlsx.writeFile(filePath);
    }

    async sendEmail(to, subject, text, filePath, filename) {
        let transporter;
        let sesError = null;
        
        // Try AWS SES first if configured
        if (process.env.AWS_ACCESS_KEY_ID && process.env.AWS_SECRET_ACCESS_KEY && process.env.SES_FROM_EMAIL) {
            try {
                console.log('Attempting to use AWS SES for email sending...');
                
                // Use AWS SDK v2 for compatibility with Nodemailer
                const ses = new AWS.SES({
                    region: process.env.AWS_REGION || 'us-east-1',
                    accessKeyId: process.env.AWS_ACCESS_KEY_ID,
                    secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY,
                });

                // Create transporter with AWS SDK v2
                transporter = nodemailer.createTransport({
                    SES: { ses, aws: AWS }
                });
                
                const result = await transporter.sendMail({
                    from: process.env.SES_FROM_EMAIL,
                    to,
                    subject,
                    text,
                    attachments: [
                        {
                            filename,
                            path: filePath,
                        },
                    ],
                });
                
                console.log('Email sent successfully via AWS SES:', result.messageId);
                return result;
                
            } catch (error) {
                console.error('AWS SES failed, trying fallback method:', error.message);
                sesError = error; // Store the error for later reference
                // Fall through to SMTP fallback
            }
        }
        
        // Fallback to SMTP if SES fails or is not configured
        if (process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASS) {
            try {
                console.log('Using SMTP fallback for email sending...');
                
                transporter = nodemailer.createTransport({
                    host: process.env.SMTP_HOST,
                    port: process.env.SMTP_PORT || 587,
                    secure: process.env.SMTP_PORT === '465', // true for 465, false for other ports
                    auth: {
                        user: process.env.SMTP_USER,
                        pass: process.env.SMTP_PASS,
                    },
                });
                
                const result = await transporter.sendMail({
                    from: process.env.SMTP_FROM || process.env.SMTP_USER,
                    to,
                    subject,
                    text,
                    attachments: [
                        {
                            filename,
                            path: filePath,
                        },
                    ],
                });
                
                console.log('Email sent successfully via SMTP:', result.messageId);
                return result;
                
            } catch (smtpError) {
                console.error('SMTP also failed:', smtpError.message);
                throw new Error(`Both AWS SES and SMTP failed. SES: ${sesError?.message || 'Not configured'}, SMTP: ${smtpError.message}`);
            }
        }
        
        // If no email service is configured, simulate success for development
        console.warn('No email service configured. Simulating email send for development...');
        console.log(`📧 Email would be sent to: ${to}`);
        console.log(`📧 Subject: ${subject}`);
        console.log(`📧 Message: ${text}`);
        console.log(`📧 Attachment: ${filename}`);
        
        return {
            messageId: 'simulated-' + Date.now(),
            message: 'Email sending simulated (no email service configured)'
        };
    }
}
