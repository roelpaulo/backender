#!/bin/sh
set -e

# Helper to create a named Docker volume and migrate /app/storage from an existing Backender container
# Usage: sudo ./scripts/setup_persistence.sh [service_name] [volume_name]

SERVICE=${1:-static-backender-8emeaq}
VOLUME=${2:-backender_storage}
BACKUP_DIR=/tmp/backender_storage_backup

echo "Service: $SERVICE"
echo "Volume: $VOLUME"

# Find a running container for the service
CID=$(docker ps --filter "name=$SERVICE" --format '{{.ID}}' | head -n1)
if [ -z "$CID" ]; then
  echo "No running container found for $SERVICE. Aborting."
  exit 1
fi

echo "Backing up /app/storage from container $CID to $BACKUP_DIR"
rm -rf "$BACKUP_DIR" && mkdir -p "$BACKUP_DIR"
docker cp "$CID":/app/storage "$BACKUP_DIR"

echo "Creating Docker volume: $VOLUME"
docker volume create "$VOLUME" || true

echo "Copying backup into volume"
docker run --rm -v "$VOLUME":/data -v "$BACKUP_DIR":/backup:ro busybox sh -c 'cp -a /backup/. /data/ && sync'

echo "Updating service $SERVICE to mount volume $VOLUME at /app/storage"
# This will trigger a rolling update
docker service update --mount-add type=volume,src=$VOLUME,dst=/app/storage --with-registry-auth "$SERVICE" || true

echo "Waiting for new task to appear..."
sleep 4
docker service ps "$SERVICE" --no-trunc

NEWCID=$(docker ps --filter "name=$SERVICE" --format '{{.ID}}' | head -n1)
if [ -n "$NEWCID" ]; then
  echo "Fixing ownership inside container $NEWCID"
  docker exec -it "$NEWCID" sh -c 'chown -R 1000:1000 /app/storage || true'
  echo "Check DB file:" 
  docker exec -it "$NEWCID" sh -c 'ls -l /app/storage/database || true'
else
  echo "No new container found after update. Check service status with 'docker service ps $SERVICE'"
fi

echo "Done. Verify persistence by restarting the service or node and checking /app/storage contents remain."