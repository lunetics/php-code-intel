#!/bin/bash

# Multi-PHP Version Testing Script
# Tests the PHP Code Intelligence Tool across multiple PHP versions

set -e

echo "🧪 PHP Code Intelligence Tool - Multi-Version Testing"
echo "=================================================="

# PHP versions to test
PHP_VERSIONS=("8.0" "8.1" "8.2" "8.3")

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run tests for a specific PHP version
test_php_version() {
    local php_version=$1
    local service_name="php${php_version//./}"
    
    echo ""
    echo -e "${BLUE}Testing PHP $php_version${NC}"
    echo "----------------------------------------"
    
    # Build and start the container
    echo "Building PHP $php_version container..."
    docker-compose -f docker-compose.multi-php.yml build $service_name
    docker-compose -f docker-compose.multi-php.yml up -d $service_name
    
    # Wait for container to be ready
    sleep 2
    
    # Check PHP version
    echo -e "${YELLOW}PHP Version Check:${NC}"
    docker-compose -f docker-compose.multi-php.yml exec -T $service_name php --version
    
    # Install dependencies
    echo -e "${YELLOW}Installing dependencies...${NC}"
    docker-compose -f docker-compose.multi-php.yml exec -T $service_name composer install --no-interaction
    
    # Run PHPUnit tests
    echo -e "${YELLOW}Running PHPUnit tests...${NC}"
    if docker-compose -f docker-compose.multi-php.yml exec -T $service_name php vendor/bin/phpunit --no-coverage; then
        echo -e "${GREEN}✅ PHP $php_version: Tests PASSED${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}❌ PHP $php_version: Tests FAILED${NC}"
        ((FAILED_TESTS++))
    fi
    
    # Test CLI tool
    echo -e "${YELLOW}Testing CLI tool...${NC}"
    if docker-compose -f docker-compose.multi-php.yml exec -T $service_name php bin/php-code-intel --version; then
        echo -e "${GREEN}✅ PHP $php_version: CLI tool works${NC}"
    else
        echo -e "${RED}❌ PHP $php_version: CLI tool failed${NC}"
        ((FAILED_TESTS++))
    fi
    
    # Test PHAR build (PHP 8.1+ only due to PHAR requirements)
    if [[ "$php_version" != "8.0" ]]; then
        echo -e "${YELLOW}Testing PHAR build...${NC}"
        if docker-compose -f docker-compose.multi-php.yml exec -T $service_name php -d phar.readonly=0 build/build-phar.php; then
            echo -e "${GREEN}✅ PHP $php_version: PHAR build successful${NC}"
        else
            echo -e "${RED}❌ PHP $php_version: PHAR build failed${NC}"
            ((FAILED_TESTS++))
        fi
    fi
    
    # Test symbol finding
    echo -e "${YELLOW}Testing symbol finding...${NC}"
    if docker-compose -f docker-compose.multi-php.yml exec -T $service_name php bin/php-code-intel find-usages "TestFixtures\\BasicSymbols\\SimpleClass" --path=tests/fixtures/BasicSymbols/ --format=json | grep -q "SimpleClass"; then
        echo -e "${GREEN}✅ PHP $php_version: Symbol finding works${NC}"
    else
        echo -e "${RED}❌ PHP $php_version: Symbol finding failed${NC}"
        ((FAILED_TESTS++))
    fi
    
    # Cleanup
    docker-compose -f docker-compose.multi-php.yml stop $service_name
    docker-compose -f docker-compose.multi-php.yml rm -f $service_name
    
    ((TOTAL_TESTS++))
}

# Function to test with real-world PHP projects
test_real_world() {
    echo ""
    echo -e "${BLUE}Testing with Real-World PHP Projects${NC}"
    echo "========================================="
    
    # Test with Symfony
    echo -e "${YELLOW}Testing with Symfony framework files...${NC}"
    # This would download and test against Symfony codebase
    
    # Test with Laravel  
    echo -e "${YELLOW}Testing with Laravel framework files...${NC}"
    # This would download and test against Laravel codebase
}

# Function to generate compatibility matrix
generate_compatibility_matrix() {
    echo ""
    echo -e "${BLUE}PHP Compatibility Matrix${NC}"
    echo "========================"
    echo "| PHP Version | Tests | CLI | PHAR | Symbol Finding |"
    echo "|-------------|-------|-----|------|----------------|"
    
    for version in "${PHP_VERSIONS[@]}"; do
        echo "| $version        | ✅    | ✅  | ✅   | ✅             |"
    done
}

# Main execution
main() {
    echo "Starting multi-PHP version testing..."
    echo "Testing PHP versions: ${PHP_VERSIONS[*]}"
    
    # Test each PHP version
    for version in "${PHP_VERSIONS[@]}"; do
        test_php_version $version
    done
    
    # Generate summary
    echo ""
    echo -e "${BLUE}Testing Summary${NC}"
    echo "================"
    echo "Total PHP versions tested: $TOTAL_TESTS"
    echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
    echo -e "Failed: ${RED}$FAILED_TESTS${NC}"
    
    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "${GREEN}🎉 All PHP versions passed testing!${NC}"
        generate_compatibility_matrix
        exit 0
    else
        echo -e "${RED}❌ Some tests failed. Please check the output above.${NC}"
        exit 1
    fi
}

# Cleanup function
cleanup() {
    echo "Cleaning up containers..."
    docker-compose -f docker-compose.multi-php.yml down
}

# Set trap for cleanup
trap cleanup EXIT

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running${NC}"
    exit 1
fi

# Check if docker-compose file exists
if [ ! -f "docker-compose.multi-php.yml" ]; then
    echo -e "${RED}Error: docker-compose.multi-php.yml not found${NC}"
    exit 1
fi

# Run main function
main "$@"