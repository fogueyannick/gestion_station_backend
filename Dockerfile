# ---------- Builder ----------
FROM php:8.4-fpm AS builder

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd opcache

# Installer composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# 🔹 Crée le dossier database et le fichier SQLite pour éviter l'erreur package:discover
RUN mkdir -p /var/www/database \
    && touch /var/www/database/database.sqlite

# Installer les packages PHP sans dev et optimiser l'autoloader
RUN composer install --no-dev --optimize-autoloader

# ---------- Runtime ----------
FROM php:8.4-fpm

# Installer Nginx et les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copier l'application depuis le builder
COPY --from=builder /var/www /var/www

# Config PHP + OPcache
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Config Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Permissions Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose le port FPM (9000) et Nginx (optionnel si reverse proxy)
EXPOSE 9000

# Commande de démarrage
CMD sh -c "\
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php-fpm -D && \
    nginx -g 'daemon off;' \
"
