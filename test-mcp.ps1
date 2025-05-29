# MCP Service Test Script
# PowerShell script to test MCP service functionality

$baseUrl = "http://127.0.0.1:8000"

Write-Host "=== MCP Service 測試 ===" -ForegroundColor Green

# Test 1: Ping
Write-Host "`n1. 測試 Ping..." -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/mcp/ping" -Method GET
    Write-Host "✓ Ping 成功: $($response.status)" -ForegroundColor Green
} catch {
    Write-Host "✗ Ping 失敗: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Get Tools
Write-Host "`n2. 獲取可用工具..." -ForegroundColor Yellow
try {
    $tools = Invoke-RestMethod -Uri "$baseUrl/mcp/tools" -Method GET
    Write-Host "✓ 找到 $($tools.tools.Count) 個工具:" -ForegroundColor Green
    foreach ($tool in $tools.tools.PSObject.Properties) {
        Write-Host "  - $($tool.Value.name): $($tool.Value.description)" -ForegroundColor Cyan
    }
} catch {
    Write-Host "✗ 獲取工具失敗: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Call get_orders tool
Write-Host "`n3. 測試訂單查詢工具..." -ForegroundColor Yellow
try {
    $body = @{
        name = "get_orders"
        arguments = @{
            limit = 3
        }
    } | ConvertTo-Json -Depth 3

    $response = Invoke-RestMethod -Uri "$baseUrl/mcp/tools/call" -Method POST -Body $body -ContentType "application/json"
    Write-Host "✓ 訂單查詢成功" -ForegroundColor Green
    $content = $response.content[0].text | ConvertFrom-Json
    Write-Host "  返回 $($content.returned_count) 筆訂單 (總共找到 $($content.total_found) 筆)" -ForegroundColor Cyan
} catch {
    Write-Host "✗ 訂單查詢失敗: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Call get_products tool
Write-Host "`n4. 測試產品查詢工具..." -ForegroundColor Yellow
try {
    $body = @{
        name = "get_products"
        arguments = @{
            limit = 3
        }
    } | ConvertTo-Json -Depth 3

    $response = Invoke-RestMethod -Uri "$baseUrl/mcp/tools/call" -Method POST -Body $body -ContentType "application/json"
    Write-Host "✓ 產品查詢成功" -ForegroundColor Green
    $content = $response.content[0].text | ConvertFrom-Json
    Write-Host "  返回 $($content.total) 個產品" -ForegroundColor Cyan
} catch {
    Write-Host "✗ 產品查詢失敗: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: Call customer stats tool
Write-Host "`n5. 測試客戶統計工具..." -ForegroundColor Yellow
try {
    $body = @{
        name = "get_customer_stats"
        arguments = @{}
    } | ConvertTo-Json -Depth 3

    $response = Invoke-RestMethod -Uri "$baseUrl/mcp/tools/call" -Method POST -Body $body -ContentType "application/json"
    Write-Host "✓ 客戶統計成功" -ForegroundColor Green
    $content = $response.content[0].text | ConvertFrom-Json
    Write-Host "  總訂單數: $($content.total_orders)" -ForegroundColor Cyan
    Write-Host "  總金額: $($content.total_amount)" -ForegroundColor Cyan
} catch {
    Write-Host "✗ 客戶統計失敗: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 6: Call order analytics tool
Write-Host "`n6. 測試訂單分析工具..." -ForegroundColor Yellow
try {
    $body = @{
        name = "get_order_analytics"
        arguments = @{
            group_by = "status"
        }
    } | ConvertTo-Json -Depth 3

    $response = Invoke-RestMethod -Uri "$baseUrl/mcp/tools/call" -Method POST -Body $body -ContentType "application/json"
    Write-Host "✓ 訂單分析成功" -ForegroundColor Green
    $content = $response.content[0].text | ConvertFrom-Json
    Write-Host "  分析類型: $($content.type)" -ForegroundColor Cyan
    Write-Host "  總訂單數: $($content.summary.total_orders)" -ForegroundColor Cyan
} catch {
    Write-Host "✗ 訂單分析失敗: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n=== 測試完成 ===" -ForegroundColor Green
Write-Host "`n📋 n8n 整合資訊:" -ForegroundColor Yellow
Write-Host "Base URL: $baseUrl" -ForegroundColor Cyan
Write-Host "Tools Endpoint: $baseUrl/mcp/tools (GET)" -ForegroundColor Cyan
Write-Host "Call Tool Endpoint: $baseUrl/mcp/tools/call (POST)" -ForegroundColor Cyan
Write-Host "Test Page: http://127.0.0.1:8000/mcp-test" -ForegroundColor Cyan
