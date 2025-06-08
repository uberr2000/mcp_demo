// Test AWS SES email sending with real configuration
import { SendExcelEmailTool } from "./tools/SendExcelEmailTool.js";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

// Load environment variables
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, "../.env") });

async function testRealEmailSending() {
    console.log('🧪 Testing SendExcelEmailTool with AWS SES configuration...\n');
    
    const tool = new SendExcelEmailTool();
    
    try {
        console.log('📝 Testing AWS SES email sending');
        console.log('From:', process.env.SES_FROM_EMAIL);
        console.log('AWS Region:', process.env.AWS_REGION);
        console.log('AWS Access Key ID:', process.env.AWS_ACCESS_KEY_ID?.substring(0, 8) + '...');
        
        const params = {
            type: 'products',
            email: 'terry.hk796@gmail.com',
            subject: 'Test Product Export from MCP Server',
            message: 'This is a test email sent from the MCP server using AWS SES.',
            limit: 5
        };
        
        console.log('\nSending email with params:', JSON.stringify(params, null, 2));
        
        const result = await tool.execute(params);
        console.log('\n✅ Email sent successfully!');
        console.log('Result:', JSON.stringify(result, null, 2));
        
    } catch (error) {
        console.error('\n❌ Email sending failed:', error.message);
        console.error('Full error:', error);
        
        // Provide helpful debugging info
        if (error.message.includes('UnauthorizedOperation') || error.message.includes('AccessDenied')) {
            console.log('\n🔍 Possible causes:');
            console.log('- AWS credentials may be incorrect');
            console.log('- IAM user needs SES permissions');
            console.log('- AWS region might be wrong');
        }
        
        if (error.message.includes('MessageRejected') || error.message.includes('not verified')) {
            console.log('\n🔍 Possible causes:');
            console.log('- The FROM email address needs to be verified in AWS SES');
            console.log('- SES might be in sandbox mode (can only send to verified addresses)');
        }
    }
}

testRealEmailSending();
