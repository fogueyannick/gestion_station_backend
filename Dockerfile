# ---------- Builder ----------
FROM php:8.4-fpm AS builder

# Installer dépendances système et PHP
RUN apt-get update && apt-get install -y \
    git unzip libsqlite3-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite zip gd opcache

# Installer composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copier tout le code
COPY . .

# Installer les packages PHP sans dev et optimiser l'autoloader
RUN composer install --no-dev --optimize-autoloader

# ---------- Runtime ----------
FROM php:8.4-fpm

# Installer Nginx et dépendances PHP
RUN apt-get update && apt-get install -y \
    nginx libsqlite3-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copier l'application depuis le builder
COPY --from=builder /var/www /var/www

# Créer le dossier pour la DB SQLite
RUN mkdir -p /var/www/database && chown -R www-data:www-data /var/www/database

# Config PHP + OPcache
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Config Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Permissions Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database

# Exposer le port FPM
EXPOSE 9000

# CMD : migrer, exécuter le seeder initial et lancer PHP-FPM + Nginx
CMD sh -c "php artisan migrate --force && php artisan db:seed --class=InitialDataSeeder --force && php-fpm -D && nginx -g 'daemon off;'"
