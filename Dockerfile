FROM alpine:3.21

# Install nginx, PHP 8.4, and required extensions
RUN apk add --no-cache \
    nginx \
    php84 \
    php84-fpm \
    php84-pdo \
    php84-pdo_sqlite \
    php84-sqlite3 \
    php84-pdo_mysql \
    php84-mysqli \
    php84-pdo_pgsql \
    php84-pgsql \
    php84-session \
    php84-mbstring \
    php84-openssl \
    php84-curl \
    php84-phar \
    php84-bcmath \
    php84-xml \
    php84-dom \
    php84-zip \
    php84-iconv \
    php84-tokenizer \
    php84-fileinfo \
    composer \
    git \
    curl \
    tar

# Create non-root user
RUN adduser -D -u 1000 backender

# Create application directories
RUN mkdir -p /app/public /app/app /app/storage/endpoints /app/storage/logs /app/storage/database \
    && chown -R backender:backender /app

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy PHP-FPM configuration
COPY docker/php-fpm.conf /etc/php84/php-fpm.d/www.conf

# Set working directory
WORKDIR /app

# Copy composer files
COPY --chown=backender:backender composer.json /app/

# Manually install PHPMailer to bypass Composer's connectivity issues in certain environments
# This ensures it can be built anywhere even if Packagist is unreachable
RUN mkdir -p /app/vendor/phpmailer/phpmailer && \
    curl -L https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.tar.gz | tar xz -C /app/vendor/phpmailer/phpmailer --strip-components=1 && \
    mkdir -p /app/vendor/composer && \
    echo '<?php require_once __DIR__ . "/phpmailer/phpmailer/src/PHPMailer.php"; require_once __DIR__ . "/phpmailer/phpmailer/src/SMTP.php"; require_once __DIR__ . "/phpmailer/phpmailer/src/Exception.php";' > /app/vendor/autoload.php && \
    chown -R backender:backender /app/vendor

# Copy application files
COPY --chown=backender:backender app /app/app
COPY --chown=backender:backender public /app/public

# Copy storage structure with .gitkeep files to ensure directories exist in image
COPY --chown=backender:backender storage /app/storage-init

# Declare storage as a volume so runtimes can mount persistent storage to /app/storage
VOLUME ["/app/storage"]

# Expose port
EXPOSE 80

# Create startup script with storage initialization
COPY --chmod=755 <<'EOF' /start.sh
#!/bin/sh
set -e

# Initialize storage from template if empty (first run or fresh volume)
if [ ! -f /app/storage/database/.gitkeep ]; then
    echo "Initializing storage structure..."
    mkdir -p /app/storage/logs /app/storage/database /app/storage/endpoints
    cp -n /app/storage-init/database/.gitkeep /app/storage/database/ 2>/dev/null || true
    cp -n /app/storage-init/endpoints/.gitkeep /app/storage/endpoints/ 2>/dev/null || true
    cp -n /app/storage-init/logs/.gitkeep /app/storage/logs/ 2>/dev/null || true
    cp -n /app/storage-init/README.md /app/storage/ 2>/dev/null || true
fi

# Ensure proper ownership
chown -R backender:backender /app/storage
touch /app/storage/logs/php-fpm.log /app/storage/logs/php-error.log /var/log/nginx/error.log /var/log/nginx/access.log

# Start services
php-fpm84 -D
tail -F /app/storage/logs/php-fpm.log /app/storage/logs/php-error.log /var/log/nginx/error.log /var/log/nginx/access.log &
exec nginx -g "daemon off;"
EOF

# Run as non-root user for php-fpm, but nginx needs root for port 80
CMD ["/start.sh"]
