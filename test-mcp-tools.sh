#!/bin/bash

# MCP Tools Test Script
# Run this script to test all MCP tools after starting the Laravel Octane server

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Server configuration
MCP_SERVER_URL="http://127.0.0.1:8080/mcp"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

print_error() {
    echo -e "${RED}[FAIL]${NC} $1"
}

# Function to test MCP endpoint
test_mcp_call() {
    local method="$1"
    local params="$2"
    local description="$3"
    
    print_status "Testing: $description"
    
    local response=$(curl -s -X POST "$MCP_SERVER_URL" \
        -H "Content-Type: application/json" \
        -d "{\"jsonrpc\": \"2.0\", \"id\": 1, \"method\": \"$method\", \"params\": $params}")
    
    if echo "$response" | grep -q '"error"'; then
        print_error "$description - Error in response"
        echo "Response: $response"
        return 1
    elif echo "$response" | grep -q '"result"'; then
        print_success "$description - Success"
        return 0
    else
        print_error "$description - Unexpected response format"
        echo "Response: $response"
        return 1
    fi
}

echo "🧪 Starting MCP Tools Test Suite..."
echo "Server URL: $MCP_SERVER_URL"
echo ""

# Check if server is running
print_status "Checking if MCP server is running..."
if curl -s "$MCP_SERVER_URL" > /dev/null; then
    print_success "MCP server is responding"
else
    print_error "MCP server is not responding at $MCP_SERVER_URL"
    echo "Please make sure the server is running with: php artisan octane:start --host=127.0.0.1 --port=8080"
    exit 1
fi

echo ""

# Test 1: List available tools
print_status "Testing tools/list endpoint..."
TOOLS_RESPONSE=$(curl -s -X POST "$MCP_SERVER_URL" \
    -H "Content-Type: application/json" \
    -d '{"jsonrpc": "2.0", "id": 1, "method": "tools/list", "params": {}}')

if echo "$TOOLS_RESPONSE" | grep -q '"tools"'; then
    print_success "Tools list retrieved successfully"
    echo "Available tools:"
    echo "$TOOLS_RESPONSE" | jq -r '.result.tools[].name' 2>/dev/null || echo "$TOOLS_RESPONSE"
else
    print_error "Failed to retrieve tools list"
    echo "Response: $TOOLS_RESPONSE"
fi

echo ""

# Test 2: Get Orders Tool
test_mcp_call "tools/call" '{"name": "get_orders", "arguments": {}}' "Get Orders Tool"

echo ""

# Test 3: Get Products Tool
test_mcp_call "tools/call" '{"name": "get_products", "arguments": {}}' "Get Products Tool"

echo ""

# Test 4: Get Customer Stats Tool
test_mcp_call "tools/call" '{"name": "get_customer_stats", "arguments": {}}' "Get Customer Stats Tool"

echo ""

# Test 5: Get Order Analytics Tool
test_mcp_call "tools/call" '{"name": "get_order_analytics", "arguments": {}}' "Get Order Analytics Tool"

echo ""

# Test 6: Test with parameters (if tools support them)
print_status "Testing tools with parameters..."

# Test GetOrdersTool with customer_id parameter
test_mcp_call "tools/call" '{"name": "get_orders", "arguments": {"customer_id": "123"}}' "Get Orders Tool with customer_id"

echo ""

# Test GetProductsTool with category parameter
test_mcp_call "tools/call" '{"name": "get_products", "arguments": {"category": "electronics"}}' "Get Products Tool with category"

echo ""

# Test server capabilities
print_status "Testing server capabilities..."
CAPABILITIES_RESPONSE=$(curl -s -X POST "$MCP_SERVER_URL" \
    -H "Content-Type: application/json" \
    -d '{"jsonrpc": "2.0", "id": 1, "method": "initialize", "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test-client", "version": "1.0.0"}}}')

if echo "$CAPABILITIES_RESPONSE" | grep -q '"capabilities"'; then
    print_success "Server capabilities retrieved successfully"
else
    print_error "Failed to retrieve server capabilities"
    echo "Response: $CAPABILITIES_RESPONSE"
fi

echo ""

# Performance test
print_status "Running performance test (10 consecutive requests)..."
start_time=$(date +%s%N)
for i in {1..10}; do
    curl -s -X POST "$MCP_SERVER_URL" \
        -H "Content-Type: application/json" \
        -d '{"jsonrpc": "2.0", "id": '$i', "method": "tools/call", "params": {"name": "get_orders", "arguments": {}}}' > /dev/null
done
end_time=$(date +%s%N)
duration=$((($end_time - $start_time) / 1000000)) # Convert to milliseconds

print_success "Performance test completed: 10 requests in ${duration}ms (avg: $((duration / 10))ms per request)"

echo ""
echo "🎉 MCP Tools Test Suite Completed!"
echo ""
echo "If all tests passed, your MCP server is working correctly with streamable HTTP transport."
echo "You can now integrate this server with MCP clients using the endpoint: $MCP_SERVER_URL"
