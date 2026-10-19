FROM php:8.3-fpm

# Устанавливаем официальный скрипт быстрой установки расширений PHP
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Устанавливаем только нужные модули (быстро, без сборки из исходников)
RUN install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip

# Ставим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ставим легковесный Node.js и npm для фронтенда
RUN apt-get update && apt-get install -y --no-install-recommends nodejs npm git && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
