FROM php:8.3-apache
RUN apt-get update && \
    apt-get install -y unzip libzip-dev && \
    docker-php-ext-install zip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
RUN groupadd -g 1000 render && usermod -a -G 1000 www-data
WORKDIR /var/www/html
COPY . /var/www/html/
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
