#!/bin/bash

# Laravel MCP Server Ubuntu Setup Script
# Run this script in Ubuntu WSL to set up your Laravel MCP server

set -e  # Exit on any error

echo "🚀 Starting Laravel MCP Server Ubuntu Setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running in WSL
if ! grep -qEi "(Microsoft|WSL)" /proc/version &> /dev/null; then
    print_warning "This script is designed for Ubuntu WSL. Continuing anyway..."
fi

# Step 1: Update system
print_status "Updating Ubuntu system packages..."
sudo apt update && sudo apt upgrade -y
print_success "System updated successfully"

# Step 2: Install PHP 8.2 and extensions
print_status "Installing PHP 8.2 and required extensions..."
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-curl \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-gd \
    php8.2-mysql \
    php8.2-sqlite3 \
    php8.2-redis \
    php8.2-opcache
print_success "PHP 8.2 installed successfully"

# Step 3: Install Composer
print_status "Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    print_success "Composer installed successfully"
else
    print_success "Composer already installed"
fi

# Step 4: Install additional tools
print_status "Installing additional tools..."
sudo apt install -y git curl wget unzip
print_success "Additional tools installed"

# Step 5: Create project directory
PROJECT_DIR="$HOME/laravel-projects"
print_status "Creating project directory at $PROJECT_DIR..."
mkdir -p "$PROJECT_DIR"
print_success "Project directory created"

# Step 6: Copy project from Windows (if exists)
WINDOWS_PROJECT_PATH="/mnt/d/workspace/Demo/mcp_demo"
if [ -d "$WINDOWS_PROJECT_PATH" ]; then
    print_status "Copying project from Windows directory..."
    cp -r "$WINDOWS_PROJECT_PATH" "$PROJECT_DIR/"
    cd "$PROJECT_DIR/mcp_demo"
    print_success "Project copied successfully"
else
    print_warning "Windows project directory not found at $WINDOWS_PROJECT_PATH"
    print_status "Please manually copy your project to $PROJECT_DIR/mcp_demo"
    exit 1
fi

# Step 7: Install project dependencies
print_status "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader
print_success "Dependencies installed"

# Step 8: Set up environment
print_status "Setting up environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    print_success "Environment file created"
fi

# Generate application key
php artisan key:generate --force
print_success "Application key generated"

# Create SQLite database
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    print_success "SQLite database created"
fi

# Step 9: Install Laravel Octane with FrankenPHP
print_status "Installing Laravel Octane with FrankenPHP..."
php artisan octane:install frankenphp --force
print_success "Laravel Octane with FrankenPHP installed"

# Step 10: Set permissions
print_status "Setting up file permissions..."
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
print_success "Permissions set correctly"

# Step 11: Run migrations (if needed)
print_status "Running database migrations..."
php artisan migrate --force
print_success "Migrations completed"

# Step 12: Clear caches
print_status "Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
print_success "Caches cleared"

echo ""
print_success "🎉 Laravel MCP Server setup completed successfully!"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "1. Start the server: ${GREEN}php artisan octane:start --host=127.0.0.1 --port=8080${NC}"
echo "2. Test the server: ${GREEN}curl http://127.0.0.1:8080${NC}"
echo "3. Test MCP tools: ${GREEN}curl -X POST http://127.0.0.1:8080/mcp -H 'Content-Type: application/json' -d '{\"jsonrpc\": \"2.0\", \"id\": 1, \"method\": \"tools/list\", \"params\": {}}'${NC}"
echo ""
echo -e "${YELLOW}Project location:${NC} $PROJECT_DIR/mcp_demo"
echo -e "${YELLOW}To navigate to project:${NC} cd $PROJECT_DIR/mcp_demo"
echo ""
