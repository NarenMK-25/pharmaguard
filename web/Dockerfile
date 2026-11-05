# Use the official PHP image with Apache
FROM php:8.2-apache

# Install the PDO MySQL extension required by your login system
RUN docker-php-ext-install pdo pdo_mysql

# Copy your frontend PHP/HTML/JS files into the Apache web root
COPY . /var/www/html/

EXPOSE 80