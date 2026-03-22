FROM drupal:10.3-php8.3-apache

# Install bcmath (required by Commerce) and other useful extensions
RUN docker-php-ext-install bcmath

# Install git, unzip for Composer
RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*

# Update Composer
RUN composer self-update

# Install required packages: drush CLI, correct commerceguys/intl v2 (fixes
# NumberFormatRepository signature mismatch), and commerceguys/addressing v2
RUN cd /opt/drupal && COMPOSER_ALLOW_SUPERUSER=1 composer require \
    drush/drush:^12 \
    commerceguys/intl:^2.0.6 \
    commerceguys/addressing:^2.1.1 \
    --no-interaction --prefer-dist --no-progress

# Ensure apache2-foreground is available and fix permissions in entrypoint
RUN chmod +x /usr/local/bin/apache2-foreground || true
