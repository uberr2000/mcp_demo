# Test MCP stdio server
Write-Host "Testing MCP stdio server..."

# Start the server in the background
$process = Start-Process -FilePath "php" -ArgumentList "artisan", "mcp:server", "--debug" -NoNewWindow -PassThru -RedirectStandardInput -RedirectStandardOutput -RedirectStandardError

# Wait a moment for the server to start
Start-Sleep -Seconds 2

# Send a test request
$testRequest = '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}'
Write-Host "Sending request: $testRequest"

# Write to the process stdin
$process.StandardInput.WriteLine($testRequest)
$process.StandardInput.Flush()

# Wait for response
Start-Sleep -Seconds 2

# Read the output
$output = $process.StandardOutput.ReadToEnd()
Write-Host "Response: $output"

# Clean up
$process.Kill()
Write-Host "Test completed."
