FROM docker.io/library/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends supervisor openvpn \
    && rm -rf /var/lib/apt/lists/*

COPY rootfs /

WORKDIR /app

EXPOSE 6912/udp
EXPOSE 6913/tcp

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
