#!/bin/bash
# PHP Code Intelligence Tool - Docker Runtime Container Setup
set -e

# Colors for output
BLUE='\033[36m'
GREEN='\033[32m'
YELLOW='\033[33m'
RED='\033[31m'
NC='\033[0m' # No Color

echo -e "${BLUE}PHP Code Intelligence Tool - Docker Runtime Container Setup${NC}"
echo "==============================================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker is available${NC}"

# Build runtime container
echo -e "${YELLOW}Building PHP Code Intelligence runtime container...${NC}"
docker build -f Dockerfile.runtime -t php-code-intel:runtime .

echo -e "${GREEN}✅ Runtime container built successfully!${NC}"

# Test the container
echo -e "${YELLOW}Testing runtime container...${NC}"
docker run --rm php-code-intel:runtime --version

echo -e "${GREEN}✅ Runtime container test passed!${NC}"

# Add shell function to user's shell profile
SHELL_PROFILE=""
if [ -f "$HOME/.bashrc" ]; then
    SHELL_PROFILE="$HOME/.bashrc"
elif [ -f "$HOME/.zshrc" ]; then
    SHELL_PROFILE="$HOME/.zshrc"
elif [ -f "$HOME/.bash_profile" ]; then
    SHELL_PROFILE="$HOME/.bash_profile"
fi

if [ -n "$SHELL_PROFILE" ]; then
    echo -e "${YELLOW}Adding shell function to $SHELL_PROFILE...${NC}"
    
    # Check if function already exists
    if ! grep -q "php-code-intel()" "$SHELL_PROFILE"; then
        cat >> "$SHELL_PROFILE" << 'EOF'

# PHP Code Intelligence Tool - Docker Runtime Container Function
php-code-intel() {
    local image="php-code-intel:runtime"
    
    # Check if image exists
    if ! docker image inspect "$image" >/dev/null 2>&1; then
        echo "❌ Runtime container not found. Please run: make build-runtime"
        return 1
    fi
    
    # Run the tool
    docker run --rm \
        -v $(pwd):/workspace \
        -e PHP_MEMORY_LIMIT=512M \
        "$image" "$@"
}

# Alias for convenience
alias pci='php-code-intel'
EOF
        
        echo -e "${GREEN}✅ Shell function added to $SHELL_PROFILE${NC}"
        echo -e "${YELLOW}💡 Restart your terminal or run: source $SHELL_PROFILE${NC}"
    else
        echo -e "${YELLOW}⚠️ Shell function already exists in $SHELL_PROFILE${NC}"
    fi
fi

echo ""
echo -e "${BLUE}🚀 Usage Examples:${NC}"
echo -e "${GREEN}Direct Docker usage:${NC}"
echo "  docker run --rm -v \$(pwd):/workspace php-code-intel:runtime find-usages \"App\\\\User\" --path=src/"
echo ""
echo -e "${GREEN}With shell function (after restarting terminal):${NC}"
echo "  php-code-intel find-usages \"App\\\\User\" --path=src/"
echo "  pci find-usages \"App\\\\User\" --format=json"
echo ""
echo -e "${GREEN}With Make commands:${NC}"
echo "  make analyze-runtime"
echo "  make index-runtime"
echo "  make runtime-help"
echo ""
echo -e "${GREEN}✅ Setup complete! The PHP Code Intelligence Tool is ready to use.${NC}"