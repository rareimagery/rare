# Step 12: Deployment & Operations

## Deployment Targets

| System | Platform | URL | Deploy Method |
|--------|----------|-----|---------------|
| Frontend | Vercel | rareimagery.net | Auto on `git push` to main |
| Backend | Hostinger VPS | 72.62.80.155 | SSH + git pull + cache clear |
| Database | VPS Docker | port 5432 | Persistent volume |

## Frontend Deployment (Vercel)

### Setup
1. Vercel project connected to `rareimagery/rare.git`
2. **Root Directory:** `frontend`
3. **Framework:** Next.js (auto-detected)
4. **Node version:** 22.x

### Deploy
```bash
git push origin main   # Auto-deploys to Vercel
```

### Environment Variables (Vercel Dashboard)
Set these in **Settings → Environment Variables**:
```
DRUPAL_BASE_URL=http://72.62.80.155
DRUPAL_API_USER=rare
DRUPAL_API_PASS=<password>
NEXTAUTH_SECRET=<random-string>
NEXTAUTH_URL=https://rareimagery.net
XAI_API_KEY=<key>
X_CONSUMER_KEY=<key>
X_CONSUMER_SECRET=<key>
ANTHROPIC_API_KEY=<key>
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
PRINTFUL_API_KEY=<key>
```

### Caching
- `vercel.json`: 60s server cache + 300s stale-while-revalidate
- SSG pages regenerate on deploy
- API routes are dynamic (no cache)

## Backend Deployment (VPS)

### SSH Access
```bash
ssh root@72.62.80.155
```

### Deploy Backend Changes
```bash
ssh root@72.62.80.155
cd /var/www/rareimagery-marketplace
git pull origin main
docker exec rare-drupal /opt/drupal/vendor/bin/drush cache:rebuild
```

### Deploy New Module Config
```bash
# After adding/changing YAML configs:
docker exec rare-drupal /opt/drupal/vendor/bin/drush config:import -y
docker exec rare-drupal /opt/drupal/vendor/bin/drush cache:rebuild
```

### Enable a New Module
```bash
docker exec rare-drupal /opt/drupal/vendor/bin/drush en rareimagery_xstore -y
docker exec rare-drupal /opt/drupal/vendor/bin/drush cache:rebuild
```

### Fresh Install (Nuclear Option)
```bash
docker exec rare-drupal /opt/drupal/vendor/bin/drush site:install minimal \
  --db-url=pgsql://rare_user:PASSWORD@rare-postgres/rare_drupal \
  --account-name=rare --account-pass=PASSWORD -y
docker exec rare-drupal /opt/drupal/vendor/bin/drush en rareimagery_xstore rareimagery_ai -y
```

## Docker Containers

```bash
# Check status
docker ps
# Expected: rare-drupal, rare-postgres

# Restart
docker compose restart

# Rebuild (after Dockerfile changes)
docker compose build && docker compose up -d

# Logs
docker logs rare-drupal --tail 50 -f

# Database shell
docker exec -it rare-postgres psql -U rare_user -d rare_drupal
```

## Monitoring

### Check Frontend
```bash
curl -sI https://rareimagery.net | head -5
# Should show: HTTP/2 200
```

### Check Backend
```bash
curl -s http://72.62.80.155/jsonapi | head -5
# Should show JSON:API response
```

### Check Drupal Status
```bash
ssh root@72.62.80.155
docker exec rare-drupal /opt/drupal/vendor/bin/drush status
docker exec rare-drupal /opt/drupal/vendor/bin/drush watchdog:show --count=10
```

## Common Issues

### Vercel shows 404
- Check Vercel dashboard → Deployments tab
- Verify domain `rareimagery.net` is assigned in Settings → Domains
- Ensure Root Directory is set to `frontend`
- Check build logs for errors

### Drupal API returns 403
- Verify `jsonapi_basic_auth` module is enabled
- Check `DRUPAL_API_USER` / `DRUPAL_API_PASS` match Drupal credentials
- Verify JSON:API write is enabled: `drush config:get jsonapi.settings read_only`

### Store page shows "No creators found"
- Drupal unreachable from Vercel → check VPS is running
- Firewall blocking port 80 → `ufw allow 80`
- Check `DRUPAL_BASE_URL` in Vercel env vars

### Images not loading
- Check `next.config.ts` has the image domain whitelisted
- Verify image exists in Drupal: `drush entity:load file <fid>`

## Backup

### Database
```bash
ssh root@72.62.80.155
docker exec rare-postgres pg_dump -U rare_user rare_drupal > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
cat backup_20260311.sql | docker exec -i rare-postgres psql -U rare_user -d rare_drupal
```

## Makefile Quick Reference

```bash
make up              # Start Docker containers
make down            # Stop containers
make build           # Rebuild images
make install         # Fresh Drupal install
make cr              # Cache rebuild
make drush CMD="..." # Run Drush command
make export          # Export config
make import          # Import config
make reindex         # Rebuild search
make test            # PHPUnit
make lint            # PHP CodeSniffer
```

## Gotchas

- Local Docker DB has **ZERO data** — all real data on remote VPS
- `drush config:set ... false` doesn't work for booleans — use `0`
- Basic Auth requires custom `jsonapi_basic_auth` module
- `.env.local` has credentials — never commit
- `frontend/` has its own `.git` pointing to `rare-frontend.git` (legacy)
- Vercel must be pointed at `rare.git` with root directory `frontend`
