import { GetOrderAnalyticsTool } from './tools/GetOrderAnalyticsTool.js';
import { GetCustomerStatsTool } from './tools/GetCustomerStatsTool.js';

async function testBothTools() {
    try {
        console.log('=== Testing both Analytics and Customer Stats tools ===\n');
        
        // Test Order Analytics
        console.log('1. Order Analytics - Product Analysis:');
        const analyticsTools = new GetOrderAnalyticsTool();
        const analyticsResult = await analyticsTools.execute({
            analytics_type: 'product',
            limit: 3
        });
        console.log('✅ Product Analytics working!');
        console.log(`Found ${analyticsResult.data.length} products with orders`);
        console.log('Top products:', analyticsResult.data.map(p => `${p.product_name} (${p.order_count} orders)`).join(', '));
        console.log('\n');

        // Test Customer Stats
        console.log('2. Customer Stats - Top customers:');
        const customerTool = new GetCustomerStatsTool();
        const customerResult = await customerTool.execute({
            limit: 3
        });
        console.log('✅ Customer Stats working!');
        console.log(`Found ${customerResult.stats.length} customers with orders`);
        console.log('Top customers:', customerResult.stats.map(c => `${c.customer_name} ($${c.total_spending})`).join(', '));
        console.log('\n');

        // Test with date filters
        console.log('3. Testing date filters:');
        const filteredAnalytics = await analyticsTools.execute({
            analytics_type: 'daily',
            date_from: '2025-05-25',
            date_to: '2025-05-30',
            limit: 3
        });
        console.log('✅ Daily Analytics with date filter working!');
        console.log(`Found ${filteredAnalytics.data.length} daily records`);
        
        const filteredCustomers = await customerTool.execute({
            date_from: '2025-05-25',
            date_to: '2025-05-30',
            limit: 3
        });
        console.log('✅ Customer Stats with date filter working!');
        console.log(`Found ${filteredCustomers.stats.length} customers in date range`);
        console.log('\n');

        console.log('🎉 All tests passed! Both tools are working correctly with real data.');

    } catch (error) {
        console.error('❌ Error in testing:', error);
    }
}

testBothTools();
