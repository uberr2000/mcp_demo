import db from './database.js';

async function testProductsData() {
    console.log('🔍 检查数据库中的产品数据...\n');
    
    try {
        // 检查产品总数
        const countResult = await db.query('SELECT COUNT(*) as total FROM products');
        console.log('产品总数:', countResult[0].total);
        
        // 获取前几条产品数据
        const products = await db.query('SELECT * FROM products LIMIT 5');
        console.log('产品数据样本:', JSON.stringify(products, null, 2));
        
        // 检查产品表结构
        const columns = await db.query('DESCRIBE products');
        console.log('\n产品表结构:');
        columns.forEach(col => {
            console.log(`- ${col.Field}: ${col.Type} (${col.Null === 'YES' ? 'nullable' : 'not null'})`);
        });
        
    } catch (error) {
        console.error('❌ 数据库查询失败:', error.message);
    }
}

testProductsData();
