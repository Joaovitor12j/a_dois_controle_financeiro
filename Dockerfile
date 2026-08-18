FROM serversideup/php:8.3-fpm

WORKDIR /app

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . /app

EXPOSE 9000

CMD ["php-fpm"]
