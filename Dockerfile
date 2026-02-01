# Stage 1: Builder
# We use this stage to prepare files. 
# In a real-world app, you might run 'composer install' or build frontend assets here.
FROM php:8.2-cli as builder

WORKDIR /app
COPY . .

# Stage 2: Production
FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files from the builder stage
COPY --from=builder /app /var/www/html

# Set permissions for Apache user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Expose port 80
EXPOSE 80
