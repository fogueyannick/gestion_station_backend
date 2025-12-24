# Étape 1 : Builder PHP + Composer + extensions
FROM php:8.4-fpm AS builder

# Installer dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copier le projet
COPY . .

# Installer les dépendances PHP optimisées pour la production
RUN composer install --no-dev --optimize-autoloader

# Étape 2 : Image finale légère
FROM php:8.4-fpm

WORKDIR /var/www

# Copier le code et les dépendances depuis le builder
COPY --from=builder /var/www /var/www
COPY --from=builder /usr/local/etc/php /usr/local/etc/php

# Exposer le port standard HTTP
EXPOSE 8080

# Commande de démarrage : migrations + PHP-FPM
CMD ["sh", "-c", "php artisan migrate --force && php-fpm"]
