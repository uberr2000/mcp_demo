import db from './database.js';

async function debugQuery() {
    try {
        console.log('=== Debugging GetOrdersTool query ===');
        
        // First, let's check if we have orders with status 'completed' in May 2025
        console.log('\n1. Checking orders with status "completed" in May 2025:');
        const sql1 = `
            SELECT COUNT(*) as count 
            FROM orders 
            WHERE status = 'completed' 
            AND created_at >= '2025-05-01' 
            AND created_at <= '2025-05-31 23:59:59'
        `;
        const result1 = await db.query(sql1);
        console.log('Count of completed orders in May:', result1[0].count);

        // Check some sample data
        console.log('\n2. Sample completed orders in May 2025:');
        const sql2 = `
            SELECT o.id, o.transaction_id, o.name, o.status, o.created_at, p.name as product_name
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id
            WHERE o.status = 'completed' 
            AND o.created_at >= '2025-05-01' 
            AND o.created_at <= '2025-05-31 23:59:59'
            LIMIT 5
        `;
        const result2 = await db.query(sql2);
        console.log('Sample orders:', result2);

        // Now let's test the exact query that GetOrdersTool would build
        console.log('\n3. Testing exact GetOrdersTool query:');
        const sql3 = `
            SELECT o.id, o.transaction_id, o.name, o.quantity, o.amount, o.status, o.created_at, o.updated_at, 
                   p.id as product_id, p.name as product_name, p.price as product_price 
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id 
            WHERE 1=1 
            AND o.status = ? 
            AND o.created_at >= ? 
            AND o.created_at <= ? 
            ORDER BY o.created_at DESC 
            LIMIT ?
        `;
        const params = ['completed', '2025-05-01', '2025-05-31 23:59:59', 5];
        console.log('Query:', sql3);
        console.log('Params:', params);
        
        const result3 = await db.query(sql3, params);
        console.log('Query result:', result3);
        console.log('Result count:', result3.length);

    } catch (error) {
        console.error('Debug query error:', error);
    } finally {
        await db.close();
    }
}

debugQuery();
