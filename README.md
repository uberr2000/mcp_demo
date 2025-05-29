# MCP Demo Project

A Laravel-based Model Context Protocol (MCP) demonstration project featuring an e-commerce order management system with AI-powered chat functionality using OpenAI.

## Features

- **Order Management**: Display and manage customer orders with pagination
- **Product Catalog**: Browse supermarket products (sodas, chips, ice cream, etc.)
- **AI Chat Interface**: Query order and product data using natural language in Traditional Chinese
- **Database Integration**: SQLite database with seeded sample data
- **Modern UI**: Responsive design using Tailwind CSS

## Project Structure

### Models
- **Product**: Represents supermarket items with name, price, description, stock, and category
- **Order**: Customer orders with transaction ID, customer name, amount, status, and product relationships

### Database Schema

#### Products Table
- `id` - Primary key
- `name` - Product name (Traditional Chinese)
- `description` - Product description
- `price` - Product price (HKD)
- `stock_quantity` - Available stock
- `category` - Product category (飲料, 零食, 雪糕)
- `created_at`, `updated_at` - Timestamps

#### Orders Table
- `id` - Primary key
- `transaction_id` - Unique transaction identifier (TXN######)
- `name` - Customer name (Traditional Chinese)
- `amount` - Order total amount
- `status` - Order status (pending, processing, completed, cancelled, refunded)
- `product_id` - Foreign key to products table
- `quantity` - Quantity ordered
- `created_at`, `updated_at` - Timestamps

### Sample Data
- **10 Products**: Supermarket items including:
  - 可口可樂 (Coca-Cola)
  - 樂事薯片 (Lay's Chips)
  - 哈根達斯雪糕 (Häagen-Dazs Ice Cream)
  - 百事可樂 (Pepsi)
  - And more...

- **500 Orders**: Randomly generated orders with:
  - Unique transaction IDs
  - Chinese customer names
  - Random products and quantities
  - Various order statuses
  - Realistic timestamps

## AI Chat Functionality

The AI chat interface uses OpenAI's GPT-3.5-turbo model to answer queries about orders and products. The system:

1. **Processes natural language queries** in Traditional Chinese
2. **Searches relevant data** based on keywords and patterns
3. **Provides context** to the AI model with retrieved data
4. **Returns intelligent responses** about orders and products

### Example Queries
- "顯示所有已完成的訂單" (Show all completed orders)
- "TXN000001 的訂單詳情" (Details for order TXN000001)
- "陳大明的所有訂單" (All orders for customer 陳大明)
- "有什麼飲料產品？" (What beverage products are available?)

## Installation & Setup

### Prerequisites
- PHP 8.1+
- Composer
- Node.js & npm (optional, for asset compilation)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd mcp_demo
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure OpenAI API**
   Add your OpenAI API key to `.env`:
   ```
   OPENAI_API_KEY=your_openai_api_key_here
   ```

5. **Database setup**
   The project is configured to use SQLite by default:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start the server**
   ```bash
   php artisan serve
   ```

7. **Access the application**
   Open your browser and navigate to `http://127.0.0.1:8000`

## Configuration

### Database Configuration

**SQLite (Default)**
```env
DB_CONNECTION=sqlite
```

**MySQL (Alternative)**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mcp_demo
DB_USERNAME=root
DB_PASSWORD=your_password
```

For MySQL, create the database first:
```sql
CREATE DATABASE mcp_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### OpenAI Configuration
```env
OPENAI_API_KEY=your_openai_api_key_here
```

## Usage

### Main Dashboard
- View paginated list of orders with details
- Browse product catalog
- Use AI chat to query data

### AI Chat Interface
The chat interface supports various query types:
- Order lookup by transaction ID
- Customer order history
- Product searches
- Status-based filtering
- General inquiries about the data

### API Endpoints
- `GET /` - Main dashboard
- `POST /chat` - AI chat endpoint

## Technical Implementation

### MCP Integration
The project demonstrates MCP concepts by:
1. **Data Retrieval**: Structured database queries based on AI prompts
2. **Context Building**: Formatting retrieved data for AI consumption
3. **Response Generation**: Using OpenAI to generate intelligent responses
4. **User Interface**: Real-time chat interface for natural language queries

### Technologies Used
- **Backend**: Laravel 12, PHP 8.1+
- **Database**: SQLite/MySQL
- **AI**: OpenAI GPT-3.5-turbo
- **Frontend**: Blade templates, Tailwind CSS, jQuery
- **HTTP Client**: OpenAI PHP Client

## Development

### Adding New Features
1. Create new models/controllers as needed
2. Update database migrations and seeders
3. Extend the chat functionality in `ChatController`
4. Add new UI components to the dashboard

### Testing
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

## Troubleshooting

### Database Issues
- Ensure SQLite is enabled in PHP
- For MySQL, check connection credentials
- Run `php artisan config:clear` after configuration changes

### OpenAI Issues
- Verify API key is correct
- Check API quota and usage limits
- Ensure internet connectivity

### Performance
- Consider adding database indexes for large datasets
- Implement caching for frequently accessed data
- Use Laravel queues for heavy AI operations

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Support

For issues or questions, please create an issue in the repository or contact the development team.
