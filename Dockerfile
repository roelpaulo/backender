FROM alpine:3.21

# Install nginx, PHP 8.4, and required extensions
RUN apk add --no-cache \
    nginx \
    php84 \
    php84-fpm \
    php84-pdo \
    php84-pdo_sqlite \
    php84-sqlite3 \
    php84-session \
    php84-json \
    php84-mbstring \
    php84-openssl \
    && ln -s /usr/bin/php84 /usr/bin/php

# Create non-root user
RUN adduser -D -u 1000 backender

# Create application directories
RUN mkdir -p /app/public /app/app /app/storage/endpoints /app/storage/logs /app/storage/database \
    && chown -R backender:backender /app

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy PHP-FPM configuration
COPY docker/php-fpm.conf /etc/php84/php-fpm.d/www.conf

# Copy application files
COPY --chown=backender:backender app /app/app
COPY --chown=backender:backender public /app/public

# Set working directory
WORKDIR /app

# Expose port
EXPOSE 80

# Create startup script
RUN echo '#!/bin/sh' > /start.sh && \
    echo '# Ensure storage directories exist' >> /start.sh && \
    echo 'mkdir -p /app/storage/logs /app/storage/database /app/storage/endpoints' >> /start.sh && \
    echo 'chown -R backender:backender /app/storage' >> /start.sh && \
    echo 'php-fpm84 -D' >> /start.sh && \
    echo 'nginx -g "daemon off;"' >> /start.sh && \
    chmod +x /start.sh

# Run as non-root user for php-fpm, but nginx needs root for port 80
CMD ["/start.sh"]
