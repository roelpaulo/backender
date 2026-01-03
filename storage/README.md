# Backender Storage Directory

This directory contains all persistent data for Backender and is automatically mounted into the container at `/app/storage`.

## Structure

```
storage/
├── database/       # SQLite databases (backender.sqlite, etc.)
├── endpoints/      # Custom endpoint PHP files
└── logs/           # Application and error logs
```

## Persistence

### Local Development
The `./storage` folder is bind-mounted to `/app/storage` in the container via docker-compose.yml.
All data persists on your local filesystem.

### Production (Dokploy)
Dokploy automatically mounts this directory when deploying from the repository.
The docker-compose.yml configuration ensures the storage folder persists across:
- Container restarts
- Deployments
- Application updates

## Important Notes

- **Never delete** the `.gitkeep` files in subdirectories
- Database files (`*.sqlite`) are git-ignored but persist on disk
- Log files are git-ignored but persist on disk
- Custom endpoint files should be backed up separately
- The container user (uid 1000) owns these files

## Backup Recommendation

```bash
# Backup storage directory
tar -czf backender-storage-backup-$(date +%Y%m%d).tar.gz storage/

# Restore from backup
tar -xzf backender-storage-backup-YYYYMMDD.tar.gz
```
