FROM mcr.microsoft.com/devcontainers/php:8.4-bookworm

USER root

# System Packages and PHP Extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        mariadb-client \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pdo_mysql \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Xdebug configuration
RUN echo "xdebug.start_with_request=trigger" \
    > /usr/local/etc/php/conf.d/zz-xdebug.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

USER vscode

# Laravel
RUN composer global require laravel/installer \
    && echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> /home/vscode/.bashrc
