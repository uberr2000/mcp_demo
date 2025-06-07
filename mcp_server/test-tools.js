import { GetOrdersTool } from './tools/GetOrdersTool.js';
import { GetProductsTool } from './tools/GetProductsTool.js';
import db from './database.js';

async function testTools() {
    console.log('=== Testing MCP Tools ===');
    
    try {
        // 測試資料庫連接
        console.log('\n1. Testing database connection...');
        await db.connect();
        console.log('✅ Database connected successfully');

        // 測試是否有資料表
        console.log('\n2. Checking if tables exist...');
        try {
            const tables = await db.query("SHOW TABLES");
            console.log('Available tables:', tables.map(t => Object.values(t)[0]));
        } catch (error) {
            console.log('❌ Error checking tables:', error.message);
        }

        // 測試 GetOrdersTool
        console.log('\n3. Testing GetOrdersTool...');
        const ordersTool = new GetOrdersTool();
        try {
            const ordersResult = await ordersTool.execute({ limit: 5 });
            console.log('✅ GetOrdersTool result:', JSON.stringify(ordersResult, null, 2));
        } catch (error) {
            console.log('❌ GetOrdersTool error:', error.message);
        }

        // 測試 GetProductsTool
        console.log('\n4. Testing GetProductsTool...');
        const productsTool = new GetProductsTool();
        try {
            const productsResult = await productsTool.execute({ limit: 5 });
            console.log('✅ GetProductsTool result:', JSON.stringify(productsResult, null, 2));
        } catch (error) {
            console.log('❌ GetProductsTool error:', error.message);
        }

        // 檢查是否有資料
        console.log('\n5. Checking data count...');
        try {
            const orderCount = await db.query("SELECT COUNT(*) as count FROM orders");
            console.log('Orders count:', orderCount[0].count);
            
            const productCount = await db.query("SELECT COUNT(*) as count FROM products");
            console.log('Products count:', productCount[0].count);
        } catch (error) {
            console.log('❌ Error counting data:', error.message);
        }

    } catch (error) {
        console.error('❌ Test failed:', error);
    } finally {
        await db.close();
        console.log('\n=== Test completed ===');
        process.exit(0);
    }
}

testTools();
