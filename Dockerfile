# Use an official PHP image as the base
FROM php:7.4-fpm

# Install PHP extensions
RUN apt-get update && apt-get install -qy zip git curl libmcrypt-dev libzip-dev libjpeg-dev libpng-dev

RUN docker-php-ext-install pdo pdo_mysql mysqli bcmath exif zip gd

RUN docker-php-ext-enable pdo pdo_mysql mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /etc/nginx/html

# To run Composer as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Copy your application files
COPY . .

# Run composer install
RUN composer install

# COPY localhost.crt /etc/ssl/certs/localhost.crt
# COPY localhost.key /etc/ssl/private/localhost.key

# RUN chmod 644 /etc/ssl/certs/localhost.crt
# RUN chmod 600 /etc/ssl/private/localhost.key

# RUN mv security.ini /var/www/
# COPY nginx.conf /etc/nginx/conf.d/

# EXPOSE 443

