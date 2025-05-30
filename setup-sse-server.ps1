#!/usr/bin/env pwsh

# Laravel MCP Server with SSE Setup Script
# This script sets up the opgginc/laravel-mcp-server package with SSE support

Write-Host "=== Laravel MCP Server (SSE) Setup ===" -ForegroundColor Green
Write-Host "Setting up opgginc/laravel-mcp-server with Server-Sent Events support..." -ForegroundColor Yellow

# Function to run command and check result
function Invoke-SafeCommand {
    param(
        [string]$Command,
        [string]$Description,
        [bool]$Required = $true
    )
    
    Write-Host "`n--- $Description ---" -ForegroundColor Cyan
    Write-Host "Running: $Command" -ForegroundColor Gray
    
    try {
        $result = Invoke-Expression $Command
        Write-Host "✅ Success: $Description" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "❌ Failed: $Description" -ForegroundColor Red
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
        if ($Required) {
            Write-Host "This step is required. Please fix the error and try again." -ForegroundColor Red
            exit 1
        }
        return $false
    }
}

# 1. Check prerequisites
Write-Host "`n1. Checking prerequisites..." -ForegroundColor Yellow

if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
    Write-Host "❌ PHP is not installed or not in PATH" -ForegroundColor Red
    exit 1
}

$phpVersion = php -r "echo PHP_VERSION;"
Write-Host "✅ PHP version: $phpVersion" -ForegroundColor Green

if (-not (Get-Command "composer" -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Composer is not installed or not in PATH" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Composer is available" -ForegroundColor Green

if (-not (Get-Command "redis-server" -ErrorAction SilentlyContinue)) {
    Write-Host "⚠️  Redis server not found in PATH" -ForegroundColor Yellow
    Write-Host "   Please ensure Redis is installed and running" -ForegroundColor Yellow
}

# 2. Check project structure
Write-Host "`n2. Checking project structure..." -ForegroundColor Yellow

if (-not (Test-Path "artisan")) {
    Write-Host "❌ Not in Laravel project root directory" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Laravel project detected" -ForegroundColor Green

# 3. Install/Update the new MCP package
Write-Host "`n3. Installing opgginc/laravel-mcp-server..." -ForegroundColor Yellow

# Remove old package if exists
if (Select-String -Path "composer.json" -Pattern "php-mcp/laravel" -Quiet) {
    Invoke-SafeCommand "composer remove php-mcp/laravel" "Removing old php-mcp/laravel package" $false
}

# Install new package
Invoke-SafeCommand "composer require opgginc/laravel-mcp-server" "Installing opgginc/laravel-mcp-server" $true

# 4. Publish configuration
Invoke-SafeCommand "php artisan vendor:publish --tag=mcp-server-config --force" "Publishing MCP server configuration" $true

# 5. Set up environment
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Invoke-SafeCommand "Copy-Item '.env.example' '.env'" "Copying .env file" $true
    }
}

# 6. Configure Redis (if needed)
Write-Host "`n6. Configuring Redis..." -ForegroundColor Yellow

$envContent = Get-Content ".env" -Raw
if (-not ($envContent -match "REDIS_HOST")) {
    Add-Content ".env" "`nREDIS_HOST=127.0.0.1"
    Add-Content ".env" "REDIS_PASSWORD=null"
    Add-Content ".env" "REDIS_PORT=6379"
    Write-Host "✅ Redis configuration added to .env" -ForegroundColor Green
} else {
    Write-Host "✅ Redis configuration already exists" -ForegroundColor Green
}

# Add MCP specific environment variables
if (-not ($envContent -match "MCP_SERVER_ENABLED")) {
    Add-Content ".env" "`nMCP_SERVER_ENABLED=true"
    Add-Content ".env" "MCP_REDIS_CONNECTION=default"
    Write-Host "✅ MCP configuration added to .env" -ForegroundColor Green
}

# 7. Remove old MCP directory structure
Write-Host "`n7. Cleaning up old MCP structure..." -ForegroundColor Yellow

$oldDirs = @("app/Mcp", "app/mcp")
foreach ($dir in $oldDirs) {
    if (Test-Path $dir) {
        Remove-Item -Recurse -Force $dir
        Write-Host "✅ Removed old directory: $dir" -ForegroundColor Green
    }
}

# 8. Create new MCP directory structure
Write-Host "`n8. Creating new MCP structure..." -ForegroundColor Yellow

$newDir = "app/MCP/Tools"
if (-not (Test-Path $newDir)) {
    New-Item -ItemType Directory -Path $newDir -Force | Out-Null
    Write-Host "✅ Created directory: $newDir" -ForegroundColor Green
}

# 9. Clear caches and regenerate autoloader
Write-Host "`n9. Clearing caches..." -ForegroundColor Yellow

Invoke-SafeCommand "php artisan config:clear" "Clearing config cache" $false
Invoke-SafeCommand "php artisan cache:clear" "Clearing application cache" $false
Invoke-SafeCommand "composer dump-autoload -o" "Regenerating optimized autoloader" $true

# 10. Check MCP configuration
Write-Host "`n10. Verifying MCP configuration..." -ForegroundColor Yellow

if (Test-Path "config/mcp-server.php") {
    Write-Host "✅ MCP server configuration exists" -ForegroundColor Green
} else {
    Write-Host "❌ MCP server configuration missing" -ForegroundColor Red
}

# Check if tools are registered
$configContent = Get-Content "config/mcp-server.php" -Raw
if ($configContent -match "App\\MCP\\Tools") {
    Write-Host "✅ MCP tools are registered" -ForegroundColor Green
} else {
    Write-Host "⚠️  MCP tools may need to be registered in config/mcp-server.php" -ForegroundColor Yellow
}

# 11. Test Laravel application
Write-Host "`n11. Testing Laravel application..." -ForegroundColor Yellow

try {
    $configTest = php artisan config:show app.name 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Laravel application is working" -ForegroundColor Green
    } else {
        Write-Host "❌ Laravel application has issues" -ForegroundColor Red
        Write-Host $configTest -ForegroundColor Red
    }
}
catch {
    Write-Host "❌ Error testing Laravel: $($_.Exception.Message)" -ForegroundColor Red
}

# 12. Final setup information
Write-Host "`n=== SETUP COMPLETE ===" -ForegroundColor Green

Write-Host "`nMCP Server with SSE is now configured!" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "1. Ensure Redis is running: redis-server" -ForegroundColor White
Write-Host "2. Start Laravel server: php artisan serve --port=8001" -ForegroundColor White
Write-Host "3. Test SSE endpoint: http://localhost:8001/mcp/sse" -ForegroundColor White
Write-Host "4. Test with client: http://localhost:8001/mcp-test.html" -ForegroundColor White

Write-Host "`nKey differences from STDIO version:" -ForegroundColor Yellow
Write-Host "✅ Uses Server-Sent Events (SSE) instead of STDIO" -ForegroundColor Green
Write-Host "✅ Better security for enterprise environments" -ForegroundColor Green
Write-Host "✅ Real-time communication with Redis backend" -ForegroundColor Green
Write-Host "✅ Session-based message handling" -ForegroundColor Green

Write-Host "`nAPI Endpoints:" -ForegroundColor Yellow
Write-Host "GET  /mcp/sse     - SSE connection endpoint" -ForegroundColor White
Write-Host "POST /mcp/message - Tool execution endpoint" -ForegroundColor White

Write-Host "`nAvailable Tools:" -ForegroundColor Yellow
Write-Host "- get_orders: 從資料庫獲取訂單資訊" -ForegroundColor White
Write-Host "- get_products: 從資料庫獲取產品資訊" -ForegroundColor White
Write-Host "- get_customer_stats: 獲取客戶統計資訊" -ForegroundColor White
Write-Host "- get_order_analytics: 獲取訂單分析資料" -ForegroundColor White

Write-Host "`nSetup completed successfully! 🎉" -ForegroundColor Green
