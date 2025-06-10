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
        this.connection = null;
    }    async connect() {
        if (this.connection) {
            return this.connection;
        }

        try {
            this.connection = await mysql.createConnection({
                host: process.env.DB_HOST || '127.0.0.1',
                port: process.env.DB_PORT || 3306,
                user: process.env.DB_USERNAME || 'root',
                password: process.env.DB_PASSWORD || '',
                database: process.env.DB_DATABASE || 'mcp_demo',
                timezone: '+00:00',
                // 连接池配置
                connectionLimit: 10,
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

            console.log('Database connected successfully');
            return this.connection;
        } catch (error) {
            console.error('Database connection failed:', error);
            throw error;
        }
    }async query(sql, params = []) {
        try {
            // 检查连接是否存在且有效
            if (!this.connection) {
                await this.connect();
            } else {
                // 检查连接是否仍然有效
                try {
                    await this.connection.ping();
                } catch (pingError) {
                    console.log('Connection lost, reconnecting...');
                    this.connection = null;
                    await this.connect();
                }
            }
            
            // 使用 query 而不是 execute 來處理參數化查詢
            const [rows] = await this.connection.query(sql, params);
            return rows;
        } catch (error) {
            console.error('Database query error:', error);
            
            // 如果是连接错误，尝试重连一次
            if (error.code === 'PROTOCOL_CONNECTION_LOST' || 
                error.code === 'ECONNRESET' || 
                error.message.includes('closed state')) {
                console.log('Connection error detected, attempting to reconnect...');
                this.connection = null;
                try {
                    await this.connect();
                    const [rows] = await this.connection.query(sql, params);
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
        if (this.connection) {
            await this.connection.end();
            this.connection = null;
            console.log('Database connection closed');
        }
    }
}

// 創建單例實例
const db = new Database();

export default db;
