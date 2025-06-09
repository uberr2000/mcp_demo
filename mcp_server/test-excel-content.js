import { SendExcelEmailTool } from './tools/SendExcelEmailTool.js';
import fs from 'fs/promises';

async function testExcelContent() {
    console.log('🧪 Testing Excel content generation...\n');

    const tool = new SendExcelEmailTool();
    
    // Test with a small limit to see the actual data
    const input = {
        type: "orders",
        email: "test@example.com", // Using test email to trigger simulation mode
        subject: "Test Excel Content",
        message: "Testing Excel generation",
        limit: 3
    };

    console.log('📝 Testing Excel content generation in simulation mode');
    console.log('Input:', JSON.stringify(input, null, 2));
    console.log('');

    try {
        const result = await tool.execute(input);
        console.log('✅ Excel generation successful!');
        console.log('Result:', JSON.stringify(result, null, 2));
        
        // Check if the Excel file was created (in simulation mode it should show the filename)
        if (result.data && result.data.filename) {
            console.log('\n📄 Excel file details:');
            console.log('Filename:', result.data.filename);
            console.log('Records count:', result.data.records_count);
        }
    } catch (error) {
        console.error('❌ Excel generation failed:', error.message);
        console.error('Full error:', error);
    }
}

testExcelContent();
