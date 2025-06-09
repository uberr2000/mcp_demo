import { SendExcelEmailTool } from './tools/SendExcelEmailTool.js';

async function debugDataStructure() {
    console.log('🔍 Debugging data structure...\n');

    const tool = new SendExcelEmailTool();
    
    try {
        // Test products data
        console.log('📦 Testing products data:');
        const productsData = await tool.getProductsData({}, 3);
        console.log('Products data:', JSON.stringify(productsData, null, 2));
        console.log('Products count:', productsData.length);
        
        console.log('\n📋 Testing orders data:');
        const ordersData = await tool.getOrdersData({}, 3);
        console.log('Orders data:', JSON.stringify(ordersData, null, 2));
        console.log('Orders count:', ordersData.length);
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error('Full error:', error);
    }
}

debugDataStructure();
