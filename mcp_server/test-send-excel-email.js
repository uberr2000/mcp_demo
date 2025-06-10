import { SendExcelEmailTool } from './tools/SendExcelEmailTool.js';

async function testSendExcelEmail() {
    const tool = new SendExcelEmailTool();

    try {
        console.log('=== Testing SendExcelEmailTool ===');
        
        const params = {
            type: "orders",
            email: "terry.hk796@gmail.com",
            subject: "訂單數據出口",
            message: "以下是最近的訂單數據的Excel匯出。",
            limit: 10  // Start with small limit for testing
        };

        console.log('Input parameters:', JSON.stringify(params, null, 2));
        console.log('Starting tool execution...');

        const startTime = Date.now();
        const result = await tool.execute(params);
        const endTime = Date.now();

        console.log('Tool execution completed successfully!');
        console.log('Execution time:', (endTime - startTime) / 1000, 'seconds');
        console.log('Result:', JSON.stringify(result, null, 2));

    } catch (error) {
        console.error('Error testing SendExcelEmailTool:', error);
        console.error('Error stack:', error.stack);
    }
}

testSendExcelEmail();
