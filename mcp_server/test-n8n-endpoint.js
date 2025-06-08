// Test script for n8n compatibility endpoint

const SERVER_URL = 'http://localhost:8080';

async function testN8nEndpoint() {
    console.log('🧪 Testing /tools/list endpoint for n8n compatibility...\n');
    
    try {
        // Test 1: Valid n8n request
        console.log('📝 Test 1: Valid JSON-RPC request');
        const validRequest = {
            method: 'tools/list',
            params: {},
            jsonrpc: '2.0',
            id: 1
        };
        
        console.log('Request:', JSON.stringify(validRequest, null, 2));
        
        const response = await fetch(`${SERVER_URL}/tools/list`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(validRequest)
        });
        
        const result = await response.json();
        console.log('Response Status:', response.status);
        console.log('Response:', JSON.stringify(result, null, 2));
        
        if (result.result && result.result.tools) {
            console.log('✅ Valid response received with tools list');
            console.log(`📊 Tools count: ${result.result.tools.length}`);
        } else {
            console.log('❌ Invalid response format');
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 2: Invalid JSON-RPC version
        console.log('📝 Test 2: Invalid JSON-RPC version');
        const invalidJsonRpc = {
            method: 'tools/list',
            params: {},
            jsonrpc: '1.0',
            id: 2
        };
        
        console.log('Request:', JSON.stringify(invalidJsonRpc, null, 2));
        
        const response2 = await fetch(`${SERVER_URL}/tools/list`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invalidJsonRpc)
        });
        
        const result2 = await response2.json();
        console.log('Response Status:', response2.status);
        console.log('Response:', JSON.stringify(result2, null, 2));
        
        if (result2.error && result2.error.code === -32600) {
            console.log('✅ Correctly rejected invalid JSON-RPC version');
        } else {
            console.log('❌ Should have rejected invalid JSON-RPC version');
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 3: Invalid method
        console.log('📝 Test 3: Invalid method');
        const invalidMethod = {
            method: 'invalid/method',
            params: {},
            jsonrpc: '2.0',
            id: 3
        };
        
        console.log('Request:', JSON.stringify(invalidMethod, null, 2));
        
        const response3 = await fetch(`${SERVER_URL}/tools/list`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(invalidMethod)
        });
        
        const result3 = await response3.json();
        console.log('Response Status:', response3.status);
        console.log('Response:', JSON.stringify(result3, null, 2));
        
        if (result3.error && result3.error.code === -32601) {
            console.log('✅ Correctly rejected invalid method');
        } else {
            console.log('❌ Should have rejected invalid method');
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 4: Request without ID
        console.log('📝 Test 4: Request without ID');
        const noIdRequest = {
            method: 'tools/list',
            params: {},
            jsonrpc: '2.0'
        };
        
        console.log('Request:', JSON.stringify(noIdRequest, null, 2));
        
        const response4 = await fetch(`${SERVER_URL}/tools/list`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(noIdRequest)
        });
        
        const result4 = await response4.json();
        console.log('Response Status:', response4.status);
        console.log('Response:', JSON.stringify(result4, null, 2));
        
        if (result4.id === null && result4.result) {
            console.log('✅ Correctly handled request without ID');
        } else {
            console.log('❌ Should handle request without ID correctly');
        }
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Make sure the server is running on port 3000');
    }
}

// Run the test
testN8nEndpoint();
