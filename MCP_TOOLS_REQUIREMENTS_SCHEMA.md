# MCP Tools Schema with Field Requirements

## 🎯 Field Requirement Legend
- 🔴 **REQUIRED** - Must be provided or API call will fail
- 🟡 **OPTIONAL** - Can be omitted, tool will use defaults or skip filtering
- ✅ **Has Default** - Optional field with default value

---

## 1. get_orders Tool

**Description**: 從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢

### Parameters:
- `transaction_id` (string, 🟡 **OPTIONAL**): 訂單交易ID (支援部分匹配)
- `customer_name` (string, 🟡 **OPTIONAL**): 客戶姓名 (支援部分匹配)
- `status` (string, 🟡 **OPTIONAL**): 訂單狀態
  - 可選值: `"pending"`, `"processing"`, `"completed"`, `"cancelled"`, `"refunded"`, `"all"`
  - 預設: 不篩選狀態
- `product_name` (string, 🟡 **OPTIONAL**): 產品名稱篩選 (支援部分匹配)
- `min_amount` (number, 🟡 **OPTIONAL**): 最低金額篩選
- `max_amount` (number, 🟡 **OPTIONAL**): 最高金額篩選
- `date_from` (string, 🟡 **OPTIONAL**): 開始日期 (YYYY-MM-DD格式)
- `date_to` (string, 🟡 **OPTIONAL**): 結束日期 (YYYY-MM-DD格式)
- `limit` (integer, 🟡 **OPTIONAL**, ✅ **預設: 10**): 返回結果數量 (範圍: 1-100)

**Required Fields**: ⚠️ **NONE** - All parameters are optional

### Example Usage:
```json
{
  "name": "get_orders",
  "arguments": {
    "status": "completed",    // 🟡 OPTIONAL
    "limit": 20               // 🟡 OPTIONAL
  }
}
```

---

## 2. get_products Tool

**Description**: 從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍、庫存進行查詢

### Parameters:
- `name` (string, 🟡 **OPTIONAL**): 產品名稱 (支援部分匹配)
- `category` (string, 🟡 **OPTIONAL**): 產品類別
- `min_price` (number, 🟡 **OPTIONAL**): 最低價格篩選
- `max_price` (number, 🟡 **OPTIONAL**): 最高價格篩選
- `stock_quantity` (integer, 🟡 **OPTIONAL**): 最低庫存數量篩選
- `limit` (integer, 🟡 **OPTIONAL**, ✅ **預設: 10**): 返回結果數量 (範圍: 1-100)

**Required Fields**: ⚠️ **NONE** - All parameters are optional

### Example Usage:
```json
{
  "name": "get_products",
  "arguments": {
    "category": "飲料",       // 🟡 OPTIONAL
    "min_price": 10,         // 🟡 OPTIONAL
    "limit": 15              // 🟡 OPTIONAL
  }
}
```

---

## 3. get_customer_stats Tool

**Description**: 獲取客戶統計資訊，包括訂單數量、總消費、平均訂單金額等

### Parameters:
- `customer_name` (string, 🟡 **OPTIONAL**): 客戶姓名 (支援部分匹配)
- `date_from` (string, 🟡 **OPTIONAL**): 統計開始日期 (YYYY-MM-DD格式)
- `date_to` (string, 🟡 **OPTIONAL**): 統計結束日期 (YYYY-MM-DD格式)
- `status` (string, 🟡 **OPTIONAL**): 訂單狀態篩選
  - 可選值: `"pending"`, `"processing"`, `"completed"`, `"cancelled"`, `"refunded"`, `"all"`
- `limit` (integer, 🟡 **OPTIONAL**, ✅ **預設: 20**): 返回客戶數量 (範圍: 1-100)

**Required Fields**: ⚠️ **NONE** - All parameters are optional

### Example Usage:
```json
{
  "name": "get_customer_stats",
  "arguments": {
    "customer_name": "張",    // 🟡 OPTIONAL
    "status": "completed",   // 🟡 OPTIONAL
    "limit": 10              // 🟡 OPTIONAL
  }
}
```

---

## 4. get_order_analytics Tool

**Description**: 獲取訂單分析資料，包括按日期、狀態、產品的統計分析

### Parameters:
- `analytics_type` (string, 🟡 **OPTIONAL**, ✅ **預設: "daily"**): 分析類型
  - 可選值: `"daily"`, `"weekly"`, `"monthly"`, `"status"`, `"product"`
- `date_from` (string, 🟡 **OPTIONAL**): 分析開始日期 (YYYY-MM-DD格式)
- `date_to` (string, 🟡 **OPTIONAL**): 分析結束日期 (YYYY-MM-DD格式)
- `status` (string, 🟡 **OPTIONAL**): 訂單狀態篩選
  - 可選值: `"pending"`, `"processing"`, `"completed"`, `"cancelled"`, `"refunded"`, `"all"`
- `limit` (integer, 🟡 **OPTIONAL**, ✅ **預設: 30**): 返回結果數量 (範圍: 1-100)

**Required Fields**: ⚠️ **NONE** - All parameters are optional

### Example Usage:
```json
{
  "name": "get_order_analytics",
  "arguments": {
    "analytics_type": "monthly", // 🟡 OPTIONAL
    "status": "completed",       // 🟡 OPTIONAL
    "date_from": "2025-05-01"    // 🟡 OPTIONAL
  }
}
```

---

## 5. send_excel_email Tool ⚡

**Description**: 生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱

### Parameters:
- `type` (string, 🔴 **REQUIRED**): 導出類型
  - 可選值: `"orders"`, `"products"`
  - ⚠️ **必須提供此欄位，否則API會失敗**
- `email` (string, 🔴 **REQUIRED**): 收件人郵箱地址
  - 必須是有效的email格式 (例: user@example.com)
  - ⚠️ **必須提供此欄位，否則API會失敗**
- `subject` (string, 🟡 **OPTIONAL**): 郵件主題
  - 預設: 根據導出類型自動生成
- `message` (string, 🟡 **OPTIONAL**): 郵件正文內容
  - 預設: 自動生成描述性內容
- `status` (string, 🟡 **OPTIONAL**): 訂單狀態篩選 (僅適用於訂單導出)
  - 可選值: `"pending"`, `"processing"`, `"completed"`, `"cancelled"`, `"refunded"`, `"all"`
- `customer_name` (string, 🟡 **OPTIONAL**): 客戶姓名篩選 (僅適用於訂單導出)
- `product_name` (string, 🟡 **OPTIONAL**): 產品名稱篩選
- `category` (string, 🟡 **OPTIONAL**): 產品類別篩選 (僅適用於產品導出)
- `date_from` (string, 🟡 **OPTIONAL**): 開始日期篩選 (YYYY-MM-DD格式)
- `date_to` (string, 🟡 **OPTIONAL**): 結束日期篩選 (YYYY-MM-DD格式)
- `limit` (integer, 🟡 **OPTIONAL**, ✅ **預設: 1000**): 導出記錄數量 (最大: 10000)

**Required Fields**: 🔴 **REQUIRED**: `type`, `email`

### Example Usage:
```json
{
  "name": "send_excel_email",
  "arguments": {
    "type": "orders",                        // 🔴 REQUIRED
    "email": "terry.hk796@gmail.com",        // 🔴 REQUIRED  
    "subject": "張美麗的訂單紀錄",            // 🟡 OPTIONAL
    "customer_name": "張美麗",                // 🟡 OPTIONAL
    "status": "all"                          // 🟡 OPTIONAL
  }
}
```

---

## 📋 Requirements Summary Table

| Tool Name | Required Fields | Optional Fields Count | Notes |
|-----------|----------------|----------------------|-------|
| `get_orders` | **NONE** | 9 | All filtering is optional |
| `get_products` | **NONE** | 6 | All filtering is optional |
| `get_customer_stats` | **NONE** | 5 | All filtering is optional |
| `get_order_analytics` | **NONE** | 5 | All filtering is optional |
| `send_excel_email` | **`type`, `email`** | 9 | Only tool with required fields |

---

## ✅ Valid API Call Examples

### 1. Minimal Valid Calls (Required Fields Only)
```json
// get_orders - no required fields
{
  "name": "get_orders",
  "arguments": {}
}

// send_excel_email - minimal with required fields
{
  "name": "send_excel_email",
  "arguments": {
    "type": "orders",                    // 🔴 REQUIRED
    "email": "user@example.com"          // 🔴 REQUIRED
  }
}
```

### 2. Complete Valid Calls (With Optional Fields)
```json
// get_orders with filtering
{
  "name": "get_orders",
  "arguments": {
    "customer_name": "張美麗",            // 🟡 OPTIONAL
    "status": "completed",               // 🟡 OPTIONAL
    "date_from": "2025-05-01",           // 🟡 OPTIONAL
    "limit": 20                          // 🟡 OPTIONAL
  }
}

// send_excel_email with filtering
{
  "name": "send_excel_email",
  "arguments": {
    "type": "orders",                    // 🔴 REQUIRED
    "email": "terry.hk796@gmail.com",    // 🔴 REQUIRED
    "subject": "訂單報告",                // 🟡 OPTIONAL
    "customer_name": "張美麗",            // 🟡 OPTIONAL
    "status": "completed",               // 🟡 OPTIONAL
    "date_from": "2025-05-01"            // 🟡 OPTIONAL
  }
}
```

---

## ❌ Invalid API Call Examples

### Missing Required Fields
```json
// ❌ send_excel_email - missing 'type' field
{
  "name": "send_excel_email",
  "arguments": {
    "email": "user@example.com",         // ✅ REQUIRED (provided)
    "customer_name": "張美麗"            // 🟡 OPTIONAL
    // ❌ Missing 'type' field (REQUIRED)
  }
}
// Error: "發送郵件失敗：Undefined array key 'type'"

// ❌ send_excel_email - missing 'email' field  
{
  "name": "send_excel_email",
  "arguments": {
    "type": "orders",                    // ✅ REQUIRED (provided)
    "customer_name": "張美麗"            // 🟡 OPTIONAL
    // ❌ Missing 'email' field (REQUIRED)
  }
}
// Error: "發送郵件失敗：Undefined array key 'email'"
```

---

## 🎯 Key Takeaways

1. **Only `send_excel_email` has required fields**: `type` and `email`
2. **All other tools are completely flexible** - no required fields
3. **Optional fields enable filtering** - use them to narrow down results
4. **Default values exist** for `limit` parameters in most tools
5. **Always check field requirements** before making API calls

This documentation should prevent the "Undefined array key" errors you encountered! 🚀
