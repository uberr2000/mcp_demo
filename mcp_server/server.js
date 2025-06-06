import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import express from "express";
import { SSEServerTransport } from "@modelcontextprotocol/sdk/server/sse.js";
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
        input_schema: {
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
        input_schema: {
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
        input_schema: {
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
        input_schema: {
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
        input_schema: {
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

// Define tool instances
const toolInstances = {
    get_orders: new GetOrdersTool(),
    get_customer_stats: new GetCustomerStatsTool(),
    get_order_analytics: new GetOrderAnalyticsTool(),
    get_products: new GetProductsTool(),
    send_excel_email: new SendExcelEmailTool(),
};

// Handle request events
server.onRequest = async (request) => {
    console.log("Request received:", request.method);
    
    if (request.method === "tools/list") {
        console.log("tools/list request received");
        return { tools: toolsList };
    }
    
    if (request.method === "tools/call") {
        console.log("tools/call request received:", request);
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
    }
    
    throw new Error(`Unknown method: ${request.method}`);
};

// to support multiple simultaneous connections we have a lookup object from
// sessionId to transport
const transports = {};

const app = express();
app.use(express.json());

const router = express.Router();

// endpoint for the client to use for sending messages
const POST_ENDPOINT = "/messages";

router.post(POST_ENDPOINT, async (req, res) => {
    console.log("message request received: ", req.body);
    // when client sends messages with `SSEClientTransport`,
    // the sessionId will be atomically set as query parameter.
    const sessionId = req.query.sessionId;

    if (typeof sessionId != "string") {
        res.status(400).send({ messages: "Bad session id." });
        return;
    }
    const transport = transports[sessionId];
    if (!transport) {
        res.status(400).send({ messages: "No transport found for sessionId." });
        return;
    }

    await transport.handlePostMessage(req, res, req.body);
    return;
});

// initialization:
// create a new transport to connect and
// send an endpoint event containing a URI for the client to use for sending messages
router.get("/connect", async (req, res) => {
    console.log("connection request received");
    // tells the client to send messages to the `POST_ENDPOINT`
    const transport = new SSEServerTransport(POST_ENDPOINT, res);
    console.log("new transport created with session id: ", transport.sessionId);

    transports[transport.sessionId] = transport;

    res.on("close", () => {
        console.log("SSE connection closed");
        delete transports[transport.sessionId];
    });

    await server.connect(transport);

    // an example of a server-sent-event (message) to client
    await sendMessages(transport);

    return;
});

async function sendMessages(transport) {
    try {
        await transport.send({
            jsonrpc: "2.0",
            method: "sse/connection",
            params: { message: "Stream started" },
        });
        console.log("Stream started");

        let messageCount = 0;
        const interval = setInterval(async () => {
            messageCount++;

            const message = `Message ${messageCount} at ${new Date().toISOString()}`;

            try {
                await transport.send({
                    jsonrpc: "2.0",
                    method: "sse/message",
                    params: { data: message },
                });

                console.log(`Sent: ${message}`);

                if (messageCount === 2) {
                    clearInterval(interval);
                    await transport.send({
                        jsonrpc: "2.0",
                        method: "sse/complete",
                        params: { message: "Stream completed" },
                    });
                    console.log("Stream completed");
                }
            } catch (error) {
                console.error("Error sending message:", error);
                clearInterval(interval);
            }
        }, 1000);
    } catch (error) {
        console.error("Error in startSending:", error);
    }
}

app.use("/", router);

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`MCP Streamable HTTP Server listening on port ${PORT}`);
});
