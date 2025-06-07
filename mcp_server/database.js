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
    }

    async connect() {
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
                acquireTimeout: 60000,
                timeout: 60000
            });

            console.log('Database connected successfully');
            return this.connection;
        } catch (error) {
            console.error('Database connection failed:', error);
            throw error;
        }
    }    async query(sql, params = []) {
        try {
            if (!this.connection) {
                await this.connect();
            }
            
            // 使用 query 而不是 execute 來處理參數化查詢
            const [rows] = await this.connection.query(sql, params);
            return rows;
        } catch (error) {
            console.error('Database query error:', error);
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
