// Test both products and orders email export
import { SendExcelEmailTool } from "./tools/SendExcelEmailTool.js";

async function testBothEmailTypes() {
    console.log('🧪 Testing SendExcelEmailTool for both products and orders...\n');
    
    const tool = new SendExcelEmailTool();
    
    try {
        // Test 1: Products export
        console.log('📝 Test 1: Products export');
        const productsParams = {
            type: 'products',
            email: 'test@example.com',
            limit: 5
        };
        
        const productsResult = await tool.execute(productsParams);
        console.log('✅ Products export successful!');
        console.log('Records exported:', productsResult.data.records_count);
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 2: Orders export
        console.log('📝 Test 2: Orders export');
        const ordersParams = {
            type: 'orders',
            email: 'test@example.com',
            limit: 5
        };
        
        const ordersResult = await tool.execute(ordersParams);
        console.log('✅ Orders export successful!');
        console.log('Records exported:', ordersResult.data.records_count);
        
        console.log('\n' + '='.repeat(60) + '\n');
        
        // Test 3: With filters
        console.log('📝 Test 3: Orders export with filters');
        const filteredParams = {
            type: 'orders',
            email: 'test@example.com',
            filters: {
                status: 'completed'
            },
            limit: 10
        };
        
        const filteredResult = await tool.execute(filteredParams);
        console.log('✅ Filtered orders export successful!');
        console.log('Records exported:', filteredResult.data.records_count);
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Stack:', error.stack);
    }
}

testBothEmailTypes();
