import dotenv from 'dotenv';
import { SendExcelEmailTool } from './mcp_server/tools/SendExcelEmailTool.js';

// Load environment variables
dotenv.config();

async function testFinalEmail() {
    console.log('🧪 Testing Final Email Configuration...\n');
    
    // Check environment variables
    console.log('📋 Environment Variables Check:');
    console.log('AWS_REGION:', process.env.AWS_REGION || '❌ Not set');
    console.log('AWS_ACCESS_KEY_ID:', process.env.AWS_ACCESS_KEY_ID ? '✅ Set' : '❌ Not set');
    console.log('AWS_SECRET_ACCESS_KEY:', process.env.AWS_SECRET_ACCESS_KEY ? '✅ Set' : '❌ Not set');
    console.log('SES_FROM_EMAIL:', process.env.SES_FROM_EMAIL || '❌ Not set');
    console.log('');
    
    // Check if we have all required SES variables
    const hasSESConfig = process.env.AWS_ACCESS_KEY_ID && 
                        process.env.AWS_SECRET_ACCESS_KEY && 
                        process.env.SES_FROM_EMAIL;
    
    if (hasSESConfig) {
        console.log('✅ AWS SES configuration is complete - should use SES');
    } else {
        console.log('❌ AWS SES configuration is incomplete - will use simulation');
    }
    console.log('');
    
    try {
        const emailTool = new SendExcelEmailTool();
        
        console.log('📧 Testing email sending with AWS SES...');
        
        const params = {
            type: 'products',
            email: 'terry.hk796@gmail.com', // User's email address
            subject: 'Test Email from MCP Demo - Final Test',
            message: 'This is a final test email to verify AWS SES is working correctly. If you receive this email, the configuration is successful!',
            limit: 5
        };
        
        console.log('📤 Sending email with parameters:', {
            type: params.type,
            email: params.email,
            subject: params.subject,
            limit: params.limit
        });
        console.log('');
        
        const result = await emailTool.execute(params);
        
        console.log('✅ Email tool execution completed:');
        console.log(JSON.stringify(result, null, 2));
        
        if (result.success) {
            console.log('\n🎉 SUCCESS! Email should have been sent via AWS SES.');
            console.log('📮 Please check your email inbox (terry.hk796@gmail.com) for the message.');
            console.log('📋 Check spam/junk folder if not in inbox.');
        } else {
            console.log('\n❌ Email sending failed.');
        }
        
    } catch (error) {
        console.error('❌ Error during email test:', error.message);
        console.error('Full error:', error);
    }
}

testFinalEmail();
