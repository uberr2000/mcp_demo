import db from './database.js';

async function testSimpleQuery() {
    try {
        await db.connect();
        
        console.log('Testing simple query without parameters...');
        const result1 = await db.query("SELECT * FROM products LIMIT 3");
        console.log('✅ Simple query worked:', result1.length, 'rows');
        
        console.log('\nTesting query with integer parameter...');
        const result2 = await db.query("SELECT * FROM products LIMIT ?", [3]);
        console.log('❌ This might fail...');
        
        console.log('\nTesting query with string parameter...');
        const result3 = await db.query("SELECT * FROM products WHERE name LIKE ?", ['%可樂%']);
        console.log('Result:', result3.length, 'rows');
        
    } catch (error) {
        console.error('Error:', error.message);
        console.error('SQL State:', error.sqlState);
        console.error('Error Code:', error.code);
    } finally {
        await db.close();
        process.exit(0);
    }
}

testSimpleQuery();
