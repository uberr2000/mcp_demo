// Simulate n8n's exact request format
const n8nRequest = {
    method: 'tools/list',
    params: {},
    jsonrpc: '2.0',
    id: 1
};

console.log('🔧 Simulating n8n request to /tools/list endpoint...\n');
console.log('Request payload (exactly as n8n would send):');
console.log(JSON.stringify(n8nRequest, null, 2));

async function simulateN8nRequest() {
    try {
        const response = await fetch('http://localhost:8080/tools/list', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(n8nRequest)
        });
        
        const result = await response.json();
        
        console.log('\n📊 Response:');
        console.log('Status:', response.status);
        console.log('Headers:', Object.fromEntries(response.headers.entries()));
        console.log('\nBody:');
        console.log(JSON.stringify(result, null, 2));
        
        // Validate the response format
        if (result.jsonrpc === '2.0' && 
            result.result && 
            Array.isArray(result.result.tools) &&
            result.id === 1) {
            console.log('\n✅ Response format is fully compatible with n8n expectations!');
            console.log(`📋 Available tools: ${result.result.tools.length}`);
            
            // Show tool names
            const toolNames = result.result.tools.map(tool => tool.name);
            console.log(`🔧 Tool names: ${toolNames.join(', ')}`);
        } else {
            console.log('\n❌ Response format may not be compatible with n8n');
        }
        
    } catch (error) {
        console.error('❌ Request failed:', error.message);
    }
}

simulateN8nRequest();
