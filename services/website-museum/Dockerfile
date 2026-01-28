FROM docker.io/library/php:7.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor nginx \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -r app \
    && useradd -r -g app -m -d /app app

RUN docker-php-ext-install pdo_mysql

COPY app /app
COPY rootfs /

RUN mkdir -p /app/data && chown -R www-data:www-data /app/data

WORKDIR /app

EXPOSE 80/tcp

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
