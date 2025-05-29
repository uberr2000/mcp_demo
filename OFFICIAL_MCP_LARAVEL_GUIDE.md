# Laravel MCP Server with Official php-mcp/laravel Package
# Testing and Integration Guide

## Setup Complete! ✅

The official `php-mcp/laravel` package is now successfully installed and configured. Here's what we have:

### Official MCP Tools Available

1. **get_orders** - 從資料庫獲取訂單資訊
2. **get_products** - 從資料庫獲取產品資訊  
3. **get_customer_stats** - 獲取客戶統計資訊
4. **get_order_analytics** - 獲取訂單分析資料

### HTTP Endpoints (Working ✅)

The official package provides these HTTP endpoints:

- **GET** `/mcp/tools` - List all available tools
- **POST** `/mcp/tools/call` - Call a specific tool
- **GET** `/mcp/info` - Server information
- **POST** `/mcp/initialize` - Initialize MCP session

### Testing the HTTP API

```powershell
# List tools
Invoke-RestMethod -Uri "http://127.0.0.1:8001/mcp/tools" -Method GET

# Call a tool
$body = @{
    name = "get_products"
    arguments = @{
        limit = 5
        category = "飲料"
    }
} | ConvertTo-Json -Depth 3

Invoke-RestMethod -Uri "http://127.0.0.1:8001/mcp/tools/call" -Method POST -Body $body -ContentType "application/json"
```

### n8n Integration

For n8n integration, use the HTTP endpoints:

1. **Base URL**: `http://localhost:8001/mcp`
2. **Tools Endpoint**: `POST /tools/call`
3. **List Tools**: `GET /tools`

### Example n8n HTTP Request Node Configuration:

```json
{
  "method": "POST",
  "url": "http://localhost:8001/mcp/tools/call",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "name": "get_orders",
    "arguments": {
      "customer_name": "陳大明",
      "limit": 10
    }
  }
}
```

### STDIO Limitation

⚠️ **Note**: The STDIO transport has issues on Windows due to non-blocking stream limitations. Use HTTP transport for reliability.

### Available Tool Parameters

#### get_products
- `name` (string): Product name search
- `category` (string): Product category  
- `min_price` (float): Minimum price
- `max_price` (float): Maximum price
- `in_stock` (bool): Filter by stock availability
- `limit` (int): Results limit

#### get_orders  
- `transaction_id` (string): Transaction ID search
- `customer_name` (string): Customer name search
- `status` (string): Order status filter
- `product_name` (string): Product name search
- `min_amount` (float): Minimum order amount
- `max_amount` (float): Maximum order amount
- `date_from` (string): Start date (YYYY-MM-DD)
- `date_to` (string): End date (YYYY-MM-DD)
- `limit` (int): Results limit

#### get_customer_stats
- `customer_name` (string): Filter by customer name

#### get_order_analytics  
- `date_from` (string): Start date
- `date_to` (string): End date
- `group_by` (string): Group by 'date', 'status', or 'product'

## Commands

```bash
# Discover tools
php artisan mcp:discover

# List discovered tools
php artisan mcp:list

# Start HTTP server (for HTTP transport)
php artisan serve --port=8001

# Start STDIO server (has Windows issues)
php artisan mcp:serve
```

The official Laravel MCP integration is now fully functional with HTTP transport! 🎉
