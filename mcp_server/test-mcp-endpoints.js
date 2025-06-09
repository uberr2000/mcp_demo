// import fetch from 'node-fetch'; // Use Node.js built-in fetch (Node 18+)

async function testMCPEndpoints() {
    const baseURL = 'http://localhost:8080';
    
    console.log('=== Testing MCP Server Endpoints ===\n');

    try {
        // Test health endpoint
        console.log('1. Testing health endpoint...');
        const healthResponse = await fetch(`${baseURL}/health`);
        console.log('Health status:', healthResponse.status);
        console.log('\n');

        // Test get_order_analytics - product type
        console.log('2. Testing get_order_analytics (product)...');
        const analyticsRequest = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                method: 'tools/call',
                params: {
                    name: 'get_order_analytics',
                    arguments: {
                        analytics_type: 'product',
                        limit: 3
                    }
                }
            })
        };

        const analyticsResponse = await fetch(`${baseURL}/message`, analyticsRequest);
        const analyticsData = await analyticsResponse.json();
        console.log('Analytics response status:', analyticsResponse.status);
        console.log('Analytics data:', JSON.stringify(analyticsData, null, 2));
        console.log('\n');

        // Test get_customer_stats
        console.log('3. Testing get_customer_stats...');
        const customerRequest = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                method: 'tools/call',
                params: {
                    name: 'get_customer_stats',
                    arguments: {
                        limit: 3
                    }
                }
            })
        };

        const customerResponse = await fetch(`${baseURL}/message`, customerRequest);
        const customerData = await customerResponse.json();
        console.log('Customer stats response status:', customerResponse.status);
        console.log('Customer stats data:', JSON.stringify(customerData, null, 2));
        console.log('\n');

        console.log('🎉 All MCP endpoint tests completed!');

    } catch (error) {
        console.error('❌ Error testing MCP endpoints:', error);
    }
}

testMCPEndpoints();
