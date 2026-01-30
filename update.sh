#!/bin/sh
# This script pulls the updates from the repo and deploys them.
# This script is normally executed by a GitHub workflow, over SSH.

cd `dirname $0`

if [ ! -f compose.yaml ]; then
    echo "Run this script inside the repo."
    exit 1
fi

# Make sure we can pull the images.
if [ -n "$GHCR_TOKEN" ]; then
    echo "Logging in to ghcr.io ..."
    echo $GHCT_TOKEN | docker login ghcr.io -u umonkey --password-stdin
fi

# Pull the updates.
git fetch origin master
git reset --hard origin/master

# Pull the pre-built images.
docker compose pull -q

# Rebuild the remaining images and restart the services.
docker compose up -d --remove-orphans

# Clean up unused images.
docker image prune -f
