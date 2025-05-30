# Ubuntu WSL Transition Script
# Run this in PowerShell to help transition your Laravel MCP project to Ubuntu WSL

Write-Host "🚀 Laravel MCP Server - Windows to Ubuntu WSL Transition" -ForegroundColor Green
Write-Host "================================================================" -ForegroundColor Cyan

# Check if WSL is installed
Write-Host "`n📋 Checking WSL installation..." -ForegroundColor Yellow
try {
    $wslVersion = wsl --version
    Write-Host "✅ WSL is installed" -ForegroundColor Green
} catch {
    Write-Host "❌ WSL is not installed. Please install WSL first:" -ForegroundColor Red
    Write-Host "   Run: wsl --install" -ForegroundColor White
    exit 1
}

# Check if Ubuntu is installed
Write-Host "`n📋 Checking Ubuntu installation..." -ForegroundColor Yellow
$ubuntuInstalled = wsl -l -v | Select-String "Ubuntu"
if ($ubuntuInstalled) {
    Write-Host "✅ Ubuntu is installed in WSL" -ForegroundColor Green
    Write-Host $ubuntuInstalled -ForegroundColor White
} else {
    Write-Host "❌ Ubuntu is not installed. Please install Ubuntu:" -ForegroundColor Red
    Write-Host "   Run: wsl --install -d Ubuntu" -ForegroundColor White
    exit 1
}

# Project information
$projectPath = "d:\workspace\Demo\mcp_demo"
$ubuntuProjectPath = "~/laravel-projects/mcp_demo"

Write-Host "`n📁 Project Information:" -ForegroundColor Yellow
Write-Host "   Windows Path: $projectPath" -ForegroundColor White
Write-Host "   Ubuntu Path:  $ubuntuProjectPath" -ForegroundColor White

# Check if project exists
if (Test-Path $projectPath) {
    Write-Host "✅ Project found at Windows path" -ForegroundColor Green
} else {
    Write-Host "❌ Project not found at Windows path" -ForegroundColor Red
    exit 1
}

Write-Host "`n🔧 Next Steps:" -ForegroundColor Yellow
Write-Host "1. Open Ubuntu WSL terminal" -ForegroundColor White
Write-Host "2. Copy and run the setup script" -ForegroundColor White
Write-Host "3. Test the MCP server" -ForegroundColor White

Write-Host "`n📜 Setup Commands:" -ForegroundColor Cyan
Write-Host @"
# Open Ubuntu WSL
wsl -d Ubuntu

# Navigate to your home directory
cd ~

# Copy the project from Windows
cp -r /mnt/d/workspace/Demo/mcp_demo ~/laravel-projects/

# Navigate to project
cd ~/laravel-projects/mcp_demo

# Make scripts executable
chmod +x setup-ubuntu.sh
chmod +x test-mcp-tools.sh

# Run the setup script
./setup-ubuntu.sh

# Start the server
php artisan octane:start --host=127.0.0.1 --port=8080

# In another terminal, test the tools
./test-mcp-tools.sh
"@ -ForegroundColor White

Write-Host "`n🌐 Server URLs:" -ForegroundColor Cyan
Write-Host "   Laravel App: http://127.0.0.1:8080" -ForegroundColor White
Write-Host "   MCP Endpoint: http://127.0.0.1:8080/mcp" -ForegroundColor White

Write-Host "`n🧪 Test Commands:" -ForegroundColor Cyan
Write-Host @"
# Test basic connectivity
curl http://127.0.0.1:8080

# Test MCP tools list
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/list", "params": {}}'

# Test a specific tool
curl -X POST http://127.0.0.1:8080/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": {"name": "get_orders", "arguments": {}}}'
"@ -ForegroundColor White

Write-Host "`n📊 Key Changes Made:" -ForegroundColor Yellow
Write-Host "✅ Updated to use streamable_http transport (already configured)" -ForegroundColor Green
Write-Host "✅ Laravel Octane with FrankenPHP (added to composer.json)" -ForegroundColor Green
Write-Host "✅ Created Ubuntu setup automation script" -ForegroundColor Green
Write-Host "✅ Created comprehensive testing script" -ForegroundColor Green
Write-Host "✅ Prepared for production deployment" -ForegroundColor Green

Write-Host "`n⚡ Performance Benefits:" -ForegroundColor Cyan
Write-Host "• Better performance with FrankenPHP" -ForegroundColor White
Write-Host "• Streamable HTTP transport (recommended)" -ForegroundColor White
Write-Host "• Persistent worker processes" -ForegroundColor White
Write-Host "• Better memory management" -ForegroundColor White
Write-Host "• Enterprise-ready security" -ForegroundColor White

Write-Host "`n🔒 Security Improvements:" -ForegroundColor Cyan
Write-Host "• HTTP-based transport (more secure than STDIO)" -ForegroundColor White
Write-Host "• Standard web security practices apply" -ForegroundColor White
Write-Host "• Easy to add authentication middleware" -ForegroundColor White
Write-Host "• Better control over access" -ForegroundColor White

Write-Host "`n📚 Documentation Created:" -ForegroundColor Yellow
Write-Host "• UBUNTU_SETUP_GUIDE.md - Comprehensive setup guide" -ForegroundColor White
Write-Host "• setup-ubuntu.sh - Automated setup script" -ForegroundColor White
Write-Host "• test-mcp-tools.sh - Testing automation script" -ForegroundColor White
Write-Host "• This transition guide" -ForegroundColor White

Write-Host "`n🎯 Ready to proceed? Run this command to start:" -ForegroundColor Green
Write-Host "wsl -d Ubuntu" -ForegroundColor Yellow

Write-Host "`n================================================================" -ForegroundColor Cyan
Write-Host "Happy coding! 🎉" -ForegroundColor Green
