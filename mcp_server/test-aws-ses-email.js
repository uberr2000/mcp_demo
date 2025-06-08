// Test AWS SES email functionality
import { SendExcelEmailTool } from "./tools/SendExcelEmailTool.js";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

// Configure dotenv to load from the parent directory's .env file
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, "../.env") });

async function testAWSSESEmail() {
    console.log('🧪 Testing AWS SES email functionality...\n');
    
    // Check if AWS credentials are loaded
    console.log('Environment variables check:');
    console.log('AWS_ACCESS_KEY_ID:', process.env.AWS_ACCESS_KEY_ID ? '✓ Set' : '❌ Missing');
    console.log('AWS_SECRET_ACCESS_KEY:', process.env.AWS_SECRET_ACCESS_KEY ? '✓ Set' : '❌ Missing');
    console.log('AWS_REGION:', process.env.AWS_REGION || '❌ Missing');
    console.log('SES_FROM_EMAIL:', process.env.SES_FROM_EMAIL || '❌ Missing');
    console.log('EMAIL_SIMULATION_MODE:', process.env.EMAIL_SIMULATION_MODE || 'Not set (good!)');
    console.log();
    
    const tool = new SendExcelEmailTool();
    
    try {
        // Test sending a real email via AWS SES
        console.log('📝 Test: Send products email via AWS SES');
        const params = {
            type: 'products',
            email: 'terry.hk796@gmail.com',
            subject: 'Test: 產品列表導出',
            message: 'This is a test email from the MCP server using AWS SES.',
            limit: 5
        };
        
        console.log('Input:', JSON.stringify(params, null, 2));
        console.log('\nAttempting to send email...');
        
        const result = await tool.execute(params);
        console.log('✅ Email sent successfully!');
        console.log('Result:', JSON.stringify(result, null, 2));
        
    } catch (error) {
        console.error('❌ Email sending failed:', error.message);
        console.error('Full error:', error);
        
        // Check if it's an AWS credentials issue
        if (error.message.includes('credentials') || error.message.includes('InvalidUserID')) {
            console.log('\n💡 This might be an AWS credentials or permissions issue.');
            console.log('Please verify:');
            console.log('1. Your AWS credentials are correct');
            console.log('2. Your AWS user has SES permissions');
            console.log('3. The from email is verified in AWS SES');
            console.log('4. AWS SES is not in sandbox mode (or the recipient email is verified)');
        }
    }
}

testAWSSESEmail();
