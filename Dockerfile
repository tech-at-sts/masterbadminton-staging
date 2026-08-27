FROM php:8.2-apache

RUN a2enmod rewrite

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p storage/cache \
    && chown -R www-data:www-data storage

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
