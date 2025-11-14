# ============================================================
# Laravel MongoDB Vector Search - PHP Development Container
# ============================================================
# This Dockerfile builds a PHP 8.2-FPM container with:
# - MongoDB PHP extension for database connectivity
# - Xdebug for debugging support
# - Composer for dependency management
# - Common development tools (git, wget, unzip, ping)
# ============================================================

# Get PHP version from docker-compose.yml build args
# Default to php:8.2-fpm if not specified
ARG RUNTIME_PHP_IMAGE

# Use official PHP-FPM image as base
FROM ${RUNTIME_PHP_IMAGE}

# ============================================================
# Setup Log Files
# ============================================================
# Create log files and set proper permissions for www-data user
# (the user that php-fpm runs as)

# Xdebug log file for debugging connection issues
RUN touch /tmp/xdebug.log && chown www-data:www-data /tmp/xdebug.log

# PHP error log file for application errors
RUN touch /var/log/php-errors.log && chown www-data:www-data /var/log/php-errors.log

# ============================================================
# Install System Dependencies
# ============================================================
# Install packages required for PHP extensions and development
# - libssl-dev: Required for MongoDB extension
# - wget: Download files
# - git: Version control
# - unzip: Extract archives
# - iputils-ping: Network debugging (ping command)
RUN apt-get update \
    && apt-get install -y \
        libssl-dev \
        wget \
        git \
        unzip \
        iputils-ping

# Zip extension dependencies and installation
# Clean up apt lists after to reduce image size
RUN apt-get update \
    && apt-get install -y libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# ============================================================
# Install PHP Extensions via PECL
# ============================================================

# MongoDB PHP Extension
# Conditional install: only installs if not already present
# This allows for faster rebuilds when the extension exists
RUN bash -c '[[ -n "$(pecl list | grep mongodb)" ]] \
 || (pecl install mongodb && docker-php-ext-enable mongodb)'

# Xdebug Extension
# Enables step-through debugging in VS Code
RUN bash -c '[[ -n "$(pecl list | grep xdebug)" ]] \
 || (pecl install xdebug && docker-php-ext-enable xdebug)'

# ============================================================
# Copy PHP Configuration Files
# ============================================================
# Custom PHP and Xdebug settings
# Files are copied from .devcontainer/.docker/php/ directory
# Note: Paths are relative to the build context (set in docker-compose.yml)

COPY docker-php.ini /usr/local/etc/php/conf.d/
COPY xdebug.ini /usr/local/etc/php/conf.d/

# ============================================================
# Install Composer
# ============================================================
# Composer is PHP's dependency manager, required for Laravel

# Download composer installer and run it
RUN curl -sS https://getcomposer.org/installer | php

# Move composer to global bin directory so it's available as 'composer' command
RUN mv composer.phar /usr/local/bin/composer

# ============================================================
# Set Working Directory
# ============================================================
# Set default directory for commands executed in the container
# This matches where docker-compose.yml mounts the Laravel project
# /workspace is also where VS Code's devcontainer opens
WORKDIR /workspace

# Container is now ready for Laravel development!
# PHP extensions: mongodb, xdebug, zip
# Tools: composer, git, wget, unzip, ping