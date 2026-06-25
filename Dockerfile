FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Sao_Paulo

# System deps + PHP 8.5 via ondrej/php PPA
RUN apt-get update && apt-get install -y software-properties-common curl gnupg && \
    add-apt-repository ppa:ondrej/php && \
    apt-get update && apt-get install -y \
    php8.5-fpm php8.5-cli php8.5-pgsql php8.5-mbstring php8.5-xml \
    php8.5-bcmath php8.5-curl php8.5-zip php8.5-redis php8.5-intl \
    php8.5-tokenizer php8.5-ctype php8.5-fileinfo \
    nginx supervisor unzip git && \
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Dependências Node + build
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci && npm run build

# Resto do app
COPY . .

# Otimizações Laravel
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Permissões
RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Configs nginx e supervisor
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf

RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/app.conf"]
