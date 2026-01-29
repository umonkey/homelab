server {
    listen 443 ssl;
    http2 on;
    server_name seb-museum.ru;

    ssl_certificate /etc/letsencrypt/live/seb-museum.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seb-museum.ru/privkey.pem;

    location / {
        proxy_pass http://homelab_museum:80;
    }

    gzip on;
    gzip_types text/plain text/css text/javascript application/javascript;
}

server {
    listen 443 ssl;
    http2 on;
    server_name www.seb-museum.ru;

    ssl_certificate /etc/letsencrypt/live/seb-museum.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seb-museum.ru/privkey.pem;

    location / {
        return 301 https://seb-museum.ru$request_uri;
    }
}

server {
    listen 80;
    server_name .seb-museum.ru;

    location / {
        return 301 https://seb-museum.ru$request_uri;
    }
}
