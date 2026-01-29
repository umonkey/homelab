FROM docker.io/library/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor nginx busybox-static python3-pip \
    && pip install certbot-dns-digitalocean --break-system-packages \
    && apt-get remove -y python3-pip \
    && rm -rf /var/lib/apt/lists/*

COPY rootfs /
COPY app /app

WORKDIR /app

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
