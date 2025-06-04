# Complete MCP Tools Schema Documentation

## 🚀 Overview
This MCP server provides **5 comprehensive tools** for order management, analytics, customer statistics, and data export. All order-related tools support the **"all" status parameter** for comprehensive data access without status filtering.

**MCP Server Endpoint:** `https://mcp.ink.net.tw/mcp/sse`

## 📋 Available Tools Summary

| Tool Name | Description | "All" Status Support | Required Params |
|-----------|-------------|---------------------|-----------------|
| `get_orders` | 從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢 | ✅ Yes | None |
| `get_products` | 從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢 | ❌ N/A | None |
| `get_customer_stats` | Get customer statistics including order count, total spending, average order amount | ✅ Yes | None |
| `get_order_analytics` | 獲取訂單分析資料，包括按日期、狀態、產品的統計分析 | ✅ Yes | None |
| `send_excel_email` | 生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱 | ✅ Yes | `type`, `email` |

---

## 🔧 Tool Schemas

### 1. GET_ORDERS 📊
**Name:** `get_orders`  
**Description:** 從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢  
**All Status Support:** ✅ Yes (Use `"all"` to include all order statuses)

```json
{
    "name": "get_orders",
    "description": "從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢",
    "inputSchema": {
        "type": "object",
        "properties": {
            "transaction_id": {
                "type": "string",
                "description": "交易ID（可部分匹配）- Optional field"
            },
            "customer_name": {
                "type": "string",
                "description": "客戶姓名（可部分匹配）- Optional field"
            },
            "status": {
                "type": "string",
                "description": "訂單狀態（pending, completed, cancelled, all）- Optional field. Use \"all\" to include all statuses"
            },
            "product_name": {
                "type": "string",
                "description": "產品名稱（可部分匹配）- Optional field"
            },
            "min_amount": {
                "type": "number",
                "description": "最小金額 - Optional field (use 0 to ignore this filter)"
            },
            "max_amount": {
                "type": "number",
                "description": "最大金額 - Optional field (use 0 to ignore this filter)"
            },
            "date_from": {
                "type": "string",
                "format": "date",
                "description": "開始日期 (YYYY-MM-DD) - Optional field"
            },
            "date_to": {
                "type": "string",
                "format": "date",
                "description": "結束日期 (YYYY-MM-DD) - Optional field"
            },
            "limit": {
                "type": "integer",
                "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)",
                "default": 10,
                "minimum": 1,
                "maximum": 100
            }
        }
    }
}
```

**Example Usage:**
```json
{
    "customer_name": "何淑儀",
    "status": "all",
    "limit": 5
}
```

---

### 2. GET_PRODUCTS 🛍️
**Name:** `get_products`  
**Description:** 從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢  
**All Status Support:** ❌ N/A (No status field)

```json
{
    "name": "get_products",
    "description": "從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢",
    "inputSchema": {
        "type": "object",
        "properties": {
            "name": {
                "type": "string",
                "description": "產品名稱（可部分匹配）- Optional field"
            },
            "category": {
                "type": "string",
                "description": "產品類別 - Optional field"
            },
            "min_price": {
                "type": "number",
                "description": "最小價格 - Optional field"
            },
            "max_price": {
                "type": "number",
                "description": "最大價格 - Optional field"
            },
            "stock_quantity": {
                "type": "integer",
                "description": "庫存數量篩選 - Optional field"
            },
            "limit": {
                "type": "integer",
                "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)",
                "default": 10,
                "minimum": 1,
                "maximum": 100
            }
        }
    }
}
```

**Example Usage:**
```json
{
    "name": "防曬乳",
    "stock_quantity": 5,
    "limit": 10
}
```

---

### 3. GET_CUSTOMER_STATS 👥
**Name:** `get_customer_stats`  
**Description:** Get customer statistics including order count, total spending, average order amount, etc.  
**All Status Support:** ✅ Yes (Use `"all"` to include orders of all statuses in statistics)

```json
{
    "name": "get_customer_stats",
    "description": "Get customer statistics including order count, total spending, average order amount, etc.",
    "inputSchema": {
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
                "description": "Order status filter (pending, processing, completed, cancelled, refunded, all) - Optional field. Use \"all\" to include all statuses"
            },
            "limit": {
                "type": "integer",
                "description": "Limit number of customers returned - Optional field (default: 20, range: 1-100)",
                "default": 20,
                "minimum": 1,
                "maximum": 100
            }
        }
    }
}
```

**Example Usage:**
```json
{
    "customer_name": "何淑儀",
    "status": "all",
    "date_from": "2024-01-01",
    "limit": 1
}
```

---

### 4. GET_ORDER_ANALYTICS 📈
**Name:** `get_order_analytics`  
**Description:** 獲取訂單分析資料，包括按日期、狀態、產品的統計分析  
**All Status Support:** ✅ Yes (Use `"all"` to analyze orders across all statuses)

```json
{
    "name": "get_order_analytics",
    "description": "獲取訂單分析資料，包括按日期、狀態、產品的統計分析",
    "inputSchema": {
        "type": "object",
        "properties": {
            "analytics_type": {
                "type": "string",
                "enum": ["daily", "status", "product", "monthly"],
                "description": "分析類型：daily（按日統計）、status（按狀態統計）、product（按產品統計）、monthly（按月統計）- Optional field (default: daily)",
                "default": "daily"
            },
            "date_from": {
                "type": "string",
                "format": "date",
                "description": "分析開始日期 (YYYY-MM-DD) - Optional field"
            },
            "date_to": {
                "type": "string",
                "format": "date",
                "description": "分析結束日期 (YYYY-MM-DD) - Optional field"
            },
            "status": {
                "type": "string",
                "description": "篩選特定訂單狀態（pending, completed, cancelled, all）- Optional field. Use \"all\" to include all statuses"
            },
            "limit": {
                "type": "integer",
                "description": "返回結果數量限制 - Optional field (default: 30, range: 1-100)",
                "default": 30,
                "minimum": 1,
                "maximum": 100
            }
        }
    }
}
```

**Example Usage:**
```json
{
    "analytics_type": "daily",
    "status": "all",
    "date_from": "2024-01-01",
    "date_to": "2024-01-31",
    "limit": 30
}
```

---

### 5. SEND_EXCEL_EMAIL 📧
**Name:** `send_excel_email`  
**Description:** 生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱  
**All Status Support:** ✅ Yes (Use `"all"` in filters to include orders of all statuses in export)  
**Required Parameters:** `type`, `email`

```json
{
    "name": "send_excel_email",
    "description": "生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱",
    "inputSchema": {
        "type": "object",
        "properties": {
            "type": {
                "type": "string",
                "enum": ["orders", "products"],
                "description": "要導出的數據類型：orders(訂單) 或 products(產品)"
            },
            "email": {
                "type": "string",
                "format": "email",
                "description": "接收Excel文件的郵箱地址"
            },
            "subject": {
                "type": "string",
                "description": "郵件主題 - Optional field (default: 系統自動生成)"
            },
            "message": {
                "type": "string",
                "description": "郵件內容 - Optional field (default: 系統自動生成)"
            },
            "filters": {
                "type": "object",
                "description": "篩選條件 - Optional field",
                "properties": {
                    "status": {
                        "type": "string",
                        "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
                        "description": "訂單狀態篩選（僅適用於訂單導出）- Use \"all\" to include all statuses"
                    },
                    "customer_name": {
                        "type": "string",
                        "description": "客戶姓名篩選（僅適用於訂單導出）"
                    },
                    "product_name": {
                        "type": "string",
                        "description": "產品名稱篩選"
                    },
                    "date_from": {
                        "type": "string",
                        "format": "date",
                        "description": "開始日期 (YYYY-MM-DD format)"
                    },
                    "date_to": {
                        "type": "string",
                        "format": "date",
                        "description": "結束日期 (YYYY-MM-DD format)"
                    },
                    "category": {
                        "type": "string",
                        "description": "產品類別篩選（僅適用於產品導出）"
                    },
                    "active": {
                        "type": "boolean",
                        "description": "是否啟用篩選（僅適用於產品導出）"
                    }
                }
            },
            "limit": {
                "type": "integer",
                "minimum": 1,
                "maximum": 10000,
                "description": "導出記錄數量限制 - Optional field (default: 1000, max: 10000)"
            }
        },
        "required": ["type", "email"]
    }
}
```

**Example Usage:**
```json
{
    "type": "orders",
    "email": "user@example.com",
    "subject": "全部訂單數據導出",
    "filters": {
        "status": "all",
        "date_from": "2024-01-01",
        "date_to": "2024-12-31"
    },
    "limit": 5000
}
```

---

## 🌟 "All" Status Feature

**Supported Tools:** 4 out of 5 tools support "all" status parameter:
- ✅ `get_orders` - Include orders of all statuses
- ✅ `get_customer_stats` - Calculate stats across all order statuses  
- ✅ `get_order_analytics` - Analyze data across all order statuses
- ✅ `send_excel_email` - Export orders/products without status filtering
- ❌ `get_products` - N/A (no status field for products)

**How it works:**
- Set `status: "all"` to bypass status filtering entirely
- Retrieves data across all possible statuses (pending, processing, completed, cancelled, refunded)
- Useful for comprehensive reports and complete data exports

---

## 🔗 n8n Integration Examples

### Basic Order Query
```json
{
    "tool": "get_orders",
    "parameters": {
        "customer_name": "何淑儀",
        "status": "all",
        "limit": 5
    }
}
```

### Complete Customer Analytics
```json
{
    "tool": "get_customer_stats",
    "parameters": {
        "status": "all",
        "date_from": "2024-01-01",
        "limit": 10
    }
}
```

### Daily Analytics Across All Statuses
```json
{
    "tool": "get_order_analytics",
    "parameters": {
        "analytics_type": "daily",
        "status": "all",
        "date_from": "2024-01-01",
        "date_to": "2024-01-31"
    }
}
```

### Complete Data Export
```json
{
    "tool": "send_excel_email",
    "parameters": {
        "type": "orders",
        "email": "admin@company.com",
        "filters": {
            "status": "all"
        },
        "limit": 10000
    }
}
```

---

## 🚀 Production Features

### ✅ Robust Error Handling
- Input validation with detailed error messages
- Database connection error handling
- Email delivery error handling
- File generation error handling

### ✅ Security & Validation
- Email format validation
- Parameter type checking
- Safe SQL query building with Eloquent ORM
- Proper file path handling

### ✅ Performance Optimizations
- Configurable result limits (1-100 for queries, 1-10000 for exports)
- Efficient database queries with proper indexing
- Memory-efficient Excel generation
- Background email sending via SES

### ✅ OpenAI/n8n Compatibility
- JSON Schema compliant
- Clear parameter descriptions in multiple languages
- Optional parameters clearly marked
- Enum values properly defined

---

## 📝 Complete JSON Schema Array

```json
[
    {
        "name": "get_orders",
        "description": "從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢",
        "inputSchema": {
            "type": "object",
            "properties": {
                "transaction_id": {
                    "type": "string",
                    "description": "交易ID（可部分匹配）- Optional field"
                },
                "customer_name": {
                    "type": "string",
                    "description": "客戶姓名（可部分匹配）- Optional field"
                },
                "status": {
                    "type": "string",
                    "description": "訂單狀態（pending, completed, cancelled, all）- Optional field. Use \"all\" to include all statuses"
                },
                "product_name": {
                    "type": "string",
                    "description": "產品名稱（可部分匹配）- Optional field"
                },
                "min_amount": {
                    "type": "number",
                    "description": "最小金額 - Optional field (use 0 to ignore this filter)"
                },
                "max_amount": {
                    "type": "number",
                    "description": "最大金額 - Optional field (use 0 to ignore this filter)"
                },
                "date_from": {
                    "type": "string",
                    "format": "date",
                    "description": "開始日期 (YYYY-MM-DD) - Optional field"
                },
                "date_to": {
                    "type": "string",
                    "format": "date",
                    "description": "結束日期 (YYYY-MM-DD) - Optional field"
                },
                "limit": {
                    "type": "integer",
                    "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)",
                    "default": 10,
                    "minimum": 1,
                    "maximum": 100
                }
            }
        }
    },
    {
        "name": "get_products",
        "description": "從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢",
        "inputSchema": {
            "type": "object",
            "properties": {
                "name": {
                    "type": "string",
                    "description": "產品名稱（可部分匹配）- Optional field"
                },
                "category": {
                    "type": "string",
                    "description": "產品類別 - Optional field"
                },
                "min_price": {
                    "type": "number",
                    "description": "最小價格 - Optional field"
                },
                "max_price": {
                    "type": "number",
                    "description": "最大價格 - Optional field"
                },
                "active": {
                    "type": "boolean",
                    "description": "是否為活躍產品 - Optional field"
                },
                "limit": {
                    "type": "integer",
                    "description": "返回結果數量限制 - Optional field (default: 10, range: 1-100)",
                    "default": 10,
                    "minimum": 1,
                    "maximum": 100
                }
            }
        }
    },
    {
        "name": "get_customer_stats",
        "description": "Get customer statistics including order count, total spending, average order amount, etc.",
        "inputSchema": {
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
                    "description": "Order status filter (pending, processing, completed, cancelled, refunded, all) - Optional field. Use \"all\" to include all statuses"
                },
                "limit": {
                    "type": "integer",
                    "description": "Limit number of customers returned - Optional field (default: 20, range: 1-100)",
                    "default": 20,
                    "minimum": 1,
                    "maximum": 100
                }
            }
        }
    },
    {
        "name": "get_order_analytics",
        "description": "獲取訂單分析資料，包括按日期、狀態、產品的統計分析",
        "inputSchema": {
            "type": "object",
            "properties": {
                "analytics_type": {
                    "type": "string",
                    "enum": ["daily", "status", "product", "monthly"],
                    "description": "分析類型：daily（按日統計）、status（按狀態統計）、product（按產品統計）、monthly（按月統計）- Optional field (default: daily)",
                    "default": "daily"
                },
                "date_from": {
                    "type": "string",
                    "format": "date",
                    "description": "分析開始日期 (YYYY-MM-DD) - Optional field"
                },
                "date_to": {
                    "type": "string",
                    "format": "date",
                    "description": "分析結束日期 (YYYY-MM-DD) - Optional field"
                },
                "status": {
                    "type": "string",
                    "description": "篩選特定訂單狀態（pending, completed, cancelled, all）- Optional field. Use \"all\" to include all statuses"
                },
                "limit": {
                    "type": "integer",
                    "description": "返回結果數量限制 - Optional field (default: 30, range: 1-100)",
                    "default": 30,
                    "minimum": 1,
                    "maximum": 100
                }
            }
        }
    },
    {
        "name": "send_excel_email",
        "description": "生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱",
        "inputSchema": {
            "type": "object",
            "properties": {
                "type": {
                    "type": "string",
                    "enum": ["orders", "products"],
                    "description": "要導出的數據類型：orders(訂單) 或 products(產品)"
                },
                "email": {
                    "type": "string",
                    "format": "email",
                    "description": "接收Excel文件的郵箱地址"
                },
                "subject": {
                    "type": "string",
                    "description": "郵件主題 - Optional field (default: 系統自動生成)"
                },
                "message": {
                    "type": "string",
                    "description": "郵件內容 - Optional field (default: 系統自動生成)"
                },
                "filters": {
                    "type": "object",
                    "description": "篩選條件 - Optional field",
                    "properties": {
                        "status": {
                            "type": "string",
                            "enum": ["pending", "processing", "completed", "cancelled", "refunded", "all"],
                            "description": "訂單狀態篩選（僅適用於訂單導出）- Use \"all\" to include all statuses"
                        },
                        "customer_name": {
                            "type": "string",
                            "description": "客戶姓名篩選（僅適用於訂單導出）"
                        },
                        "product_name": {
                            "type": "string",
                            "description": "產品名稱篩選"
                        },
                        "date_from": {
                            "type": "string",
                            "format": "date",
                            "description": "開始日期 (YYYY-MM-DD format)"
                        },
                        "date_to": {
                            "type": "string",
                            "format": "date",
                            "description": "結束日期 (YYYY-MM-DD format)"
                        },
                        "category": {
                            "type": "string",
                            "description": "產品類別篩選（僅適用於產品導出）"
                        },
                        "active": {
                            "type": "boolean",
                            "description": "是否啟用篩選（僅適用於產品導出）"
                        }
                    }
                },
                "limit": {
                    "type": "integer",
                    "minimum": 1,
                    "maximum": 10000,
                    "description": "導出記錄數量限制 - Optional field (default: 1000, max: 10000)"
                }
            },
            "required": ["type", "email"]
        }
    }
]
```

---

## 🎯 Summary

**Total MCP Tools:** 5  
**"All" Status Support:** 4 out of 5 tools  
**Production Ready:** ✅  
**OpenAI Compatible:** ✅  
**n8n Compatible:** ✅  

All tools are thoroughly tested, documented, and ready for production use! 🚀

**MCP Server Endpoint:** `https://mcp.ink.net.tw/mcp/sse`
