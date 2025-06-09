import { GetOrderAnalyticsTool } from './tools/GetOrderAnalyticsTool.js';

async function testAllAnalytics() {
    const tool = new GetOrderAnalyticsTool();

    try {
        console.log('=== Testing all order analytics types ===\n');
        
        // Test daily analytics
        console.log('1. Daily Analytics:');
        const dailyResult = await tool.execute({
            analytics_type: 'daily',
            limit: 5
        });
        console.log(JSON.stringify(dailyResult, null, 2));
        console.log('\n');

        // Test monthly analytics
        console.log('2. Monthly Analytics:');
        const monthlyResult = await tool.execute({
            analytics_type: 'monthly',
            limit: 5
        });
        console.log(JSON.stringify(monthlyResult, null, 2));
        console.log('\n');

        // Test status analytics
        console.log('3. Status Analytics:');
        const statusResult = await tool.execute({
            analytics_type: 'status'
        });
        console.log(JSON.stringify(statusResult, null, 2));
        console.log('\n');

        // Test product analytics
        console.log('4. Product Analytics:');
        const productResult = await tool.execute({
            analytics_type: 'product',
            limit: 5
        });
        console.log(JSON.stringify(productResult, null, 2));
        console.log('\n');

        // Test with date filter
        console.log('5. Product Analytics with date filter (May 2025):');
        const filteredResult = await tool.execute({
            analytics_type: 'product',
            date_from: '2025-05-01',
            date_to: '2025-05-31',
            limit: 3
        });
        console.log(JSON.stringify(filteredResult, null, 2));

    } catch (error) {
        console.error('Error testing analytics:', error);
    }
}

testAllAnalytics();
