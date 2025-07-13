#!/bin/bash

# Build script for PHP Code Intelligence Tool PHAR distribution

set -e

echo "=== PHP Code Intelligence Tool - PHAR Builder ==="
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "Error: This script must be run from the project root directory"
    exit 1
fi

# Check if build directory exists
if [ ! -d "build" ]; then
    mkdir -p build
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION"

if [ $(php -r "echo version_compare(PHP_VERSION, '8.0.0', '<') ? 1 : 0;") -eq 1 ]; then
    echo "Error: PHP 8.0 or higher is required"
    exit 1
fi

# Check if PHAR creation is enabled
PHAR_READONLY=$(php -r "echo ini_get('phar.readonly') ? 'true' : 'false';")
if [ "$PHAR_READONLY" = "true" ]; then
    echo "Warning: phar.readonly is enabled. PHAR creation might fail."
    echo "To fix this, add 'phar.readonly=0' to your php.ini or run with:"
    echo "php -d phar.readonly=0 build/build-phar.php"
    echo ""
fi

# Install production dependencies
echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-scripts

# Run tests to ensure everything is working
echo ""
echo "Running tests..."
composer test

# Build the PHAR
echo ""
echo "Building PHAR archive..."
php -d phar.readonly=0 build/build-phar.php

# Restore development dependencies
echo ""
echo "Restoring development dependencies..."
composer install

echo ""
echo "✓ Build completed successfully!"
echo ""
echo "The PHAR file is available at: build/php-code-intel.phar"
echo ""
echo "Usage examples:"
echo "  ./build/php-code-intel.phar --version"
echo "  ./build/php-code-intel.phar find-usages \"MyClass\" --path=src/"
echo "  ./build/php-code-intel.phar index src/ --stats"
echo ""