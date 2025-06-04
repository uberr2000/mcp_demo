# Complete MCP Tools Schema Documentation

## Overview
This MCP server provides 5 tools for order management, analytics, and data export. All order-related tools support the "all" status parameter for comprehensive data access.

## Available Tools

### 1. GET_ORDERS
**Name:** `get_orders`  
**Description:** 從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢  
**All Status Support:** ✅ Yes

**Parameters:**
- `transaction_id` (string, optional): 交易ID（可部分匹配）
- `customer_name` (string, optional): 客戶姓名（可部分匹配）
- `status` (string, optional): 訂單狀態（pending, completed, cancelled, **all**）
- `product_name` (string, optional): 產品名稱（可部分匹配）
- `min_amount` (number, optional): 最小金額
- `max_amount` (number, optional): 最大金額
- `date_from` (date, optional): 開始日期 (YYYY-MM-DD)
- `date_to` (date, optional): 結束日期 (YYYY-MM-DD)
- `limit` (integer, optional): 返回結果數量限制 (1-100, default: 10)

### 2. GET_PRODUCTS
**Name:** `get_products`  
**Description:** 從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢  
**All Status Support:** ❌ N/A (No status field)

**Parameters:**
- `name` (string, optional): 產品名稱（可部分匹配）
- `category` (string, optional): 產品類別
- `min_price` (number, optional): 最小價格
- `max_price` (number, optional): 最大價格
- `active` (boolean, optional): 是否為活躍產品
- `limit` (integer, optional): 返回結果數量限制 (1-100, default: 10)

### 3. GET_CUSTOMER_STATS
**Name:** `get_customer_stats`  
**Description:** Get customer statistics including order count, total spending, average order amount, etc.  
**All Status Support:** ✅ Yes

**Parameters:**
- `customer_name` (string, optional): Customer name (partial match supported)
- `date_from` (date, optional): Statistics start date (YYYY-MM-DD)
- `date_to` (date, optional): Statistics end date (YYYY-MM-DD)
- `status` (string, optional): Order status filter (pending, processing, completed, cancelled, refunded, **all**)
- `limit` (integer, optional): Limit number of customers returned (1-100, default: 20)

### 4. GET_ORDER_ANALYTICS
**Name:** `get_order_analytics`  
**Description:** 獲取訂單分析資料，包括按日期、狀態、產品的統計分析  
**All Status Support:** ✅ Yes

**Parameters:**
- `analytics_type` (enum, optional): 分析類型 (daily, status, product, monthly, default: daily)
- `date_from` (date, optional): 分析開始日期 (YYYY-MM-DD)
- `date_to` (date, optional): 分析結束日期 (YYYY-MM-DD)
- `status` (string, optional): 篩選特定訂單狀態 (pending, completed, cancelled, **all**)
- `limit` (integer, optional): 返回結果數量限制 (1-100, default: 30)

### 5. SEND_EXCEL_EMAIL
**Name:** `send_excel_email`  
**Description:** 生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱  
**All Status Support:** ✅ Yes (in filters)

**Required Parameters:**
- `type` (enum): 要導出的數據類型 (orders, products)
- `email` (email): 接收Excel文件的郵箱地址

**Optional Parameters:**
- `subject` (string): 郵件主題
- `message` (string): 郵件內容
- `filters` (object): 篩選條件
  - `status` (enum): 訂單狀態篩選 (pending, processing, completed, cancelled, refunded, **all**)
  - `customer_name` (string): 客戶姓名篩選
  - `product_name` (string): 產品名稱篩選
  - `date_from` (date): 開始日期
  - `date_to` (date): 結束日期
  - `category` (string): 產品類別篩選
  - `active` (boolean): 是否啟用篩選
- `limit` (integer): 導出記錄數量限制 (1-10000, default: 1000)

## "All" Status Feature

**Supported Tools:** 4 out of 5 tools support "all" status
- ✅ get_orders
- ✅ get_customer_stats  
- ✅ get_order_analytics
- ✅ send_excel_email
- ❌ get_products (N/A - no status field)

**Usage:** Set `status: "all"` to include orders of all statuses (pending, completed, cancelled, etc.) in your queries.

## n8n Integration

**MCP Server Endpoint:** `https://mcp.ink.net.tw/mcp/sse`

**Example Queries:**

```json
// Get customer stats for all orders
{
  "customer_name": "何淑儀",
  "status": "all",
  "limit": 1
}

// Daily analytics across all statuses
{
  "analytics_type": "daily",
  "status": "all",
  "limit": 30
}

// Export all orders to Excel
{
  "type": "orders",
  "email": "user@example.com",
  "filters": {
    "status": "all"
  }
}
```

All tools are production-ready and fully compatible with OpenAI and n8n integrations! 🚀
