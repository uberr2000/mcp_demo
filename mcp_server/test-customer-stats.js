import { GetCustomerStatsTool } from './tools/GetCustomerStatsTool.js';

async function testCustomerStats() {
    const tool = new GetCustomerStatsTool();

    try {
        console.log('=== Testing Customer Stats ===\n');
        
        // Test all customers (top 10)
        console.log('1. Top 10 customers by spending:');
        const result1 = await tool.execute({
            limit: 10
        });
        console.log(JSON.stringify(result1, null, 2));
        console.log('\n');

        if (result1.stats && result1.stats.length > 0) {
            console.log('Top customers:');
            result1.stats.forEach((customer, index) => {
                console.log(`${index + 1}. ${customer.customer_name}: ${customer.total_orders} orders, $${customer.total_spending} total, $${customer.avg_order_amount.toFixed(2)} avg`);
            });
        }
        console.log('\n');

        // Test with customer name filter
        console.log('2. Customer stats with name filter (張):');
        const result2 = await tool.execute({
            customer_name: '張',
            limit: 5
        });
        console.log(JSON.stringify(result2, null, 2));
        console.log('\n');

        // Test with status filter
        console.log('3. Customer stats for completed orders only:');
        const result3 = await tool.execute({
            status: 'completed',
            limit: 5
        });
        console.log(JSON.stringify(result3, null, 2));
        console.log('\n');

        // Test with date filter
        console.log('4. Customer stats for May 2025:');
        const result4 = await tool.execute({
            date_from: '2025-05-01',
            date_to: '2025-05-31',
            limit: 5
        });
        console.log(JSON.stringify(result4, null, 2));

    } catch (error) {
        console.error('Error testing customer stats:', error);
    }
}

testCustomerStats();
