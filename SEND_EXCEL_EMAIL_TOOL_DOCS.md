# SendExcelEmailTool Documentation

## Overview

The `SendExcelEmailTool` is an MCP (Model Context Protocol) tool that generates Excel files from your order or product data and sends them via Amazon SES email. This tool is perfect for automated reporting, data exports, and sharing business insights.

## Features

✅ **Dual Export Types**: Orders and Products data export  
✅ **Advanced Filtering**: Filter by status, dates, customer names, product names, etc.  
✅ **Professional Excel Output**: Formatted spreadsheets with headers and styling  
✅ **Amazon SES Integration**: Reliable email delivery  
✅ **Customizable Messages**: Custom email subjects and content  
✅ **Batch Processing**: Export up to 10,000 records per request  
✅ **OpenAI Compatible**: Ready for AI assistant integration  

## Setup

### 1. AWS SES Configuration

Add these environment variables to your `.env` file:

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_aws_access_key_here
AWS_SECRET_ACCESS_KEY=your_aws_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_SES_REGION=us-east-1
MAIL_FROM_ADDRESS=verified-email@yourdomain.com
MAIL_FROM_NAME="Your Company Name"
```

### 2. Verify Email Addresses

In AWS SES Console:
- Verify your sending email address
- Verify recipient email addresses (for sandbox mode)
- Move to production for unrestricted sending

## Usage Examples

### Basic Order Export

```bash
curl -X POST http://localhost:8080/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{
    "name": "send_excel_email",
    "arguments": {
      "type": "orders",
      "email": "manager@company.com"
    }
  }'
```

### Filtered Orders Export

```bash
curl -X POST http://localhost:8080/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{
    "name": "send_excel_email",
    "arguments": {
      "type": "orders",
      "email": "sales@company.com",
      "subject": "Completed Orders Report",
      "message": "Please find the completed orders report for review.",
      "filters": {
        "status": "completed",
        "date_from": "2025-05-01",
        "date_to": "2025-05-31"
      },
      "limit": 100
    }
  }'
```

### Products Export

```bash
curl -X POST http://localhost:8080/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{
    "name": "send_excel_email",
    "arguments": {
      "type": "products",
      "email": "inventory@company.com",
      "filters": {
        "active": true,
        "category": "Electronics"
      }
    }
  }'
```

## OpenAI Integration

### Function Schema

```json
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
          "description": "要導出的數據類型：orders(訂單) 或 products(產品)"
        },
        "email": {
          "type": "string",
          "format": "email",
          "description": "接收Excel文件的郵箱地址"
        }
      },
      "required": ["type", "email"]
    }
  }
}
```

### Example Prompts

**English:**
- "Send me the latest orders in Excel format to john@company.com"
- "Export all completed orders from last month to the sales team"
- "Generate a product catalog Excel file and email it to marketing@company.com"

**Chinese:**
- "發送最新訂單的Excel文件到 manager@company.com"
- "導出上個月已完成的訂單並發送給銷售團隊"
- "生成產品目錄Excel文件並發送到 marketing@company.com"

## Excel Output Format

### Orders Export Includes:
- 交易ID (Transaction ID)
- 客戶姓名 (Customer Name)  
- 產品名稱 (Product Name)
- 產品類別 (Product Category)
- 數量 (Quantity)
- 單價 (Unit Price)
- 總金額 (Total Amount)
- 訂單狀態 (Order Status)
- 訂單日期 (Order Date)
- 客戶郵箱 (Customer Email)
- 客戶電話 (Customer Phone)
- 送貨地址 (Shipping Address)
- 備註 (Notes)

### Products Export Includes:
- 產品ID (Product ID)
- 產品名稱 (Product Name)
- 產品描述 (Product Description)
- 產品類別 (Product Category)
- 價格 (Price)
- 庫存數量 (Stock Quantity)
- SKU編號 (SKU Code)
- 是否啟用 (Active Status)
- 創建日期 (Created Date)
- 更新日期 (Updated Date)
- 重量 (Weight)
- 尺寸 (Dimensions)
- 供應商 (Supplier)

## Available Filters

### Orders Filters:
- `status`: pending, processing, completed, cancelled
- `customer_name`: Partial name search
- `product_name`: Partial product name search
- `date_from`: Start date (YYYY-MM-DD)
- `date_to`: End date (YYYY-MM-DD)

### Products Filters:
- `product_name`: Partial name search
- `category`: Product category filter
- `active`: true/false for active products only
- `date_from`: Created after date
- `date_to`: Created before date

## Response Format

### Success Response:
```json
{
  "success": true,
  "message": "Excel 文件已成功發送到 user@company.com",
  "data": {
    "type": "orders",
    "email": "user@company.com",
    "filename": "orders_export_2025-06-03_14-30-45.xlsx",
    "records_count": 25,
    "export_time": "2025-06-03_14-30-45",
    "subject": "訂單數據導出 - 2025-06-03_14-30-45"
  }
}
```

### Error Response:
```json
{
  "success": false,
  "error": "發送郵件失敗：Invalid email address"
}
```

## Testing

### Local Testing:
```bash
php test-send-excel-tool.php
```

### Ubuntu Server Testing:
```bash
chmod +x test-send-excel-ubuntu.sh
./test-send-excel-ubuntu.sh
```

### PowerShell Testing:
```powershell
.\test-mcp-tools.ps1 local
```

## Troubleshooting

### Common Issues:

1. **AWS SES Authentication Error**
   - Verify AWS credentials in `.env`
   - Check AWS region settings
   - Ensure IAM user has SES permissions

2. **Email Not Received**
   - Check AWS SES verified email addresses
   - Verify sender email is verified in SES
   - Check spam folder
   - Review AWS SES sending limits

3. **Large File Issues**
   - Reduce limit parameter (max 10,000)
   - Check available memory and disk space
   - Consider using filters to reduce data size

4. **Tool Not Found**
   - Verify tool is registered in `config/mcp-server.php`
   - Clear config cache: `php artisan config:clear`
   - Restart the server

## Security Considerations

- Never expose AWS credentials in client-side code
- Use IAM roles with minimal required permissions
- Validate email addresses before sending
- Implement rate limiting for production use
- Monitor AWS SES usage and costs

## Performance Tips

- Use filters to reduce data size
- Set appropriate limits (default: 1000, max: 10,000)
- Consider implementing background job queues for large exports
- Monitor memory usage during Excel generation

---

*This tool is part of the MCP Demo Laravel application and integrates seamlessly with OpenAI assistants and n8n workflows.*
