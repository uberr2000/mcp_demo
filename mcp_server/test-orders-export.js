import { SendExcelEmailTool } from './tools/SendExcelEmailTool.js';

async function testOrdersExport() {
    console.log('🧪 Testing orders export functionality...\n');

    const tool = new SendExcelEmailTool();
    
    const input = {
        type: "orders",
        email: "terry.hk796@gmail.com",
        subject: "Test: 訂單導出",
        message: "This is a test email for orders export.",
        limit: 5
    };

    console.log('📝 Test: Send orders email via AWS SES');
    console.log('Input:', JSON.stringify(input, null, 2));
    console.log('');

    try {
        console.log('Attempting to send orders email...');
        const result = await tool.execute(input);
        console.log('✅ Orders email sent successfully!');
        console.log('Result:', JSON.stringify(result, null, 2));
    } catch (error) {
        console.error('❌ Orders email sending failed:', error.message);
        console.error('Full error:', error);
    }
}

testOrdersExport();
