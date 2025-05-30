# Laravel MCP Server Ubuntu WSL Setup Guide

## Overview
This guide will help you migrate your Laravel MCP server project to Ubuntu WSL and configure it with FrankenPHP and streamable HTTP transport.

## Step 1: Prepare Ubuntu WSL Environment

### 1.1 Open Ubuntu WSL Terminal
```bash
# Open Windows Terminal and start Ubuntu WSL
wsl -d Ubuntu
```

### 1.2 Update Ubuntu System
```bash
sudo apt update && sudo apt upgrade -y
```

### 1.3 Install Required Dependencies
```bash
# Install PHP 8.2 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl php8.2-gd php8.2-mysql php8.2-sqlite3

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (if needed)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install Git
sudo apt install -y git curl wget unzip
```

## Step 2: Copy Project to Ubuntu

### 2.1 Create Project Directory
```bash
# Create a directory for your Laravel projects
mkdir -p ~/laravel-projects
cd ~/laravel-projects
```

### 2.2 Copy Project Files
You have several options to copy your project:

**Option A: Copy from Windows directory (Recommended)**
```bash
# Access your Windows files from WSL
cp -r /mnt/d/workspace/Demo/mcp_demo ~/laravel-projects/
cd ~/laravel-projects/mcp_demo
```

**Option B: Use Git (if project is in repository)**
```bash
git clone <your-repository-url> mcp_demo
cd mcp_demo
```

**Option C: Manual copy via Windows Explorer**
- Open Windows Explorer
- Navigate to `\\wsl$\Ubuntu\home\<username>\laravel-projects\`
- Copy your project folder there

## Step 3: Install Project Dependencies

### 3.1 Install Composer Dependencies
```bash
cd ~/laravel-projects/mcp_demo
composer install
```

### 3.2 Configure Environment
```bash
# Copy environment file if it doesn't exist
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (if using SQLite)
touch database/database.sqlite
```

### 3.3 Install Laravel Octane with FrankenPHP
```bash
# Install Octane (should already be in composer.json)
composer require laravel/octane

# Install Octane with FrankenPHP
php artisan octane:install frankenphp
```

## Step 4: Configure MCP Server for HTTP Transport

### 4.1 Update MCP Server Configuration
Edit `config/mcp-server.php` to use streamable HTTP transport:

```php
<?php

return [
    'transport' => [
        'provider' => 'streamable_http',
        'host' => '127.0.0.1',
        'port' => 8080,
        'path' => '/mcp',
    ],
    
    'tools' => [
        App\Mcp\Tools\GetOrdersTool::class,
        App\Mcp\Tools\GetProductsTool::class,
        App\Mcp\Tools\GetCustomerStatsTool::class,
        App\Mcp\Tools\GetOrderAnalyticsTool::class,
    ],
    
    'resources' => [
        // Add your resources here
    ],
];
```

### 4.2 Add MCP Routes (if needed)
Create or update `routes/web.php` to include MCP endpoints:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// MCP Server endpoints will be handled automatically by the package
```

## Step 5: Start the Server

### 5.1 Start Laravel Octane with FrankenPHP
```bash
# Start the server
php artisan octane:start --host=127.0.0.1 --port=8080

# Or start in watch mode for development
php artisan octane:start --watch --host=127.0.0.1 --port=8080
```

### 5.2 Verify Server is Running
```bash
# Test basic Laravel response
curl http://127.0.0.1:8080

# Test MCP endpoint
curl http://127.0.0.1:8080/mcp
```

## Step 6: Test MCP Tools

### 6.1 Test Tool Registration
```bash
# Check if tools are registered properly
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/list", "params": {}}'
```

### 6.2 Test Individual Tools
```bash
# Test GetOrdersTool
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": {"name": "get_orders", "arguments": {}}}'

# Test GetProductsTool
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": {"name": "get_products", "arguments": {}}}'
```

## Step 7: Troubleshooting

### 7.1 Common Issues

**Permission Issues**
```bash
# Fix storage permissions
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

**Port Already in Use**
```bash
# Check what's using the port
sudo netstat -tulpn | grep :8080

# Kill process if needed
sudo kill -9 <process_id>
```

**FrankenPHP Installation Issues**
```bash
# Manually install FrankenPHP if needed
curl -sSL https://get.frankenphp.dev | bash
```

### 7.2 Logs and Debugging
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Octane logs
php artisan octane:status
```

## Step 8: Production Considerations

### 8.1 Process Management
```bash
# Install Supervisor for process management
sudo apt install supervisor

# Create supervisor config for Octane
sudo nano /etc/supervisor/conf.d/laravel-octane.conf
```

### 8.2 Security
- Configure firewall rules
- Set up proper SSL certificates
- Configure rate limiting
- Implement authentication for MCP endpoints

## Next Steps

1. **Test All Tools**: Verify each MCP tool works correctly
2. **Client Integration**: Test with actual MCP clients
3. **Performance Tuning**: Optimize Octane configuration
4. **Monitoring**: Set up logging and monitoring
5. **Documentation**: Update API documentation

## Useful Commands

```bash
# Restart Octane server
php artisan octane:restart

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Check server status
php artisan octane:status

# Monitor performance
php artisan octane:reload --watch
```

This setup provides a robust foundation for your Laravel MCP server with proper HTTP transport support.
