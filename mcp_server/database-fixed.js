import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 加載 Laravel .env 文件
dotenv.config({ path: path.resolve(__dirname, '../.env') });

class Database {
    constructor() {
        this.pool = null;
    }

    async connect() {
        if (this.pool) {
            return this.pool;
        }

        try {
            this.pool = mysql.createPool({
                host: process.env.DB_HOST || '127.0.0.1',
                port: process.env.DB_PORT || 3306,
                user: process.env.DB_USERNAME || 'root',
                password: process.env.DB_PASSWORD || '',
                database: process.env.DB_DATABASE || 'mcp_demo',
                timezone: '+00:00',
                // 连接池配置
                connectionLimit: 10,
                queueLimit: 0,
                // 自动重连配置
                reconnect: true,
                // 连接超时配置
                connectTimeout: 60000,
                acquireTimeout: 60000,
                // 空闲超时
                idleTimeout: 300000,
                // 启用 keep alive
                keepAliveInitialDelay: 0,
                enableKeepAlive: true
            });

            console.log('Database pool created successfully');
            return this.pool;
        } catch (error) {
            console.error('Database pool creation failed:', error);
            throw error;
        }
    }

    async query(sql, params = []) {
        try {
            // 确保连接池已创建
            if (!this.pool) {
                await this.connect();
            }
            
            // 从连接池获取连接并执行查询
            const [rows] = await this.pool.query(sql, params);
            return rows;
        } catch (error) {
            console.error('Database query error:', error);
            
            // 如果是连接错误，尝试重新创建连接池
            if (error.code === 'PROTOCOL_CONNECTION_LOST' || 
                error.code === 'ECONNRESET' || 
                error.message.includes('closed state') ||
                error.message.includes('Connection lost')) {
                console.log('Connection error detected, recreating pool...');
                this.pool = null;
                try {
                    await this.connect();
                    const [rows] = await this.pool.query(sql, params);
                    return rows;
                } catch (retryError) {
                    console.error('Retry connection failed:', retryError);
                    throw retryError;
                }
            }
            
            throw error;
        }
    }

    async close() {
        if (this.pool) {
            await this.pool.end();
            this.pool = null;
            console.log('Database pool closed');
        }
    }
}

// 創建單例實例
const db = new Database();

export default db;
