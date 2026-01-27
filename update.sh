#!/bin/sh
# This script pulls the updates from the repo and deploys them.
# This script is normally executed by a GitHub workflow, over SSH.

cd `dirname $0`

if [ ! -f compose.yaml ]; then
    echo "Run this script inside the repo."
    exit 1
fi

# Pull the updates.
git fetch origin master
git reset --hard origin/master

# Pull the pre-built images.
docker-compose pull

# Rebuild the remaining images and restart the services.
docker-compose up -d --build --remove-orphans

# Clean up unused images.
docker image prune -f
