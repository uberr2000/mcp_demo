// Test the SendExcelEmailTool to verify the fix
import { SendExcelEmailTool } from "./tools/SendExcelEmailTool.js";

async function testSendExcelEmailTool() {
    console.log('🧪 Testing SendExcelEmailTool after email fix...\n');
    
    const tool = new SendExcelEmailTool();
    
    try {
        // Test with minimal parameters (should use simulation mode)
        console.log('📝 Test: Send products email (simulation mode)');
        const params = {
            type: 'products',
            email: 'terry.hk796@gmail.com',
            subject: '產品列表導出',
            message: '請查閱附加的產品列表。',
            limit: 10
        };
        
        console.log('Input:', JSON.stringify(params, null, 2));
        
        const result = await tool.execute(params);
        console.log('✅ Execution successful!');
        console.log('Result:', JSON.stringify(result, null, 2));
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Stack:', error.stack);
    }
}

testSendExcelEmailTool();
