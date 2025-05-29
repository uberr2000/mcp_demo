$process = Start-Process -FilePath "php" -ArgumentList "artisan", "mcp:serve" -WorkingDirectory "d:\workspace\Demo\mcp_demo" -RedirectStandardInput $true -RedirectStandardOutput $true -RedirectStandardError $true -UseNewEnvironment -PassThru

# Test initialize
$initMessage = @{
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

Write-Host "Sending initialize message..."
$process.StandardInput.WriteLine($initMessage)
$process.StandardInput.Flush()

Start-Sleep -Seconds 2

# Read response
$response = $process.StandardOutput.ReadLine()
Write-Host "Response: $response"

# Test tools/list
$toolsMessage = @{
    jsonrpc = "2.0"
    id = 2
    method = "tools/list"
} | ConvertTo-Json -Depth 3 -Compress

Write-Host "Sending tools/list message..."
$process.StandardInput.WriteLine($toolsMessage)
$process.StandardInput.Flush()

Start-Sleep -Seconds 2

# Read response
$response = $process.StandardOutput.ReadLine()
Write-Host "Tools response: $response"

$process.Kill()
$process.WaitForExit()
