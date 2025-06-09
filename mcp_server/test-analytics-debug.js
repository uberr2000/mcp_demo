import db from './database.js';

async function debugAnalytics() {
    try {
        console.log('=== DEBUG ANALYTICS TOOL ===\n');
        
        // Check total orders
        const [totalOrders] = await db.execute('SELECT COUNT(*) as count FROM orders');
        console.log('Total orders in database:', totalOrders[0].count);
        
        // Check orders with different statuses
        const [statusCounts] = await db.execute(`
            SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status 
            ORDER BY count DESC
        `);
        console.log('\nOrders by status:');
        statusCounts.forEach(row => {
            console.log(`  ${row.status}: ${row.count}`);
        });
        
        // Check recent orders
        const [recentOrders] = await db.execute(`
            SELECT id, status, created_at, product_id, quantity, amount 
            FROM orders 
            ORDER BY created_at DESC 
            LIMIT 5
        `);
        console.log('\nRecent orders:');
        recentOrders.forEach(order => {
            console.log(`  ID: ${order.id}, Status: ${order.status}, Date: ${order.created_at}, Product: ${order.product_id}, Qty: ${order.quantity}, Amount: ${order.amount}`);
        });
        
        // Check if product_id exists and is not null
        const [nullProducts] = await db.execute(`
            SELECT COUNT(*) as count 
            FROM orders 
            WHERE product_id IS NULL
        `);
        console.log(`\nOrders with NULL product_id: ${nullProducts[0].count}`);
        
        // Check product analytics query directly
        const [productAnalytics] = await db.execute(`
            SELECT 
                product_id,
                COUNT(*) as order_count,
                SUM(quantity) as total_quantity,
                SUM(amount) as total_revenue,
                AVG(amount) as average_order_value
            FROM orders 
            WHERE status = 'completed'
            AND created_at >= '2024-01-01'
            AND created_at <= '2024-12-31'
            GROUP BY product_id
            ORDER BY total_quantity DESC
            LIMIT 30
        `);
        console.log('\nProduct analytics (with filters):');
        productAnalytics.forEach(row => {
            console.log(`  Product ${row.product_id}: ${row.order_count} orders, ${row.total_quantity} qty, $${row.total_revenue}`);
        });
        
        // Check without status filter
        const [productAnalyticsNoStatus] = await db.execute(`
            SELECT 
                product_id,
                COUNT(*) as order_count,
                SUM(quantity) as total_quantity,
                SUM(amount) as total_revenue,
                AVG(amount) as average_order_value
            FROM orders 
            WHERE created_at >= '2024-01-01'
            AND created_at <= '2024-12-31'
            GROUP BY product_id
            ORDER BY total_quantity DESC
            LIMIT 30
        `);
        console.log('\nProduct analytics (no status filter):');
        productAnalyticsNoStatus.forEach(row => {
            console.log(`  Product ${row.product_id}: ${row.order_count} orders, ${row.total_quantity} qty, $${row.total_revenue}`);
        });
        
        // Check date range
        const [dateRange] = await db.execute(`
            SELECT 
                MIN(created_at) as earliest,
                MAX(created_at) as latest
            FROM orders
        `);
        console.log('\nDate range in orders:');
        console.log(`  Earliest: ${dateRange[0].earliest}`);
        console.log(`  Latest: ${dateRange[0].latest}`);
        
    } catch (error) {
        console.error('Error:', error);
    } finally {
        process.exit(0);
    }
}

debugAnalytics();
