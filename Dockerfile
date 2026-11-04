FROM php:8.3-fpm-alpine

# Системные зависимости и расширения для Laravel, PostgreSQL, MySQL, GD, Redis и Reverb (pcntl)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS linux-headers

# Копируем Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Кастомные лимиты PHP для загрузки фото лотов и аватарок
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

# Создаем непривилегированного пользователя под UID 1000 (совместимо с WSL Ubuntu)
RUN addgroup -g 1000 sportpay && adduser -u 1000 -G sportpay -s /bin/sh -D sportpay

USER sportpay

EXPOSE 9000
CMD ["php-fpm"]
