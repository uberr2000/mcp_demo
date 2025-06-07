import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { SSEServerTransport } from "@modelcontextprotocol/sdk/server/sse.js";
import { 
    ListToolsRequestSchema, 
    CallToolRequestSchema 
} from "@modelcontextprotocol/sdk/types.js";
import express from "express";
import { GetOrdersTool } from "./tools/GetOrdersTool.js";
import { GetCustomerStatsTool } from "./tools/GetCustomerStatsTool.js";
import { GetOrderAnalyticsTool } from "./tools/GetOrderAnalyticsTool.js";
import { GetProductsTool } from "./tools/GetProductsTool.js";
import { SendExcelEmailTool } from "./tools/SendExcelEmailTool.js";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

// 獲取當前文件的目錄路徑
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 配置 dotenv 使用 Laravel 的 .env 文件
dotenv.config({ path: path.resolve(__dirname, "../.env") });

// 定義工具列表
const toolsList = [
    {
        name: "get_orders",
        description: "獲取訂單信息",
        inputSchema: {
            type: "object",
            properties: {
                transaction_id: { type: "string", description: "交易ID" },
                customer_name: { type: "string", description: "客戶名稱" },
                status: {
                    type: "string",
                    enum: ["pending", "processing", "completed", "cancelled"],
                    description: "訂單狀態",
                },
                product_name: { type: "string", description: "產品名稱" },
                min_amount: { type: "number", description: "最小金額" },
                max_amount: { type: "number", description: "最大金額" },
                date_from: {
                    type: "string",
                    format: "date",
                    description: "開始日期",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description: "結束日期",
                },
                limit: { type: "integer", description: "返回結果數量限制" },
            },
        },
    },
    {
        name: "get_customer_stats",
        description: "獲取客戶統計信息",
        inputSchema: {
            type: "object",
            properties: {
                customer_name: { type: "string", description: "客戶名稱" },
                date_from: {
                    type: "string",
                    format: "date",
                    description: "開始日期",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description: "結束日期",
                },
                status: {
                    type: "string",
                    enum: ["pending", "processing", "completed", "cancelled"],
                    description: "訂單狀態",
                },
                limit: { type: "integer", description: "返回結果數量限制" },
            },
        },
    },
    {
        name: "get_order_analytics",
        description: "獲取訂單分析數據",
        inputSchema: {
            type: "object",
            properties: {
                analytics_type: {
                    type: "string",
                    enum: ["daily", "monthly", "status", "product"],
                    description: "分析類型",
                },
                date_from: {
                    type: "string",
                    format: "date",
                    description: "開始日期",
                },
                date_to: {
                    type: "string",
                    format: "date",
                    description: "結束日期",
                },
                status: {
                    type: "string",
                    enum: ["pending", "processing", "completed", "cancelled"],
                    description: "訂單狀態",
                },
                limit: { type: "integer", description: "返回結果數量限制" },
            },
        },
    },
    {
        name: "get_products",
        description: "獲取產品信息",
        inputSchema: {
            type: "object",
            properties: {
                name: { type: "string", description: "產品名稱" },
                category: { type: "string", description: "產品類別" },
                min_price: { type: "number", description: "最低價格" },
                max_price: { type: "number", description: "最高價格" },
                stock: { type: "integer", description: "庫存數量" },
                limit: { type: "integer", description: "返回結果數量限制" },
            },
        },
    },
    {
        name: "send_excel_email",
        description: "發送Excel郵件",
        inputSchema: {
            type: "object",
            properties: {
                type: {
                    type: "string",
                    enum: ["orders", "products"],
                    description: "數據類型",
                },
                email: {
                    type: "string",
                    format: "email",
                    description: "收件人郵箱",
                },
                subject: { type: "string", description: "郵件主題" },
                message: { type: "string", description: "郵件內容" },
                filters: { type: "object", description: "數據過濾條件" },
                limit: { type: "integer", description: "導出數據數量限制" },
            },
            required: ["type", "email"],
        },
    },
];

// Define tool instances
const toolInstances = {
    get_orders: new GetOrdersTool(),
    get_customer_stats: new GetCustomerStatsTool(),
    get_order_analytics: new GetOrderAnalyticsTool(),
    get_products: new GetProductsTool(),
    send_excel_email: new SendExcelEmailTool(),
};

// Create server instance
const server = new Server(
    {
        name: "itsuki-mcp-server", 
        version: "1.0.0",
    },
    {
        capabilities: {
            tools: {},
        },
    }
);

// Register tools using proper MCP SDK schemas
server.setRequestHandler(ListToolsRequestSchema, async () => {
    console.log("ListToolsRequestSchema handler called");
    return { tools: toolsList };
});

server.setRequestHandler(CallToolRequestSchema, async (request, extra) => {
    console.log("CallToolRequestSchema handler called:", request);
    const { name, arguments: args } = request.params;
    
    const tool = toolInstances[name];
    if (!tool) {
        throw new Error(`Tool "${name}" not found`);
    }
    
    try {
        const result = await tool.execute(args || {});
        return {
            content: [
                {
                    type: "text", 
                    text: JSON.stringify(result, null, 2)
                }
            ]
        };
    } catch (error) {
        console.error(`Error executing tool ${name}:`, error);
        throw new Error(`Tool execution failed: ${error.message}`);
    }
});

// Transport lookup for session management
const transports = new Map();

// Express app setup
const app = express();
app.use(express.json());

// SSE endpoint for client connections
app.get("/sse", async (req, res) => {
    console.log("SSE connection request received");
    
    // Create SSE transport - let it handle the headers
    const transport = new SSEServerTransport("/message", res);
    console.log("New SSE transport created with session ID:", transport.sessionId);
    
    // Store transport
    transports.set(transport.sessionId, transport);
    
    // Handle connection close
    res.on('close', () => {
        console.log("SSE connection closed for session:", transport.sessionId);
        transports.delete(transport.sessionId);
    });
    
    // Connect server to transport
    try {
        await server.connect(transport);
        console.log("Server connected to SSE transport successfully");
    } catch (error) {
        console.error("Error connecting server to SSE transport:", error);
        if (!res.headersSent) {
            res.status(500).json({ error: "Failed to establish SSE connection" });
        }
    }
});

// Message endpoint for client requests
app.post("/message", async (req, res) => {
    console.log("Message request received:", req.body);
    
    const sessionId = req.query.sessionId;
    if (!sessionId) {
        return res.status(400).json({ error: "Missing sessionId" });
    }
    
    const transport = transports.get(sessionId);
    if (!transport) {
        return res.status(400).json({ error: "Transport not found for sessionId" });
    }
    
    try {
        // Let the transport handle the message properly with the server
        await transport.handlePostMessage(req, res, req.body);
    } catch (error) {
        console.error("Error handling post message:", error);
        if (!res.headersSent) {
            res.status(500).json({ error: "Internal server error" });
        }
    }
});

// Health check endpoint
app.get("/health", (req, res) => {
    res.json({ status: "ok", timestamp: new Date().toISOString() });
});

// Start the server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`MCP Server listening on port ${PORT}`);
    console.log(`SSE endpoint: http://localhost:${PORT}/sse`);
    console.log(`Message endpoint: http://localhost:${PORT}/message`);
    console.log(`Health check: http://localhost:${PORT}/health`);
});
