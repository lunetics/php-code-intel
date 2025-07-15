# Docker Runtime Container Guide

## Overview

The PHP Code Intelligence Tool provides optimized Docker runtime containers for environments where PHP is not installed locally. This approach offers portable, consistent execution across different systems while maintaining full functionality.

## Quick Start

### 1. Build Runtime Container

```bash
# Build production runtime container
make build-runtime

# Verify build
docker run --rm php-code-intel:runtime --version
```

### 2. Basic Usage

```bash
# Run analysis on current project
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  find-usages "App\\User" --path=src/

# Index project files
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  index src/

# Different output formats
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  find-usages "App\\User" --path=src/ --format=json
```

### 3. Convenience Setup

```bash
# Run setup script for shell integration
./scripts/runtime-setup.sh

# After setup, use like local installation
php-code-intel find-usages "App\\User" --path=src/
pci find-usages "App\\User" --format=json  # Short alias
```

## Container Variants

### Production Runtime Container

**Image**: `php-code-intel:runtime`
**Dockerfile**: `Dockerfile.runtime`

- Optimized for production use
- Minimal dependencies
- Production PHP settings
- Health checks included

```bash
# Build production container
make build-runtime

# Usage
docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
  find-usages "App\\User" --path=src/ --format=json
```

### Development Runtime Container

**Image**: `php-code-intel:runtime-dev` 
**Dockerfile**: `Dockerfile.runtime.dev`

- Includes development dependencies
- Xdebug for debugging
- Additional development tools
- Verbose error reporting

```bash
# Build development container
make build-runtime-dev

# Usage with debugging
docker run --rm -v $(pwd):/workspace php-code-intel:runtime-dev \
  find-usages "App\\User" --path=src/ --verbose
```

## Advanced Usage

### Memory Management

```bash
# Limit container memory for large projects
docker run --rm \
  -v $(pwd):/workspace \
  --memory=512m \
  --memory-swap=1g \
  php-code-intel:runtime \
  find-usages "LargeClass" --path=src/
```

### Batch Processing

```bash
# Process multiple symbols efficiently
symbols=("App\\Models\\User" "App\\Services\\UserService" "App\\Controllers\\UserController")

for symbol in "${symbols[@]}"; do
    echo "Analyzing: $symbol"
    docker run --rm -v $(pwd):/workspace php-code-intel:runtime \
        find-usages "$symbol" --path=src/ --format=json \
        > "analysis-$(echo $symbol | tr '\\' '-').json"
done
```

### Multi-Project Analysis

```bash
#!/bin/bash
# analyze-multiple-projects.sh

projects=(
    "/path/to/project1"
    "/path/to/project2"
    "/path/to/project3"
)

# Build runtime container once
make build-runtime

for project in "${projects[@]}"; do
    echo "Analyzing $(basename $project)..."
    
    docker run --rm \
        -v "$project:/workspace" \
        -v "$(pwd)/reports:/reports" \
        php-code-intel:runtime \
        find-usages "CommonInterface" --path=src/ --format=json \
        > "reports/$(basename $project)-analysis.json"
done
```

## Docker Compose Integration

### Basic Docker Compose Setup

```yaml
# docker-compose.runtime.yml
services:
  php-code-intel:
    build:
      context: .
      dockerfile: Dockerfile.runtime
    volumes:
      - .:/workspace
    working_dir: /workspace
    environment:
      - PHP_MEMORY_LIMIT=512M
    profiles:
      - tools
```

### Usage with Docker Compose

```bash
# Build service
docker-compose -f docker-compose.runtime.yml build php-code-intel

# Run analysis
docker-compose -f docker-compose.runtime.yml run --rm php-code-intel \
  find-usages "App\\User" --path=src/

# Index files
docker-compose -f docker-compose.runtime.yml run --rm php-code-intel \
  index src/
```

### Team Development Setup

```yaml
# docker-compose.override.yml
services:
  analysis:
    extends:
      file: docker-compose.runtime.yml
      service: php-code-intel
    volumes:
      - .:/workspace
      - analysis-cache:/tmp/analysis
      - ./reports:/reports
    environment:
      - ANALYSIS_CACHE_DIR=/tmp/analysis
    command: tail -f /dev/null  # Keep container running

volumes:
  analysis-cache:
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Code Analysis

on: [push, pull_request]

jobs:
  analyze:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Build runtime container
        run: make build-runtime
      
      - name: Run symbol analysis
        run: |
          docker run --rm \
            -v ${{ github.workspace }}:/workspace \
            php-code-intel:runtime \
            find-usages "App\\Models\\User" --path=src/ --format=json \
            > analysis-results.json
      
      - name: Upload results
        uses: actions/upload-artifact@v3
        with:
          name: analysis-results
          path: analysis-results.json
```

### GitLab CI

```yaml
# .gitlab-ci.yml
stages:
  - build
  - analyze

build-runtime:
  stage: build
  script:
    - make build-runtime
  artifacts:
    reports:
      docker: php-code-intel:runtime

analyze-symbols:
  stage: analyze
  dependencies:
    - build-runtime
  script:
    - docker run --rm -v $CI_PROJECT_DIR:/workspace php-code-intel:runtime
        find-usages "App\\Models\\User" --path=src/ --format=json
        > analysis-results.json
  artifacts:
    reports:
      junit: analysis-results.json
```

### Jenkins Pipeline

```groovy
pipeline {
    agent any
    
    stages {
        stage('Build Runtime Container') {
            steps {
                sh 'make build-runtime'
            }
        }
        
        stage('Run Analysis') {
            steps {
                sh '''
                    docker run --rm \
                        -v $PWD:/workspace \
                        php-code-intel:runtime \
                        find-usages "App\\\\Models\\\\User" --path=src/ --format=json \
                        > analysis-results.json
                '''
            }
        }
        
        stage('Archive Results') {
            steps {
                archiveArtifacts artifacts: 'analysis-results.json'
            }
        }
    }
}
```

## Make Commands Reference

### Build Commands

```bash
make build-runtime       # Build production runtime container
make build-runtime-dev   # Build development runtime container
make clean-runtime       # Remove runtime container images
```

### Test Commands

```bash
make test-runtime        # Test runtime container functionality
make analyze-runtime     # Run sample analysis with runtime container
make index-runtime       # Index test files with runtime container
```

### Docker Compose Commands

```bash
make runtime-compose     # Run analysis using Docker Compose
make runtime-dev-compose # Run development analysis using Docker Compose
```

### Help Commands

```bash
make runtime-help        # Show Docker runtime container usage examples
```

## Shell Integration

### Automatic Setup

```bash
# Run setup script
./scripts/runtime-setup.sh

# This adds the following function to your shell profile:
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

# Also adds convenience alias
alias pci='php-code-intel'
```

### Manual Setup

Add to your `~/.bashrc` or `~/.zshrc`:

```bash
# PHP Code Intelligence Tool - Docker Runtime Container
php-code-intel() {
    docker run --rm \
        -v $(pwd):/workspace \
        -e PHP_MEMORY_LIMIT=512M \
        php-code-intel:runtime "$@"
}

alias pci='php-code-intel'
```

## Customization

### Extending the Runtime Container

```dockerfile
# custom-runtime.Dockerfile
FROM php-code-intel:runtime

# Add custom PHP extensions
RUN apk add --no-cache \
    mysql-client \
    postgresql-client \
    && docker-php-ext-install pdo_mysql pdo_pgsql

# Add custom tools
RUN apk add --no-cache jq curl

# Custom configuration
COPY custom-php.ini /usr/local/etc/php/conf.d/custom.ini

# Custom entrypoint
COPY custom-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/custom-entrypoint.sh
ENTRYPOINT ["custom-entrypoint.sh"]
```

### Custom Build Script

```bash
#!/bin/bash
# custom-build.sh

# Build custom runtime container
docker build -f custom-runtime.Dockerfile -t my-php-code-intel:runtime .

# Create custom alias
echo "alias my-pci='docker run --rm -v \$(pwd):/workspace my-php-code-intel:runtime'" >> ~/.bashrc
```

## Performance Optimization

### Multi-Stage Builds

```dockerfile
# Dockerfile.runtime.optimized
FROM php:8.4-cli-alpine AS builder

# Install build dependencies
RUN apk add --no-cache git unzip libzip-dev
RUN docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy source and build
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Runtime stage
FROM php:8.4-cli-alpine

# Install only runtime dependencies
RUN apk add --no-cache libzip && \
    docker-php-ext-install zip

# Copy built application
COPY --from=builder /app /app

# Optimize for production
RUN echo "opcache.enable_cli=1" > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /workspace
ENTRYPOINT ["php", "/app/bin/php-code-intel"]
```

### Container Registry

```bash
# Tag and push to registry
docker tag php-code-intel:runtime your-registry.com/php-code-intel:latest
docker push your-registry.com/php-code-intel:latest

# Use from registry
docker run --rm -v $(pwd):/workspace your-registry.com/php-code-intel:latest \
  find-usages "App\\User" --path=src/
```

## Troubleshooting

### Common Issues

**Container build fails**
```bash
# Check Docker daemon
docker info

# Clean up Docker cache
docker system prune -f

# Rebuild without cache
docker build --no-cache -f Dockerfile.runtime -t php-code-intel:runtime .
```

**Permission issues with mounted volumes**
```bash
# Check file permissions
ls -la /path/to/project

# Run with user context
docker run --rm -v $(pwd):/workspace --user $(id -u):$(id -g) php-code-intel:runtime \
  find-usages "App\\User" --path=src/
```

**Memory issues**
```bash
# Increase container memory
docker run --rm -v $(pwd):/workspace --memory=1g php-code-intel:runtime \
  find-usages "LargeClass" --path=src/

# Monitor memory usage
docker stats --no-stream
```

**Container startup slow**
```bash
# Use production container for better startup
make build-runtime

# Pre-pull base images
docker pull php:8.4-cli-alpine
docker pull composer:latest
```

### Debugging Container Issues

```bash
# Run container interactively
docker run --rm -it -v $(pwd):/workspace --entrypoint=/bin/sh php-code-intel:runtime

# Check container logs
docker logs container-name

# Inspect container
docker inspect php-code-intel:runtime

# Check health status
docker run --rm php-code-intel:runtime --version
```

## Best Practices

### 1. Container Lifecycle

- Build containers once, use many times
- Use specific tags for production deployments
- Clean up unused containers regularly

### 2. Volume Mounting

- Always use absolute paths for volume mounts
- Mount only necessary directories
- Use named volumes for persistent data

### 3. Resource Management

- Set appropriate memory limits
- Use multi-stage builds for smaller images
- Cache layers effectively

### 4. Security

- Run containers with least privilege
- Use non-root users when possible
- Keep base images updated

### 5. Team Collaboration

- Document container setup in README
- Use Docker Compose for consistent environments
- Version control Docker configurations

---

**The Docker runtime container approach provides a robust, portable solution for using the PHP Code Intelligence Tool across diverse environments without requiring local PHP installation.**