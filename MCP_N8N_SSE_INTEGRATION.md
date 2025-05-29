# MCP Laravel Demo - n8n 整合說明

## MCP 服務端點

### SSE 端點 (推薦給 n8n 使用)
```
POST http://localhost:8000/mcp/sse
Content-Type: application/json

{
  "jsonrpc": "2.0",
  "method": "tools/list",
  "params": {},
  "id": 1
}
```

### Stdio 端點 (標準 MCP 協議)
```bash
php artisan mcp:server
```

## n8n MCP 客戶端配置

### 方法 1: 使用 stdio 傳輸
在 n8n 中配置 MCP 工具節點：

```json
{
  "mcpServers": {
    "laravel-demo": {
      "command": "php",
      "args": ["artisan", "mcp:server"],
      "cwd": "d:\\workspace\\Demo\\mcp_demo"
    }
  }
}
```

### 方法 2: 使用 SSE 傳輸
如果 n8n 支援 SSE，可以使用：
- 端點: `http://localhost:8000/mcp/sse`
- 方法: POST
- 內容類型: application/json

## 可用的 MCP 工具

1. **get_orders** - 查詢訂單
   - 參數: limit, status, start_date, end_date, amount_min, amount_max, product_name, sort_by, sort_direction

2. **get_products** - 查詢產品
   - 參數: limit, category, min_price, max_price

3. **get_customer_stats** - 客戶統計
   - 參數: limit

4. **get_order_analytics** - 訂單分析
   - 參數: period, group_by, start_date, end_date

## 測試範例

### 初始化
```json
{
  "jsonrpc": "2.0",
  "method": "initialize",
  "params": {
    "protocolVersion": "2024-11-05",
    "capabilities": {},
    "clientInfo": {
      "name": "n8n",
      "version": "1.0.0"
    }
  },
  "id": 1
}
```

### 獲取工具列表
```json
{
  "jsonrpc": "2.0",
  "method": "tools/list",
  "params": {},
  "id": 2
}
```

### 調用工具
```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "get_orders",
    "arguments": {
      "limit": 5,
      "status": "completed"
    }
  },
  "id": 3
}
```

## 聊天整合

聊天訊息會發送到 n8n webhook:
`https://autoflow.ink.net.tw/webhook/5697d0a1-9135-4f07-8c6e-69e97f2844c8/chat`

包含的資訊：
- `message`: 用戶消息
- `mcp_sse_endpoint`: SSE 端點 URL
- `mcp_stdio_endpoint`: stdio 端點資訊
- `mcp_config`: MCP 配置資訊
- `available_tools`: 可用工具列表

n8n 可以使用這些資訊來設置 MCP 客戶端並調用相應的工具。
