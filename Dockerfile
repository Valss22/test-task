FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/* \
    && echo 'date.timezone=Europe/Moscow' > /usr/local/etc/php/conf.d/timezone.ini \
    && echo 'default_charset=UTF-8' > /usr/local/etc/php/conf.d/charset.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
