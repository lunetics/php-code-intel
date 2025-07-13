# Use PHP 8.3 CLI Alpine image for smaller size
FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    autoconf \
    g++ \
    make \
    linux-headers

# Install PHP extensions that might be needed for code analysis
RUN docker-php-ext-install \
    zip \
    opcache \
    pcntl

# Install development tools
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for development
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    echo "memory_limit=-1" >> "$PHP_INI_DIR/php.ini" && \
    echo "error_reporting=E_ALL" >> "$PHP_INI_DIR/php.ini" && \
    echo "display_errors=On" >> "$PHP_INI_DIR/php.ini" && \
    echo "display_startup_errors=On" >> "$PHP_INI_DIR/php.ini"

# Configure Xdebug for development
RUN echo "xdebug.mode=develop,debug,coverage" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.client_host=host.docker.internal" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" && \
    echo "xdebug.start_with_request=yes" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

# Set working directory
WORKDIR /app

# Create a non-root user for development
RUN adduser -D -u 1000 developer && \
    chown -R developer:developer /app

# Switch to non-root user
USER developer

# Copy composer files first for better caching
COPY --chown=developer:developer composer.json composer.lock* ./

# Install dependencies (if composer.json exists)
RUN if [ -f composer.json ]; then composer install --no-scripts --no-interaction; fi

# Copy project files
COPY --chown=developer:developer . .

# Default command (can be overridden)
CMD ["php", "-a"]