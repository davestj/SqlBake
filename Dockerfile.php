# Use the official PHP 8.1 image
FROM php:8.1

# Install required extensions and tools
RUN apt-get update && apt-get install -y \
    git \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql gd zip

# Set the working directory
WORKDIR /var/www/apps

# Copy the application files into the container
COPY . /var/www/apps

# Expose port for PHP application
EXPOSE 80

# Define the entry point to bash
ENTRYPOINT ["/bin/bash"]