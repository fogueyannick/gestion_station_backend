# ---------- Builder ----------
FROM php:8.4-fpm AS builder

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
RUN composer install --no-dev --optimize-autoloader


# ---------- Runtime ----------
FROM php:8.4-fpm

# Installer dépendances + Nginx
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copier l'application
COPY --from=builder /var/www /var/www

# Config PHP + OPcache
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Config Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Permissions Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD sh -c "php artisan migrate --force && php-fpm -D && nginx -g 'daemon off;'"
