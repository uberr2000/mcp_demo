$headers = @{
    "Content-Type" = "application/json"
}

$body = @{
    name = "get_products"
    arguments = @{
        limit = 3
    }
} | ConvertTo-Json -Depth 3

Write-Host "Testing MCP tools endpoint..."
try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8001/mcp/tools/call" -Method POST -Body $body -Headers $headers
    Write-Host "Success!"
    $response | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    Write-Host "Response: $($_.ErrorDetails.Message)"
}
