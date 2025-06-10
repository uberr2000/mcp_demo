import { GetOrdersTool } from './tools/GetOrdersTool.js';

async function testGetOrdersTool() {
    const tool = new GetOrdersTool();

    try {
        console.log('=== Testing GetOrdersTool with exact parameters ===');
        
        const params = {
            transaction_id: "",
            customer_name: "",
            status: "completed",
            product_name: "",
            min_amount: 0,
            max_amount: 0,
            date_from: "2025-05-01",
            date_to: "2025-05-31",
            limit: 5
        };

        console.log('Input parameters:', JSON.stringify(params, null, 2));

        const result = await tool.execute(params);
        console.log('Tool result:', JSON.stringify(result, null, 2));

    } catch (error) {
        console.error('Error testing GetOrdersTool:', error);
    }
}

testGetOrdersTool();
