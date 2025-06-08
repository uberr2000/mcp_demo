// Direct test of GetProductsTool to verify schema fix
import { GetProductsTool } from "./tools/GetProductsTool.js";

async function testProductsToolDirectly() {
    console.log('🧪 Testing GetProductsTool directly...\n');
    
    const tool = new GetProductsTool();
    
    try {
        // Test 1: Valid request with stock_quantity
        console.log('📝 Test 1: Valid parameters with stock_quantity');
        const validParams = {
            name: 'laptop',
            stock_quantity: 10,
            limit: 5
        };
        
        console.log('Input:', JSON.stringify(validParams, null, 2));
        
        const result1 = await tool.execute(validParams);
        console.log('✅ Execution successful!');
        console.log('Result:', JSON.stringify(result1, null, 2));
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 2: Invalid request with old "stock" parameter
        console.log('📝 Test 2: Invalid parameters with old "stock" field');
        const invalidParams = {
            name: 'laptop',
            stock: 10,  // This should be rejected
            limit: 5
        };
        
        console.log('Input:', JSON.stringify(invalidParams, null, 2));
        
        try {
            const result2 = await tool.execute(invalidParams);
            console.log('❌ Should have failed validation but succeeded:', result2);
        } catch (error) {
            console.log('✅ Correctly rejected invalid parameter:', error.message);
        }
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 3: Show tool schema
        console.log('📝 Test 3: Tool schema verification');
        console.log('Tool name:', tool.name);
        console.log('Tool description:', tool.description);
        console.log('Input schema properties:', Object.keys(tool.inputSchema.properties));
        
        if (tool.inputSchema.properties.stock_quantity) {
            console.log('✅ Tool schema correctly includes stock_quantity');
        } else {
            console.log('❌ Tool schema missing stock_quantity');
        }
        
        if (!tool.inputSchema.properties.stock) {
            console.log('✅ Tool schema correctly excludes old stock field');
        } else {
            console.log('❌ Tool schema still includes old stock field');
        }
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Stack:', error.stack);
    }
}

testProductsToolDirectly();
