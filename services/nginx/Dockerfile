FROM docker.io/library/debian:stable-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["busybox", "crond", "-f", "-l", "5", "-L", "/dev/stderr"]
