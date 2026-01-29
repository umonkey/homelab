FROM docker.io/library/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor nginx busybox-static \
    && rm -rf /var/lib/apt/lists/*

COPY rootfs /

WORKDIR /app

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
