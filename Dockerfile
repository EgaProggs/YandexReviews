
FROM node:24-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

RUN npm run build



FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock artisan ./

RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --optimize

COPY --from=frontend /app/public/build ./public/build

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]