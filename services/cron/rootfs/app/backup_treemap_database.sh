#!/bin/sh
set -e

if [ -f /run/secrets/turso_api_token ]; then
    export TURSO_API_TOKEN=`cat /run/secrets/turso_api_token`
fi

if [ -f /run/secrets/dropbox_token ]; then
    export RCLONE_CONFIG_DROPBOX_TOKEN=`cat /run/secrets/dropbox_token`
fi

SOURCE=database.sqlite
TARGET_FOLDER=backups
TARGET_FILE=$TARGET_FOLDER/treemap-`date +'%Y%m%d'.sqlite`
REMOTE="dropbox:Yerevan Tree Map Database/"

if [ -z "$TURSO_API_TOKEN" ]; then
    echo "TURSO_API_TOKEN not set."
    exit 1
fi

echo "Downloading the database..."
curl -L -s -o backup.sql -H "Authorization: Bearer $TURSO_API_TOKEN" "https://treemap-umonkey.aws-eu-west-1.turso.io/dump" \
    && sqlite3 $SOURCE < backup.sql \
    && rm -f backup.sql

echo "Creating folder $TARGET_FOLDER ..."
mkdir -p "$TARGET_FOLDER"

echo "Copying $SOURCE to $TARGET_FILE ..."
rm -f $TARGET_FILE
echo ".clone $TARGET_FILE" | sqlite3 $SOURCE >/dev/null

echo "Cleaning up the database in $TARGET_FILE ..."
echo "DROP TABLE IF EXISTS upload_tickets;" | sqlite3 $TARGET_FILE
echo "DROP TABLE IF EXISTS queue_messages;" | sqlite3 $TARGET_FILE
echo "DROP TABLE IF EXISTS users;" | sqlite3 $TARGET_FILE
echo "VACUUM;" | sqlite3 $TARGET_FILE

echo "Compressing $TARGET_FILE ..."
xz -9 $TARGET_FILE
ls -lh $TARGET_FOLDER

echo "Copying $TARGET_FILE to Dropbox ..."
rclone copy $TARGET_FOLDER "$REMOTE"
