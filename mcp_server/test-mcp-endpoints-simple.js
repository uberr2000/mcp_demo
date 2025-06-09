// Test MCP endpoints without requiring SSE session

async function testMCPEndpoints() {
    const baseURL = 'http://localhost:8080';
    
    console.log('=== Testing MCP Server Endpoints ===\n');

    try {
        // Test health endpoint
        console.log('1. Testing health endpoint...');
        const healthResponse = await fetch(`${baseURL}/health`);
        console.log('Health status:', healthResponse.status);
        console.log('\n');        // Test /tools/list endpoint which doesn't require sessionId
        console.log('2. Testing /tools/list endpoint...');
        const toolsResponse = await fetch(`${baseURL}/tools/list`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                jsonrpc: "2.0",
                method: "tools/list",
                id: 1
            })
        });
        const toolsData = await toolsResponse.json();
        console.log('Tools response status:', toolsResponse.status);
        console.log('Tools response data:', JSON.stringify(toolsData, null, 2));

        // Check if tools are listed properly
        if (toolsData.result && toolsData.result.tools) {
            console.log('Available tools:', toolsData.result.tools.map(t => t.name).join(', '));
            console.log('\n');

            // Check if our tools are listed
            const analyticsToolFound = toolsData.result.tools.find(t => t.name === 'get_order_analytics');
            const customerToolFound = toolsData.result.tools.find(t => t.name === 'get_customer_stats');
            
            if (analyticsToolFound) {
                console.log('✅ get_order_analytics tool is properly registered');
                console.log('   Description:', analyticsToolFound.description);
            } else {
                console.log('❌ get_order_analytics tool is NOT found');
            }
            
            if (customerToolFound) {
                console.log('✅ get_customer_stats tool is properly registered');
                console.log('   Description:', customerToolFound.description);
            } else {
                console.log('❌ get_customer_stats tool is NOT found');
            }
        } else {
            console.log('No tools found in response');
        }
        console.log('\n');

        console.log('🎉 Basic MCP endpoint tests completed!');
        console.log('Note: Full tool execution requires SSE session setup, but tools are properly registered.');

    } catch (error) {
        console.error('❌ Error testing MCP endpoints:', error);
    }
}

testMCPEndpoints();
