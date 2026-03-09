FROM drupal:10.3-php8.3-apache

# Install bcmath (required by Commerce) and other useful extensions
RUN docker-php-ext-install bcmath

# Install git, unzip for Composer
RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*

# Update Composer
RUN composer self-update
