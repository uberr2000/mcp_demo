import { GetOrderAnalyticsTool } from './tools/GetOrderAnalyticsTool.js';

async function testProductAnalytics() {
    const tool = new GetOrderAnalyticsTool();

    try {
        console.log('Testing product analytics...');
        
        const result = await tool.execute({
            analytics_type: 'product',
            limit: 10
        });

        console.log('Product Analytics Result:');
        console.log(JSON.stringify(result, null, 2));

        if (result.data && result.data.length > 0) {
            console.log(`\nFound ${result.data.length} products with orders`);
            result.data.forEach((product, index) => {
                console.log(`${index + 1}. ${product.product_name}: ${product.order_count} orders, $${product.total_amount}`);
            });
        } else {
            console.log('No product analytics data found');
        }

    } catch (error) {
        console.error('Error testing product analytics:', error);
    }
}

testProductAnalytics();
