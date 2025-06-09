import db from './database.js';

async function checkTableStructure() {
    try {
        console.log('Checking products table structure...');
        const productsStructure = await db.query('DESCRIBE products');
        console.log('Products table columns:');
        productsStructure.forEach(col => {
            console.log(`- ${col.Field} (${col.Type})`);
        });

        console.log('\nChecking orders table structure...');
        const ordersStructure = await db.query('DESCRIBE orders');
        console.log('Orders table columns:');
        ordersStructure.forEach(col => {
            console.log(`- ${col.Field} (${col.Type})`);
        });

        console.log('\nSample products data:');
        const products = await db.query('SELECT * FROM products LIMIT 3');
        console.log(products);

        console.log('\nSample orders data:');
        const orders = await db.query('SELECT * FROM orders LIMIT 3');
        console.log(orders);

    } catch (error) {
        console.error('Error checking table structure:', error);
    } finally {
        await db.close();
    }
}

checkTableStructure();
