#!/bin/sh
# Get a token: https://cloud.digitalocean.com/settings/api/tokens
certbot -n certonly \
--dns-digitalocean \
--dns-digitalocean-credentials digitalocean.ini \
--email hex@umonkey.net \
--agree-tos \
-d '*.seb-museum.ru' -d seb-museum.ru
