FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libldap2-dev \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install ldap \
    && a2enmod rewrite headers \
    && { \
        echo 'ServerName localhost'; \
        echo '<Directory /var/www/html>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/quero-cafe.conf \
    && a2enconf quero-cafe \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

ENV CAFE_APP_ENV=production \
    CAFE_DB_PATH=/var/www/cafe-storage/cafe.db

COPY . /var/www/html/

RUN mkdir -p /var/www/cafe-storage \
    && touch /var/www/html/php_errors.log \
    && chown -R www-data:www-data /var/www/cafe-storage /var/www/html/php_errors.log

VOLUME ["/var/www/cafe-storage"]

EXPOSE 80
