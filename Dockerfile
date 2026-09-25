# CaszaMosqui — imagen para Render (PHP 8.2 + Apache + PostgreSQL)
FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql pdo_mysql \
 && apt-get purge -y --auto-remove \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers

ENV TZ=America/Argentina/Buenos_Aires
RUN echo "date.timezone=America/Argentina/Buenos_Aires" > /usr/local/etc/php/conf.d/tz.ini \
 && echo "display_errors=Off" > /usr/local/etc/php/conf.d/errores.ini

COPY docker/apache-casza.conf /etc/apache2/conf-enabled/casza.conf
COPY . /var/www/html/
RUN cp /var/www/html/inc/config.render.php /var/www/html/inc/config.php \
 && mkdir -p /var/www/html/cache \
 && chown -R www-data:www-data /var/www/html/cache \
 && chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 10000
CMD ["/var/www/html/docker/entrypoint.sh"]
