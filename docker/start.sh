#!/bin/sh
# Startup script for Railway deployment

# Railway injects $PORT dynamically — patch nginx before starting
PORT=${PORT:-80}
sed -i "s/listen 80;/listen ${PORT};/" /etc/nginx/sites-available/default

# Cache Laravel config with production env vars available
php /var/www/html/artisan config:cache

# Run migrations (non-blocking — supervisord starts even if this fails)
php /var/www/html/artisan migrate --force || echo "Migration failed or skipped"

# Seed lookup tables (idempotent — uses updateOrInsert)
php /var/www/html/artisan db:seed --force || echo "Seed failed or skipped"

# Start nginx + php-fpm + queue worker
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/app.conf
