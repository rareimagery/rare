# Step 3: Docker & Local Dev

**Agent:** Drupal (`drupal.md`)

## Docker Services

`docker-compose.yml` defines 2 services:

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `rare-postgres` | postgres:16-alpine | 5432 | Database |
| `rare-drupal` | Custom (Dockerfile) | 80 | Drupal + Apache |

Network: `marketplace-net` (bridge)

## Start Local Dev

```bash
# Start containers
make up

# Verify running
docker ps
# Should show: rare-drupal, rare-postgres

# Fresh Drupal install (first time only)
make install
```

## Docker Config Files

```
docker/
├── nginx/default.conf          # Nginx reverse proxy (production)
├── php/drupal.ini              # PHP config (256M memory, OPcache)
└── host-nginx/rareimagery.conf # Host-level nginx (VPS)
```

## Makefile Commands

### Docker
```bash
make up              # docker compose up -d
make down            # Stop containers
make build           # Rebuild images
make logs            # Tail Drupal logs
```

### Drupal
```bash
make install         # Fresh site install + enable modules
make cr              # Cache rebuild
make drush CMD="..." # Run any Drush command
make export          # Export config to config/sync
make import          # Import config from config/sync
make reindex         # Clear and rebuild search index
```

### Frontend
```bash
make fe-install          # npm install
make fe-dev-storefront   # Start Next.js dev
make fe-build            # Build frontend
```

### Quality
```bash
make test            # PHPUnit tests
make lint            # PHP CodeSniffer
```

## Important Note

The local Docker database has **ZERO data**. All real store data, creator profiles, and products live on the production server (72.62.80.155). Local dev is for code changes only — use the remote Drupal for API testing.

```bash
# Remote Drush (production)
ssh root@72.62.80.155
docker exec rare-drupal /opt/drupal/vendor/bin/drush status
```

## Frontend Dev Server

```bash
cd frontend
npm install          # First time
npm run dev          # Starts on http://localhost:3000
```

The frontend dev server connects to the remote Drupal API at `DRUPAL_BASE_URL` from `.env.local`.

## Next Step

→ [Step 4: Drupal Backend](04_DRUPAL.md)
