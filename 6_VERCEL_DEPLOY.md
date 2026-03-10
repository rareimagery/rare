# 6. Vercel Deploy – rareimagery.net Live

## Frontend (Next.js)
1. **Connect GitHub Repo** (rareimagery/rare-frontend):
   - vercel.com → New Project → Import rare-frontend
   - Domain: rareimagery.net + *.rareimagery.net (wildcard)
   - Env vars:
     ```
     DRUPAL_API_URL=https://your-drupal-vps/jsonapi
     DRUPAL_API_TOKEN=your-oauth-token
     NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
     ```
   - Auto-deploys on push to main.

2. **CLI Deploy** (local test):
   ```
   cd frontend
   npm i
   npx vercel login
   npx vercel --prod
   ```

`vercel.json` ready (rewrites, headers).

## Backend (Drupal VPS)
- Hostinger Ubuntu: Run `HOSTINGER_UBUNTU_DRUPAL_LIVE_SETUP.md`
- Domain api.rareimagery.net → VPS IP
- JSON:API + OAuth token for frontend.

## Subdomains Magic
- Cloudflare wildcard `*.rareimagery.net` → cname.vercel-dns.com (proxied)
- Vercel middleware → /stores/[creator]
- Frontend fetches Drupal by slug.

## Post-Deploy
- Test: elonmusk.rareimagery.net → Live store!
- ISR caches profiles (60s revalidate).

**Live**: Frontend Vercel + Drupal VPS = Scalable X marketplace.

*(SSL auto, CDN global.)*