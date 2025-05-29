# MCP Laravel Demo - n8n SSE 整合說明

## ✅ 系統狀態：完全就緒

系統已完全配置好 SSE (Server-Sent Events) 協議支援，可與 n8n MCP 客戶端完美整合。

## 🚀 解決方案：MCP stdio 服務器

當您運行 `php artisan mcp:server` 時沒有輸出，這是正常的行為。該命令以 stdio 模式運行，等待 JSON-RPC 輸入。

### 測試方法：

**方法 1: 使用管道輸入**
```bash
echo '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}' | php artisan mcp:server
```

**方法 2: 使用 debug 模式查看詳細信息**
```bash
php artisan mcp:server --debug
```

**方法 3: 使用我們的 SSE 端點**
```bash
curl -X POST http://localhost:8000/mcp/sse -H "Content-Type: application/json" -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'
```

### ✅ 工作示例：

```bash
# 獲取工具列表
echo '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}' | php artisan mcp:server

# 獲取訂單
echo '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_orders","arguments":{"limit":3}},"id":2}' | php artisan mcp:server

# 獲取產品
echo '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_products","arguments":{"limit":3}},"id":3}' | php artisan mcp:server
```

## MCP 服務端點

### 🚀 SSE 端點 (推薦給 n8n 使用)
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

### 📡 Stdio 端點 (標準 MCP 協議)
```bash
php artisan mcp:server
```

## n8n MCP 客戶端配置

### 方法 1: 使用 stdio 傳輸 ⭐
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

### 方法 2: 使用 SSE 傳輸 ⭐
如果 n8n 支援 HTTP 調用 MCP：
- 端點: `http://localhost:8000/mcp/sse`
- 方法: POST
- 內容類型: application/json
- 格式: JSON-RPC 2.0

## 可用的 MCP 工具 🛠️

1. **get_orders** - 查詢訂單
   - 參數: limit, status, start_date, end_date, amount_min, amount_max, product_name, sort_by, sort_direction

2. **get_products** - 查詢產品
   - 參數: limit, category, min_price, max_price

3. **get_customer_stats** - 客戶統計
   - 參數: limit

4. **get_order_analytics** - 訂單分析
   - 參數: period, group_by, start_date, end_date

## 測試範例 ✅

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

## 聊天整合 💬

聊天訊息會發送到 n8n webhook:
`https://autoflow.ink.net.tw/webhook/5697d0a1-9135-4f07-8c6e-69e97f2844c8/chat`

包含的資訊：
- `message`: 用戶消息
- `mcp_sse_endpoint`: SSE 端點 URL
- `mcp_stdio_endpoint`: stdio 端點資訊
- `mcp_config`: MCP 配置資訊
- `available_tools`: 可用工具列表

n8n 可以使用這些資訊來設置 MCP 客戶端並調用相應的工具。

## 測試驗證 ✅

所有功能已通過測試：
- ✅ SSE 端點正常運作
- ✅ JSON-RPC 2.0 協議相容
- ✅ 4 個 MCP 工具正常執行
- ✅ 錯誤處理完善
- ✅ 資料庫連接正常

系統準備就緒，可以與 n8n 整合！
