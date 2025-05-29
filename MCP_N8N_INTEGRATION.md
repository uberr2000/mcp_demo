# MCP Service for n8n Integration

這個 Laravel MCP (Model Context Protocol) Service 提供了完整的訂單和產品數據查詢功能，專門設計用於與 n8n 工作流程整合，讓 AI 可以透過 MCP 協議來查詢資料。

## 🚀 快速開始

### 1. 啟動 Laravel 服務
```bash
php artisan serve
```
服務將運行在: http://127.0.0.1:8000

### 2. MCP 端點
- **Base URL**: `http://127.0.0.1:8000`
- **API Base URL**: `http://127.0.0.1:8000/api/mcp`

### 3. 主要端點

| 端點 | 方法 | 描述 |
|------|------|------|
| `/mcp/info` | GET | 獲取 MCP 服務資訊 |
| `/mcp/tools` | GET | 獲取可用工具列表 |
| `/mcp/tools/call` | POST | 調用 MCP 工具 |
| `/mcp/ping` | GET | 健康檢查 |
| `/mcp/initialize` | POST | MCP 協議初始化 |

## 🛠️ 可用工具

### 1. get_orders - 訂單查詢
查詢訂單數據，支援多種篩選條件。

**參數:**
```json
{
  "transaction_id": "TXN000001",          // 交易編號
  "customer_name": "陳大明",               // 客戶姓名
  "status": "completed",                   // 訂單狀態
  "product_name": "可口可樂",              // 產品名稱
  "min_amount": 50,                        // 最小金額
  "max_amount": 500,                       // 最大金額
  "date_from": "2024-01-01",              // 開始日期
  "date_to": "2024-12-31",                // 結束日期
  "limit": 10,                            // 返回數量限制
  "sort_by": "created_at",                // 排序欄位
  "sort_direction": "desc"                // 排序方向
}
```

### 2. get_products - 產品查詢
查詢產品資訊。

**參數:**
```json
{
  "name": "可口可樂",                      // 產品名稱
  "category": "飲料",                     // 產品類別
  "limit": 10                             // 返回數量限制
}
```

### 3. get_customer_stats - 客戶統計
獲取客戶訂單統計資訊。

**參數:**
```json
{
  "customer_name": "陳大明",               // 客戶姓名（可選）
  "status": "completed"                    // 訂單狀態篩選（可選）
}
```

### 4. get_order_analytics - 訂單分析
獲取訂單分析數據。

**參數:**
```json
{
  "date_from": "2024-01-01",              // 開始日期
  "date_to": "2024-12-31",                // 結束日期
  "group_by": "day"                       // 分組方式: day, week, month, status, product
}
```

## 📋 n8n 整合範例

### HTTP Request 節點設定

#### 1. 獲取工具列表
```
Method: GET
URL: http://127.0.0.1:8000/mcp/tools
```

#### 2. 調用工具
```
Method: POST
URL: http://127.0.0.1:8000/mcp/tools/call
Content-Type: application/json
Body:
{
  "name": "get_orders",
  "arguments": {
    "status": "completed",
    "limit": 5
  }
}
```

### n8n 工作流程範例

```json
{
  "nodes": [
    {
      "parameters": {
        "url": "http://127.0.0.1:8000/mcp/tools/call",
        "options": {
          "bodyContentType": "json"
        },
        "body": {
          "name": "get_orders",
          "arguments": {
            "status": "{{ $json.status }}",
            "customer_name": "{{ $json.customer_name }}",
            "limit": 10
          }
        }
      },
      "type": "n8n-nodes-base.httpRequest",
      "name": "Query Orders"
    }
  ]
}
```

## 🔧 測試 MCP Service

訪問測試頁面: http://127.0.0.1:8000/mcp-test

這個頁面提供了：
- MCP 服務資訊顯示
- 可用工具列表
- 預設測試按鈕
- 自定義工具調用界面
- 實時結果顯示

## 🌟 AI 查詢範例

### 範例 1: 查詢最近完成的訂單
```json
{
  "name": "get_orders",
  "arguments": {
    "status": "completed",
    "sort_by": "created_at",
    "sort_direction": "desc",
    "limit": 5
  }
}
```

### 範例 2: 分析特定客戶的購買行為
```json
{
  "name": "get_customer_stats",
  "arguments": {
    "customer_name": "陳大明"
  }
}
```

### 範例 3: 獲取產品銷售排行
```json
{
  "name": "get_order_analytics",
  "arguments": {
    "group_by": "product"
  }
}
```

### 範例 4: 查詢特定產品的訂單
```json
{
  "name": "get_orders",
  "arguments": {
    "product_name": "可口可樂",
    "date_from": "2024-01-01",
    "limit": 10
  }
}
```

## 🚨 錯誤處理

MCP Service 會返回標準的錯誤響應：

```json
{
  "error": {
    "code": -32601,
    "message": "Tool 'invalid_tool' not found"
  }
}
```

常見錯誤代碼：
- `-32601`: 工具不存在
- `-32602`: 參數錯誤
- `-32603`: 內部錯誤

## 📊 資料庫結構

### Products 表
- id, name, description, price, stock_quantity, category, created_at, updated_at

### Orders 表  
- id, transaction_id, name, amount, status, product_id, quantity, created_at, updated_at

### 資料關係
- Order belongs to Product
- Product has many Orders

## 🔒 安全注意事項

1. 在生產環境中，建議添加 API 認證
2. 實施 rate limiting
3. 驗證輸入參數
4. 記錄 API 調用日誌

## 📞 支援

如有問題，請檢查：
1. Laravel 服務是否正常運行
2. 資料庫連接是否正常
3. n8n HTTP Request 節點設定是否正確
4. 使用測試頁面驗證 MCP 服務功能
