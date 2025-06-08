// Test the GetProductsTool to verify the schema fix
async function testGetProductsTool() {
    console.log('🧪 Testing GetProductsTool after schema fix...\n');
    
    try {
        // Test 1: Valid request with stock_quantity
        console.log('📝 Test 1: Request with stock_quantity parameter');
        const requestWithStock = {
            jsonrpc: '2.0',
            method: 'tools/call',
            params: {
                name: 'get_products',
                arguments: {
                    name: 'laptop',
                    stock_quantity: 10
                }
            },
            id: 1
        };
        
        console.log('Request:', JSON.stringify(requestWithStock, null, 2));
        
        const response1 = await fetch('http://localhost:8080/message?sessionId=test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestWithStock)
        });
        
        const result1 = await response1.json();
        console.log('Response Status:', response1.status);
        console.log('Response:', JSON.stringify(result1, null, 2));
        
        if (response1.status === 200 && !result1.error) {
            console.log('✅ stock_quantity parameter accepted successfully');
        } else {
            console.log('❌ Still getting validation error');
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 2: Request with old "stock" parameter (should fail)
        console.log('📝 Test 2: Request with old "stock" parameter (should be rejected)');
        const requestWithOldStock = {
            jsonrpc: '2.0',
            method: 'tools/call',
            params: {
                name: 'get_products',
                arguments: {
                    name: 'laptop',
                    stock: 10  // This should now be rejected
                }
            },
            id: 2
        };
        
        console.log('Request:', JSON.stringify(requestWithOldStock, null, 2));
        
        const response2 = await fetch('http://localhost:8080/message?sessionId=test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestWithOldStock)
        });
        
        const result2 = await response2.json();
        console.log('Response Status:', response2.status);
        console.log('Response:', JSON.stringify(result2, null, 2));
        
        if (result2.error && result2.error.message.includes('"stock" is not allowed')) {
            console.log('✅ Old "stock" parameter correctly rejected');
        } else {
            console.log('❌ Old "stock" parameter should be rejected');
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 3: Check updated tools list schema
        console.log('📝 Test 3: Verify tools list has correct schema');
        const toolsListRequest = {
            method: 'tools/list',
            params: {},
            jsonrpc: '2.0',
            id: 3
        };
        
        const response3 = await fetch('http://localhost:8080/tools/list', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(toolsListRequest)
        });
        
        const result3 = await response3.json();
        const getProductsTool = result3.result.tools.find(tool => tool.name === 'get_products');
        
        if (getProductsTool && getProductsTool.inputSchema.properties.stock_quantity) {
            console.log('✅ Tools list schema correctly shows stock_quantity');
            console.log('Schema properties:', Object.keys(getProductsTool.inputSchema.properties));
        } else {
            console.log('❌ Tools list schema issue');
        }
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Make sure the server is running on port 8080');
    }
}

testGetProductsTool();
