#!/bin/bash
# MTC_SHARE4U - Startup Script for Termux

echo "=========================================="
echo "   MTC_SHARE4U - Starting Server"
echo "=========================================="
echo ""

# Create necessary directories if they don't exist
mkdir -p database
mkdir -p public/uploads/videos
mkdir -p public/uploads/images
mkdir -p public/uploads/files
mkdir -p public/uploads/thumbnails
mkdir -p logs

# Set permissions (for Termux, chmod might not work the same)
echo "✓ Directories created/verified"

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed!"
    echo "   Install it with: pkg install php"
    exit 1
fi

echo "✓ PHP found: $(php -v | head -n 1)"
echo ""

# Start server
echo "🚀 Starting server on http://127.0.0.1:8080"
echo ""
echo "Access the app at: http://127.0.0.1:8080"
echo "Press Ctrl+C to stop the server"
echo "=========================================="
echo ""

# Start PHP server in public directory
php -S 127.0.0.1:8080 -t public