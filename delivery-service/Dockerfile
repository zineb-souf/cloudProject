FROM php:8.4-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip

RUN docker-php-ext-install pdo pdo_mysql

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
