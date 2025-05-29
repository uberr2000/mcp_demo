# Simple test: just run the server and see if it responds to a basic message
Write-Host "Testing MCP stdio server..."

# Create a test input file
$testInput = @{
    jsonrpc = "2.0"
    id = 1
    method = "initialize"
    params = @{
        protocolVersion = "2024-11-05"
        capabilities = @{
            tools = @{}
        }
        clientInfo = @{
            name = "test-client"
            version = "1.0.0"
        }
    }
} | ConvertTo-Json -Depth 5 -Compress

$testInput | Out-File -FilePath "test-input.json" -Encoding UTF8

# Run the server with input
Write-Host "Running: php artisan mcp:serve < test-input.json"
$output = & php artisan mcp:serve 2>&1
Write-Host "Server output:"
$output

Remove-Item "test-input.json" -ErrorAction SilentlyContinue
