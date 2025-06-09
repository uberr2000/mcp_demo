import { SendExcelEmailTool } from './tools/SendExcelEmailTool.js';

async function testFilteredOrdersExport() {
    console.log('🧪 Testing filtered orders export functionality...\n');

    const tool = new SendExcelEmailTool();
    
    const input = {
        type: "orders",
        email: "terry.hk796@gmail.com",
        subject: "5月份最近3筆訂單匯出",
        message: "請參考附件中的最近3筆5月份訂單資料。",
        filters: {
            date_from: "2024-05-01",
            date_to: "2024-05-31"
        },
        limit: 3
    };

    console.log('📝 Test: Send filtered orders email');
    console.log('Input:', JSON.stringify(input, null, 2));
    console.log('');

    try {
        console.log('Attempting to send filtered orders email...');
        const result = await tool.execute(input);
        console.log('✅ Filtered orders email sent successfully!');
        console.log('Result:', JSON.stringify(result, null, 2));
    } catch (error) {
        console.error('❌ Filtered orders email sending failed:', error.message);
        console.error('Full error:', error);
    }
}

testFilteredOrdersExport();
