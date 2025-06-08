// Test email configuration
import nodemailer from 'nodemailer';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load environment variables
dotenv.config({ path: path.resolve(__dirname, "../.env") });

async function testEmailConfig() {
    console.log('🧪 Testing email configuration...\n');
    
    // Check if SMTP is configured
    if (!process.env.SMTP_HOST || !process.env.SMTP_USER || !process.env.SMTP_PASS) {
        console.log('❌ SMTP configuration missing!');
        console.log('Required environment variables:');
        console.log('- SMTP_HOST:', process.env.SMTP_HOST || 'NOT SET');
        console.log('- SMTP_USER:', process.env.SMTP_USER || 'NOT SET');
        console.log('- SMTP_PASS:', process.env.SMTP_PASS ? '***SET***' : 'NOT SET');
        console.log('\n📝 To set up Gmail SMTP:');
        console.log('1. Go to your Google Account settings');
        console.log('2. Enable 2-Factor Authentication');
        console.log('3. Generate an App Password for "Mail"');
        console.log('4. Use that app password in SMTP_PASS');
        return;
    }
    
    console.log('✅ SMTP configuration found!');
    console.log('- Host:', process.env.SMTP_HOST);
    console.log('- User:', process.env.SMTP_USER);
    console.log('- Port:', process.env.SMTP_PORT || 587);
    
    try {
        // Create transporter
        const transporter = nodemailer.createTransporter({
            host: process.env.SMTP_HOST,
            port: process.env.SMTP_PORT || 587,
            secure: process.env.SMTP_PORT === '465',
            auth: {
                user: process.env.SMTP_USER,
                pass: process.env.SMTP_PASS,
            },
        });
        
        // Test connection
        console.log('\n🔍 Testing SMTP connection...');
        await transporter.verify();
        console.log('✅ SMTP connection successful!');
        
        // Send test email
        console.log('\n📧 Sending test email...');
        const info = await transporter.sendMail({
            from: process.env.SMTP_FROM || process.env.SMTP_USER,
            to: process.env.SMTP_USER, // Send to yourself for testing
            subject: 'MCP Demo - Email Test ✅',
            text: 'This is a test email from your MCP Demo server. If you receive this, email configuration is working correctly!',
            html: `
                <h2>🎉 Email Configuration Success!</h2>
                <p>This is a test email from your MCP Demo server.</p>
                <p>If you receive this email, your email configuration is working correctly!</p>
                <hr>
                <p><small>Sent at: ${new Date().toLocaleString()}</small></p>
            `
        });
        
        console.log('✅ Test email sent successfully!');
        console.log('Message ID:', info.messageId);
        console.log('Check your inbox for the test email.');
        
    } catch (error) {
        console.error('❌ Email test failed:', error.message);
        
        if (error.code === 'EAUTH') {
            console.log('\n🔑 Authentication failed. Common solutions:');
            console.log('1. Make sure you\'re using an App Password, not your regular Gmail password');
            console.log('2. Enable 2-Factor Authentication on your Google account');
            console.log('3. Generate a new App Password specifically for this app');
        } else if (error.code === 'ENOTFOUND') {
            console.log('\n🌐 Network/DNS issue. Check your internet connection.');
        } else {
            console.log('\n🔧 Error details:', error);
        }
    }
}

testEmailConfig();
