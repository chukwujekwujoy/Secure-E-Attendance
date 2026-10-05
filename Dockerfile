FROM php:8.2-apache

# Extensions you need: mysqli/pdo_mysql for filess.io, gd if you generate QR images server-side
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql gd \
    && a2enmod rewrite headers

# Point Apache at /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html

# Cloud Run injects $PORT — Apache must listen on it
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf
ENV PORT 8080
EXPOSE 8080

CMD ["apache2-foreground"]