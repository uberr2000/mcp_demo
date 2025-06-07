import db from './database.js';

async function checkTableStructure() {
    try {
        await db.connect();
        
        console.log('=== Orders Table Structure ===');
        const ordersColumns = await db.query("DESCRIBE orders");
        ordersColumns.forEach(col => {
            console.log(`${col.Field}: ${col.Type} ${col.Null === 'YES' ? '(NULL)' : '(NOT NULL)'}`);
        });
        
        console.log('\n=== Products Table Structure ===');
        const productsColumns = await db.query("DESCRIBE products");
        productsColumns.forEach(col => {
            console.log(`${col.Field}: ${col.Type} ${col.Null === 'YES' ? '(NULL)' : '(NOT NULL)'}`);
        });
        
        console.log('\n=== Sample Orders Data ===');
        const sampleOrders = await db.query("SELECT * FROM orders LIMIT 2");
        console.log(JSON.stringify(sampleOrders, null, 2));
        
        console.log('\n=== Sample Products Data ===');
        const sampleProducts = await db.query("SELECT * FROM products LIMIT 2");
        console.log(JSON.stringify(sampleProducts, null, 2));
        
    } catch (error) {
        console.error('Error:', error);
    } finally {
        await db.close();
        process.exit(0);
    }
}

checkTableStructure();
