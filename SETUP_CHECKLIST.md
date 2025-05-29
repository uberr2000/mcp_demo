# MCP Laravel Setup Checklist for New Server/Clone

When you clone this repository to a new server, follow these steps to ensure MCP tools are discovered properly:

## 1. Install Dependencies
```bash
composer install
```

## 2. Copy Environment File
```bash
cp .env.example .env
# OR if .env already exists, make sure it has the correct settings
```

## 3. Generate Application Key
```bash
php artisan key:generate
```

## 4. Configure Database
Make sure your `.env` file has the correct database settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mcp_demo
DB_USERNAME=root
DB_PASSWORD=your_password
```

## 5. Run Database Migrations
```bash
php artisan migrate
```

## 6. Seed Database (Optional but recommended)
```bash
php artisan db:seed
```

## 7. Publish MCP Configuration
```bash
php artisan vendor:publish --tag=mcp-config
```

## 8. Clear All Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 9. Discover MCP Tools
```bash
php artisan mcp:discover
```

## 10. Verify Tools are Found
```bash
php artisan mcp:list
```

## Common Issues and Solutions

### Issue 1: "Tools: None found"
**Cause**: Missing composer dependencies or configuration not published
**Solution**: 
```bash
composer install
php artisan vendor:publish --tag=mcp-config
php artisan mcp:discover
```

### Issue 2: Class not found errors
**Cause**: Autoloader not updated
**Solution**:
```bash
composer dump-autoload
php artisan mcp:discover
```

### Issue 3: Configuration cache issues
**Cause**: Cached configuration from previous environment
**Solution**:
```bash
php artisan config:clear
php artisan cache:clear
php artisan mcp:discover
```

### Issue 4: Directory permissions
**Cause**: Web server doesn't have proper permissions
**Solution**:
```bash
# On Linux/Mac
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# On Windows, ensure IIS/Apache has write permissions to storage and bootstrap/cache
```

### Issue 5: MCP directory not found
**Cause**: The `app/Mcp/Tools` directory might not exist
**Solution**:
```bash
mkdir -p app/Mcp/Tools
# Then copy the tool files from the original repository
```

## Required Files
Make sure these files exist in your cloned repository:

### MCP Configuration
- `config/mcp.php` (should be created by vendor:publish)

### MCP Tools
- `app/Mcp/Tools/GetOrdersTool.php`
- `app/Mcp/Tools/GetProductsTool.php`
- `app/Mcp/Tools/GetCustomerStatsTool.php`
- `app/Mcp/Tools/GetOrderAnalyticsTool.php`

### Models
- `app/Models/Order.php`
- `app/Models/Product.php`

### Migrations
- `database/migrations/*_create_products_table.php`
- `database/migrations/*_create_orders_table.php`

## Test Commands

After setup, run these to verify everything works:

```bash
# Test discovery
php artisan mcp:discover

# List tools
php artisan mcp:list

# Start server
php artisan serve --port=8001

# Test HTTP endpoint
curl -X GET http://127.0.0.1:8001/mcp/tools
```

## Quick Setup Script

```bash
#!/bin/bash
# Quick setup script for new server

echo "Setting up Laravel MCP Demo..."

# Install dependencies
composer install

# Setup environment
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Setup database
php artisan migrate
php artisan db:seed

# Setup MCP
php artisan vendor:publish --tag=mcp-config
php artisan config:clear
php artisan cache:clear
php artisan mcp:discover

# Verify
php artisan mcp:list

echo "Setup complete!"
```
