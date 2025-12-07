# Utiliser PHP 8.3 FPM comme base
FROM php:8.3-fpm

ARG XDEBUG_VERSION=3.4.5

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    postgresql-client \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd zip \
    && pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Définir le dossier de travail
WORKDIR /srv

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

# Définir le script comme point d'entrée
CMD ["php-fpm"]
