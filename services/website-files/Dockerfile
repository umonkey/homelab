FROM docker.io/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/*

COPY rootfs /

RUN mkdir -p /app/data && chown -R www-data:www-data /app/data

WORKDIR /app

EXPOSE 80/tcp

CMD ["nginx"]
