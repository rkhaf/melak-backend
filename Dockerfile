# Stage 1: Build Frontend Assets (Vite)
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Application Runtime (PHP-FPM + Nginx)
FROM php:8.3-fpm-alpine

# Install system dependencies & PHP extensions yang dibutuhkan Laravel
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    bash \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source code ke container
COPY . .

# Copy compiled assets dari Stage 1
COPY --from=assets-builder /app/public/build ./public/build

# Install dependencies PHP (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# PHP configuration untuk upload & performance
RUN echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/uploads.ini

# Atur permission untuk storage dan bootstrap/cache serta nginx temp dir
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/lib/nginx

# Copy konfigurasi Nginx internal
COPY ./docker/nginx.conf /etc/nginx/nginx.conf

# Expose port untuk di-bind ke host (NPM)
EXPOSE 80

# Jalankan PHP-FPM dan Nginx bersamaan
CMD php-fpm -D && php artisan storage:link --force && nginx -g 'daemon off;'
