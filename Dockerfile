# Use official PHP image with Composer
FROM php:8.2-cli

# Install required PHP extensions
RUN docker-php-ext-install mysqli

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the project
COPY . .

# Expose the Render port (Render injects $PORT)
EXPOSE 10000

# Start PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT"]
