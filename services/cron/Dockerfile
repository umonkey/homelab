FROM docker.io/library/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates sqlite3 xz-utils rclone busybox-static \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd -r app \
    && useradd -r -g app -m -d /app app

COPY rootfs /

COPY crontab /var/spool/cron/crontabs/app
RUN chmod 0600 /var/spool/cron/crontabs/app \
    && chown app:app /app

WORKDIR /app

CMD ["busybox", "crond", "-f", "-l", "5", "-L", "/dev/stderr"]
