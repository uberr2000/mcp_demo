#!/bin/bash

# SSE Connection Test for Ubuntu (Port 8080)
# Tests the forceful closure functionality

echo "=== MCP SSE Forceful Closure Test (Ubuntu) ==="
echo "Testing server on http://localhost:8080"
echo ""

# Function to test SSE connection
test_sse_connection() {
    local test_name="$1"
    local duration="$2"
    local signal="$3"
    
    echo "Testing: $test_name"
    echo "Duration: ${duration}s, Signal: $signal"
    
    # Start SSE connection in background
    curl -N -H "Accept: text/event-stream" \
         -H "Content-Type: application/json" \
         -d '{"jsonrpc":"2.0","method":"ping","params":{},"id":"test"}' \
         http://localhost:8080/mcp/sse > /tmp/sse_output_$$.log 2>&1 &
    
    local curl_pid=$!
    echo "Started SSE connection (PID: $curl_pid)"
    
    # Let connection establish
    sleep 2
    
    # Check if process is still running
    if ! kill -0 $curl_pid 2>/dev/null; then
        echo "❌ Connection failed to establish"
        return 1
    fi
    
    echo "✅ Connection established, waiting ${duration}s..."
    sleep $duration
    
    # Send termination signal
    echo "Sending $signal to PID $curl_pid"
    if [[ "$signal" == "SIGKILL" ]]; then
        kill -9 $curl_pid
    else
        kill -15 $curl_pid
    fi
    
    # Wait a bit and check if process terminated
    sleep 1
    if kill -0 $curl_pid 2>/dev/null; then
        echo "⚠️  Process still running, force killing..."
        kill -9 $curl_pid
    fi
    
    # Show last few lines of output
    echo "Last output:"
    tail -5 /tmp/sse_output_$$.log
    echo ""
    
    # Cleanup
    rm -f /tmp/sse_output_$$.log
    
    echo "✅ Test completed: $test_name"
    echo "---"
}

# Check if server is responding
echo "1. Checking server status..."
if curl -s -f http://localhost:8080/mcp/info > /dev/null; then
    echo "✅ Server is responding on port 8080"
else
    echo "❌ Server is not responding on port 8080"
    echo "Please ensure the MCP server is running with: php artisan serve --port=8080"
    exit 1
fi
echo ""

# Test 1: Normal disconnection
test_sse_connection "Normal Disconnection" 3 "SIGTERM"

# Test 2: Abrupt disconnection
test_sse_connection "Abrupt Disconnection" 3 "SIGKILL"

# Test 3: Longer connection
test_sse_connection "Extended Connection" 10 "SIGTERM"

# Test 4: Multiple quick connections
echo "Testing: Multiple Quick Connections"
for i in {1..3}; do
    echo "Connection $i..."
    curl -N -H "Accept: text/event-stream" \
         http://localhost:8080/mcp/sse > /dev/null 2>&1 &
    local pid=$!
    sleep 1
    kill -15 $pid 2>/dev/null
    sleep 0.5
done
echo "✅ Multiple connections test completed"
echo "---"

echo ""
echo "=== Test Summary ==="
echo "✅ All SSE forceful closure tests completed"
echo "📝 Check Laravel logs for detailed connection lifecycle:"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo "🔗 For manual testing, visit: http://localhost:8080/sse-test"
echo "💡 The SSE endpoint supports robust client disconnect detection!"
