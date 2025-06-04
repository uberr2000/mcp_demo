# OpenAI-Compatible MCP Tools Schema (Corrected)

## Complete OpenAI Function Schema

```json
{
  "tools": [
    {
      "type": "function",
      "function": {
        "name": "get_orders",
        "description": "從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢",
        "parameters": {
          "type": "object",
          "properties": {
            "transaction_id": {
              "type": "string",
              "description": "訂單交易ID - Optional field"
            },
            "customer_name": {
              "type": "string",
              "description": "客戶姓名 - Optional field"
            },
            "status": {
              "type": "string",
              "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
              "description": "訂單狀態 - Optional field. Use 'all' to include all statuses"
            },
            "product_name": {
              "type": "string",
              "description": "產品名稱 - Optional field"
            },
            "min_amount": {
              "type": "number",
              "minimum": 0,
              "description": "最低金額過濾 - Optional field (use 0 to ignore this filter)"
            },
            "max_amount": {
              "type": "number",
              "minimum": 0,
              "description": "最高金額過濾 - Optional field (use 0 to ignore this filter)"
            },
            "date_from": {
              "type": "string",
              "format": "date",
              "description": "開始日期 (YYYY-MM-DD format) - Optional field"
            },
            "date_to": {
              "type": "string",
              "format": "date",
              "description": "結束日期 (YYYY-MM-DD format) - Optional field"
            },
            "limit": {
              "type": "integer",
              "minimum": 1,
              "maximum": 100,
              "default": 10,
              "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)"
            }
          },
          "required": [],
          "additionalProperties": false
        }
      }
    },
    {
      "type": "function",
      "function": {
        "name": "get_products",
        "description": "從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍、庫存進行查詢",
        "parameters": {
          "type": "object",
          "properties": {
            "name": {
              "type": "string",
              "description": "產品名稱 - Optional field"
            },
            "category": {
              "type": "string",
              "description": "產品類別 - Optional field"
            },
            "min_price": {
              "type": "number",
              "minimum": 0,
              "description": "最低價格 - Optional field"
            },
            "max_price": {
              "type": "number",
              "minimum": 0,
              "description": "最高價格 - Optional field"
            },
            "stock_quantity": {
              "type": "integer",
              "minimum": 0,
              "description": "最低庫存數量 - Optional field"
            },
            "limit": {
              "type": "integer",
              "minimum": 1,
              "maximum": 100,
              "default": 10,
              "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)"
            }
          },
          "required": [],
          "additionalProperties": false
        }
      }
    },
    {
      "type": "function",
      "function": {
        "name": "get_customer_stats",
        "description": "Get customer statistics including order count, total spending, average order amount, etc.",
        "parameters": {
          "type": "object",
          "properties": {
            "customer_name": {
              "type": "string",
              "description": "Customer name (partial match supported) - Optional field"
            },
            "date_from": {
              "type": "string",
              "format": "date",
              "description": "Statistics start date (YYYY-MM-DD) - Optional field"
            },
            "date_to": {
              "type": "string",
              "format": "date",
              "description": "Statistics end date (YYYY-MM-DD) - Optional field"
            },
            "status": {
              "type": "string",
              "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
              "description": "Order status filter - Optional field. Use 'all' to include all statuses"
            },
            "limit": {
              "type": "integer",
              "minimum": 1,
              "maximum": 100,
              "default": 20,
              "description": "Limit number of customers returned - Optional field (default: 20, range: 1-100)"
            }
          },
          "required": [],
          "additionalProperties": false
        }
      }
    },
    {
      "type": "function",
      "function": {
        "name": "get_order_analytics",
        "description": "獲取訂單分析資料，包括按日期、狀態、產品的統計分析",
        "parameters": {
          "type": "object",
          "properties": {
            "analytics_type": {
              "type": "string",
              "enum": ["daily", "weekly", "monthly", "status", "product"],
              "default": "daily",
              "description": "分析類型：daily(每日), weekly(每週), monthly(每月), status(按狀態), product(按產品) - Optional field (default: daily)"
            },
            "date_from": {
              "type": "string",
              "format": "date",
              "description": "分析開始日期 (YYYY-MM-DD format) - Optional field"
            },
            "date_to": {
              "type": "string",
              "format": "date",
              "description": "分析結束日期 (YYYY-MM-DD format) - Optional field"
            },
            "status": {
              "type": "string",
              "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
              "description": "訂單狀態過濾 - Optional field. Use 'all' to include all statuses"
            },
            "limit": {
              "type": "integer",
              "minimum": 1,
              "maximum": 100,
              "default": 30,
              "description": "返回結果數量限制 - Optional field (default: 30, range: 1-100)"
            }
          },
          "required": [],
          "additionalProperties": false
        }
      }
    },
    {
      "type": "function",
      "function": {
        "name": "send_excel_email",
        "description": "生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱",
        "parameters": {
          "type": "object",
          "properties": {
            "type": {
              "type": "string",
              "enum": ["orders", "products"],
              "description": "導出類型：orders(訂單) 或 products(產品) - Required field"
            },
            "email": {
              "type": "string",
              "format": "email",
              "description": "收件人郵箱地址 - Required field"
            },
            "subject": {
              "type": "string",
              "description": "郵件主題 - Optional field (default: auto-generated based on export type)"
            },
            "message": {
              "type": "string",
              "description": "郵件正文內容 - Optional field (default: auto-generated)"
            },
            "filters": {
              "type": "object",
              "description": "篩選條件 - Optional field",
              "properties": {
                "status": {
                  "type": "string",
                  "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
                  "description": "訂單狀態篩選（僅適用於訂單導出）- Use 'all' to include all statuses"
                },
                "customer_name": {
                  "type": "string",
                  "description": "客戶姓名篩選（僅適用於訂單導出）"
                },
                "product_name": {
                  "type": "string",
                  "description": "產品名稱篩選"
                },
                "category": {
                  "type": "string",
                  "description": "產品類別篩選（僅適用於產品導出）"
                },
                "stock_quantity": {
                  "type": "integer",
                  "minimum": 0,
                  "description": "庫存數量篩選（僅適用於產品導出）"
                },
                "date_from": {
                  "type": "string",
                  "format": "date",
                  "description": "開始日期篩選 (YYYY-MM-DD format)"
                },
                "date_to": {
                  "type": "string",
                  "format": "date",
                  "description": "結束日期篩選 (YYYY-MM-DD format)"
                }
              }
            },
            "limit": {
              "type": "integer",
              "minimum": 1,
              "maximum": 10000,
              "default": 1000,
              "description": "導出記錄數量限制 - Optional field (default: 1000, max: 10000)"
            }
          },
          "required": ["type", "email"],
          "additionalProperties": false
        }
      }
    }
  ]
}
```

## 🔧 Fixed Issues

### ✅ **Corrected Database Schema Mapping**
- **Removed:** `active` (boolean) - Column doesn't exist in products table
- **Added:** `stock_quantity` (integer) - Actual column in products table
- **Available Product Columns:** `id`, `name`, `description`, `price`, `stock_quantity`, `category`, `created_at`, `updated_at`

### ✅ **Updated Tools**
- **GetProductsTool:** Fixed to use `stock_quantity` instead of non-existent `is_active` column
- **SendExcelEmailTool:** Updated filters to use `stock_quantity` for product filtering
- **ProductsExport:** Simplified to only export existing database columns

### ✅ **Example Usage (Corrected)**

#### Get Products with Stock Filter
```text
Prompt: "查找庫存大於等於10的產品"
Expected Call: get_products({"stock_quantity": 10, "limit": 5})
```

#### Export Products with Stock Filter
```text
Prompt: "導出庫存不足5的產品到Excel並發送到admin@company.com"
Expected Call: send_excel_email({
  "type": "products", 
  "email": "admin@company.com",
  "filters": {"stock_quantity": 5},
  "subject": "低庫存產品報告"
})
```

#### Price Range Product Query
```text
Prompt: "找出價格在100到500之間的化妝品"
Expected Call: get_products({
  "category": "化妝品",
  "min_price": 100,
  "max_price": 500
})
```

## 🚨 **Error Fixed**
**Before:** `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_active' in 'where clause'`  
**After:** ✅ All queries now use only existing database columns

## 📊 **Actual Product Data Structure**
```json
{
  "id": 1,
  "name": "防曬乳SPF50",
  "description": "高效防曬產品",
  "price": 299.00,
  "stock_quantity": 25,
  "category": "化妝品",
  "created_at": "2025-06-04 10:00:00",
  "updated_at": "2025-06-04 10:00:00"
}
```

All tools are now fully compatible with the actual database schema! 🚀
