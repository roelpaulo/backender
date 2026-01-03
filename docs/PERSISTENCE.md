# Persistence (Backender)

Backender stores its SQLite database and related runtime files under `/app/storage`. To ensure data survives container restarts, mount `/app/storage` as a Docker volume or a host bind.

## Quick guide (Docker Compose)
If you're running locally with Docker Compose, the `docker-compose.yml` already mounts `./storage:/app/storage`:

```yaml
services:
  backender:
    volumes:
      - ./storage:/app/storage
```

Make sure `./storage` exists and is persisted on your host.

## Quick guide (Docker Swarm / Dokploy)
On a Swarm or Dokploy-managed host, use a named Docker volume and mount it to the service.

Steps (manager node):

1. Backup (optional) the current storage from the running container to host:

```bash
CID=$(docker ps --filter "name=static-backender-8emeaq" --format '{{.ID}}' | head -n1)
mkdir -p /tmp/backender_storage_backup
docker cp "$CID":/app/storage /tmp/backender_storage_backup
```

2. Create a named volume and populate it with the backup:

```bash
docker volume create backender_storage
docker run --rm -v backender_storage:/data -v /tmp/backender_storage_backup:/backup:ro busybox sh -c 'cp -a /backup/. /data/ && sync'
```

3. Update the service to mount the volume at `/app/storage`:

```bash
docker service update --mount-add type=volume,src=backender_storage,dst=/app/storage --with-registry-auth static-backender-8emeaq
```

4. Check the new task and verify the DB exists:

```bash
docker service ps static-backender-8emeaq --no-trunc
NEWCID=$(docker ps --filter "name=static-backender-8emeaq" --format '{{.ID}}' | head -n1)
docker exec -it $NEWCID sh -c 'ls -l /app/storage/database/backender.sqlite'
```

### Automated helper
A helper script `scripts/setup_persistence.sh` exists in the repository to automate the backup -> volume -> update process. Run it on the manager node:

```bash
sudo ./scripts/setup_persistence.sh static-backender-8emeaq backender_storage
```

## Notes & tips
- Ensure the mounted volume directory is owned by the `backender` user (uid 1000). After mounting, fix ownership inside the container if needed:

```bash
docker exec -it $NEWCID sh -c 'chown -R 1000:1000 /app/storage || true'
```

- Dokploy UI may provide an easier way to configure persistent volumes for your project — prefer that when available.
- If you've already lost DB data, check backups under `/tmp/backender_storage_backup` if you ran the helper earlier; otherwise the data may be unrecoverable.

If you want, I can run the helper script on the manager node (SSH access required) or create a Dokploy stack file that includes the volume mount for you.