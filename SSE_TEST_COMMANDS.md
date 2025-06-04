# Quick SSE Test Commands for Ubuntu (Port 8080)

## 1. Basic SSE Connection Test
```bash
curl -N -H "Accept: text/event-stream" \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","method":"ping","params":{},"id":"test-1"}' \
     http://localhost:8080/mcp/sse
```

## 2. Test SSE with timeout (auto-disconnect after 10 seconds)
```bash
timeout 10 curl -N -H "Accept: text/event-stream" \
               -H "Content-Type: application/json" \
               -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":"test-2"}' \
               http://localhost:8080/mcp/sse
```

## 3. Background connection test (forceful closure)
```bash
# Start connection in background
curl -N -H "Accept: text/event-stream" \
     http://localhost:8080/mcp/sse > sse_output.log 2>&1 &

# Get the process ID
SSE_PID=$!
echo "SSE Connection PID: $SSE_PID"

# Wait a few seconds
sleep 5

# Check if still running
if kill -0 $SSE_PID 2>/dev/null; then
    echo "Connection is active, terminating..."
    kill -15 $SSE_PID  # SIGTERM (normal)
    # or: kill -9 $SSE_PID  # SIGKILL (forceful)
fi

# Check output
echo "=== SSE Output ==="
cat sse_output.log
rm sse_output.log
```

## 4. Test tools/list via SSE
```bash
curl -N -H "Accept: text/event-stream" \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":"tools-test"}' \
     http://localhost:8080/mcp/sse
```

## 5. Test with invalid method (error handling)
```bash
curl -N -H "Accept: text/event-stream" \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","method":"invalid","params":{},"id":"error-test"}' \
     http://localhost:8080/mcp/sse
```

## 6. Check server info endpoint
```bash
curl -s http://localhost:8080/mcp/info | jq .
```

## 7. Monitor Laravel logs during testing
```bash
# In a separate terminal
tail -f storage/logs/laravel.log | grep -E "(SSE|Connection|mcp_sse)"
```

## 8. Test with browser
Open: http://localhost:8080/sse-test

## Expected SSE Output Format:
```
event: welcome
data: {"jsonrpc":"2.0","result":{"status":"connected","connection_id":"mcp_sse_..."},"id":"welcome"}

event: response  
data: {"jsonrpc":"2.0","result":{...},"id":"test-1"}

event: ping
data: {"event":"ping","data":{"timestamp":1234567890,"ping_count":1,"uptime":15}}

event: ping
data: {"event":"ping","data":{"timestamp":1234567905,"ping_count":2,"uptime":30}}
```

## Forceful Closure Testing:
1. Start SSE connection
2. Monitor Laravel logs: `tail -f storage/logs/laravel.log`
3. Kill client process (Ctrl+C or kill command)
4. Check logs for disconnect detection and cleanup messages

## Key Log Messages to Look For:
- "SSE Connection establishing" - Connection start
- "Starting connection maintenance loop" - Keepalive start  
- "Connection detected as closed" - Disconnect detection
- "Forcing connection closure" - Cleanup initiation
- "Connection forcefully closed" - Cleanup completion
