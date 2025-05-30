# Laravel MCP Server with SSE Support

## Overview

This project has been migrated from `php-mcp/laravel` to `opgginc/laravel-mcp-server` to support **Server-Sent Events (SSE)** transport instead of STDIO. This provides better security and control for enterprise environments.

## Key Features

- ✅ **SSE Transport**: More secure than STDIO for enterprise use
- ✅ **Real-time Communication**: Server-Sent Events for live data streaming
- ✅ **Redis Integration**: Uses Redis for message queuing and session management
- ✅ **Enterprise Security**: Better control over API exposure
- ✅ **Laravel Integration**: Seamless integration with Laravel applications

## Package Information

- **Package**: `opgginc/laravel-mcp-server` v1.1.0
- **Transport**: Server-Sent Events (SSE)
- **Message Broker**: Redis
- **PHP**: 8.2+
- **Laravel**: 12+

## Configuration

The MCP server is configured in `config/mcp-server.php`:

```php
return [
    'enabled' => env('MCP_SERVER_ENABLED', true),
    
    'server' => [
        'name' => 'OP.GG MCP Server',
        'version' => '0.1.0',
    ],
    
    'default_path' => 'mcp',  // Routes: GET /mcp/sse, POST /mcp/message
    
    'sse_adapter' => 'redis',
    'adapters' => [
        'redis' => [
            'prefix' => 'mcp_sse_',
            'connection' => env('MCP_REDIS_CONNECTION', 'default'),
            'ttl' => 100,
        ],
    ],
    
    'tools' => [
        \App\MCP\Tools\GetOrdersTool::class,
        \App\MCP\Tools\GetProductsTool::class,
        \App\MCP\Tools\GetCustomerStatsTool::class,
        \App\MCP\Tools\GetOrderAnalyticsTool::class,
    ],
];
```

## Available Tools

### 1. GetOrdersTool
- **Name**: `get_orders`
- **Description**: 從資料庫獲取訂單資訊，可以根據交易ID、客戶姓名、訂單狀態進行查詢
- **Parameters**:
  - `transaction_id` (string): 交易ID（可部分匹配）
  - `customer_name` (string): 客戶姓名（可部分匹配）
  - `status` (string): 訂單狀態（pending, completed, cancelled）
  - `product_name` (string): 產品名稱（可部分匹配）
  - `min_amount` (number): 最小金額
  - `max_amount` (number): 最大金額
  - `date_from` (date): 開始日期 (YYYY-MM-DD)
  - `date_to` (date): 結束日期 (YYYY-MM-DD)
  - `limit` (integer): 返回結果數量限制 (1-100, default: 10)

### 2. GetProductsTool
- **Name**: `get_products`
- **Description**: 從資料庫獲取產品資訊，可以根據產品名稱、類別、價格範圍進行查詢
- **Parameters**:
  - `name` (string): 產品名稱（可部分匹配）
  - `category` (string): 產品類別
  - `min_price` (number): 最小價格
  - `max_price` (number): 最大價格
  - `active` (boolean): 是否為活躍產品
  - `limit` (integer): 返回結果數量限制 (1-100, default: 10)

### 3. GetCustomerStatsTool
- **Name**: `get_customer_stats`
- **Description**: 獲取客戶統計資訊，包括訂單數量、總消費金額、平均訂單金額等
- **Parameters**:
  - `customer_email` (email): 客戶電子郵件地址
  - `customer_name` (string): 客戶姓名（可部分匹配）
  - `date_from` (date): 統計開始日期 (YYYY-MM-DD)
  - `date_to` (date): 統計結束日期 (YYYY-MM-DD)
  - `status` (string): 訂單狀態篩選（pending, completed, cancelled）
  - `limit` (integer): 返回客戶數量限制 (1-100, default: 20)

### 4. GetOrderAnalyticsTool
- **Name**: `get_order_analytics`
- **Description**: 獲取訂單分析資料，包括按日期、狀態、產品的統計分析
- **Parameters**:
  - `analytics_type` (enum): 分析類型（daily, status, product, monthly）
  - `date_from` (date): 分析開始日期 (YYYY-MM-DD)
  - `date_to` (date): 分析結束日期 (YYYY-MM-DD)
  - `status` (string): 篩選特定訂單狀態（pending, completed, cancelled）
  - `limit` (integer): 返回結果數量限制 (1-100, default: 30)

## API Endpoints

### SSE Connection
```
GET /mcp/sse
```
Establishes Server-Sent Events connection for real-time communication.

### Message Endpoint
```
POST /mcp/message
```
Sends messages to the MCP server. Requires `sessionId` from the SSE connection.

## Usage Examples

### 1. Establishing SSE Connection

```javascript
const eventSource = new EventSource('http://localhost:8001/mcp/sse');

eventSource.onopen = function(event) {
    console.log('SSE connection opened');
};

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Received:', data);
};

eventSource.onerror = function(event) {
    console.error('SSE error:', event);
};
```

### 2. Sending Tool Requests

```javascript
// Extract sessionId from SSE connection
const sessionId = 'your-session-id-from-sse';

// Send tool request
fetch('http://localhost:8001/mcp/message', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        sessionId: sessionId,
        method: 'tools/call',
        params: {
            name: 'get_orders',
            arguments: {
                customer_name: 'John',
                status: 'completed',
                limit: 5
            }
        }
    })
});
```

### 3. Python Client Example

```python
import requests
import json
import sseclient

# Establish SSE connection
response = requests.get('http://localhost:8001/mcp/sse', stream=True)
client = sseclient.SSEClient(response)

session_id = None

for event in client.events():
    data = json.loads(event.data)
    
    if 'sessionId' in data:
        session_id = data['sessionId']
        break

# Send tool request
if session_id:
    tool_request = {
        "sessionId": session_id,
        "method": "tools/call",
        "params": {
            "name": "get_products",
            "arguments": {
                "category": "electronics",
                "min_price": 100,
                "limit": 10
            }
        }
    }
    
    response = requests.post(
        'http://localhost:8001/mcp/message',
        json=tool_request
    )
```

## Development Setup

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Set up Redis
Ensure Redis is running and configure in `.env`:
```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload -o
```

### 5. Start Server
```bash
php artisan serve --port=8001
```

## Testing

### Test SSE Endpoint
```bash
curl -N -H "Accept: text/event-stream" http://localhost:8001/mcp/sse
```

### Test Tool Execution
Use the browser's developer tools or tools like Postman to test the `/mcp/message` endpoint.

## Security Considerations

1. **Authentication**: Add middleware to protect endpoints:
   ```php
   'middlewares' => [
       'auth:api'
   ],
   ```

2. **Rate Limiting**: Implement rate limiting for API endpoints

3. **Input Validation**: All tools include comprehensive input validation

4. **Error Handling**: Proper error responses with JSON-RPC error codes

## Troubleshooting

### Common Issues

1. **Redis Connection Error**
   - Ensure Redis is running
   - Check Redis configuration in `.env`

2. **Tool Not Found**
   - Verify tool class exists in `app/MCP/Tools/`
   - Check tool is registered in `config/mcp-server.php`
   - Run `composer dump-autoload -o`

3. **SSE Connection Issues**
   - Check server is running on correct port
   - Verify firewall settings
   - Test with simple curl command

## Migration Benefits

### From STDIO to SSE

- **Security**: No exposure of internal system details
- **Control**: Better authentication and authorization
- **Scalability**: Redis-based message queuing
- **Real-time**: Live data streaming capabilities
- **Enterprise Ready**: Suitable for production environments

### Performance

- **Redis Caching**: Fast message delivery
- **Session Management**: Efficient client handling
- **Resource Management**: Better memory and connection handling

## Support

For issues and questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Monitor Redis logs
3. Enable debug mode: `APP_DEBUG=true`
4. Review MCP server documentation: [OP.GG Laravel MCP Server](https://github.com/opgginc/laravel-mcp-server)
