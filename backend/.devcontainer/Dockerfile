FROM php:8.5-apache

# system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# install PHP extensions
RUN docker-php-ext-install pdo_pgsql zip bcmath

# install PCOV from PECL
RUN pecl install pcov && docker-php-ext-enable pcov

# Apache modules - rewrite nice URLs and headers for security
RUN a2enmod rewrite headers

# copy default php development configuration php.ini-development to php.ini
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# add my own configuration to php.ini`
COPY custom.ini $PHP_INI_DIR/conf.d/custom.ini

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# change working dir
WORKDIR /app

# local development: var/cache is an anonymous volume seeded from the image,
# so the compiled DI container and the log file must be writable for www-data
RUN mkdir -p /app/var/cache /app/var/log && chmod -R 777 /app/var

# install composer dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader