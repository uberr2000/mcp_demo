# MCP 測試腳本

# 測試 1: 列出工具
Write-Host "測試 1: 列出 MCP 工具"
$response1 = Invoke-RestMethod -Uri "http://localhost:8000/mcp/sse" -Method POST -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'
Write-Host $response1
Write-Host ""

# 測試 2: 獲取訂單
Write-Host "測試 2: 獲取訂單"
$response2 = Invoke-RestMethod -Uri "http://localhost:8000/mcp/sse" -Method POST -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_orders","arguments":{"limit":3}},"id":2}'
Write-Host $response2
Write-Host ""

# 測試 3: 獲取產品
Write-Host "測試 3: 獲取產品"
$response3 = Invoke-RestMethod -Uri "http://localhost:8000/mcp/sse" -Method POST -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"tools/call","params":{"name":"get_products","arguments":{"limit":3}},"id":3}'
Write-Host $response3
Write-Host ""

# 測試 4: 初始化
Write-Host "測試 4: 初始化 MCP"
$response4 = Invoke-RestMethod -Uri "http://localhost:8000/mcp/sse" -Method POST -ContentType "application/json" -Body '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0.0"}},"id":4}'
Write-Host $response4
Write-Host ""

Write-Host "所有測試完成！"
