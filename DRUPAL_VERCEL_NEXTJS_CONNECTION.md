# Connecting Drupal (VPS) → rareimagery.net → Vercel Next.js
### RareImagery X Marketplace – Full Stack Architecture

---

## Architecture Overview

```
Creators/Visitors
      │
      ▼
┌─────────────────────────────────────────┐
│           Cloudflare (DNS + CDN)        │
│         rareimagery.net nameservers     │
└────────────────┬────────────────────────┘
                 │
       ┌─────────┴──────────┐
       ▼                    ▼
*.rareimagery.net      api.rareimagery.net
rareimagery.net              │
       │                    ▼
┌──────────────┐    ┌───────────────────┐
│    Vercel    │    │  Hostinger VPS    │
│  Next.js 14  │◄───│  Drupal 10.3      │
│ Creator Stores│   │  (JSON:API)       │
│              │    │  Postgres 16      │
└──────────────┘    └───────────────────┘
```

**How data flows:**
1. `creatorname.rareimagery.net` hits Vercel → Next.js reads the subdomain
2. Next.js calls `api.rareimagery.net` (Drupal JSON:API) for store/profile data
3. Drupal returns creator store + X profile data (populated by Grok)
4. Next.js renders the branded storefront and serves it to the visitor

---

## Step 1: Set Up Cloudflare (DNS layer)

This is the recommended approach — Cloudflare sits in front of everything, giving you free SSL, DDoS protection, and easy DNS control.

1. Go to [cloudflare.com](https://cloudflare.com) → Add site → `rareimagery.net`
2. Cloudflare will scan your existing DNS records
3. At your domain registrar, **replace nameservers** with the two Cloudflare nameservers provided
4. Add these DNS records in Cloudflare:

| Type | Name | Value | Proxy |
|------|------|-------|-------|
| A | `api` | `72.62.80.155` | ✅ Proxied |
| CNAME | `@` | `cname.vercel-dns.com` | ✅ Proxied |
| CNAME | `www` | `cname.vercel-dns.com` | ✅ Proxied |
| CNAME | `*` | `cname.vercel-dns.com` | ✅ Proxied |

> The wildcard `*` record routes every `creatorname.rareimagery.net` to Vercel automatically — no manual DNS entry per creator.

---

## Step 2: Expose Drupal as a Headless API

Drupal 10 ships with JSON:API built in. Run these in your VPS:

```bash
# Enable JSON:API and CORS support
docker compose exec drupal drush en jsonapi -y
docker compose exec drupal drush cr
```

### Configure CORS (allow Vercel to call Drupal)

Edit `drupal/sites/default/services.yml` (create if missing):

```yaml
parameters:
  cors.config:
    enabled: true
    allowedHeaders:
      - '*'
    allowedMethods:
      - GET
      - POST
      - PATCH
      - DELETE
    allowedOrigins:
      - 'https://rareimagery.net'
      - 'https://*.rareimagery.net'
      - 'https://*.vercel.app'
    exposedHeaders: false
    maxAge: false
    supportsCredentials: false
```

```bash
docker compose exec drupal drush cr
```

### Update docker-compose.yml — change port 8080 → 80

Now that Cloudflare proxies `api.rareimagery.net`, Drupal should listen on port 80:

```yaml
ports:
  - "80:80"   # was 8080:80
```

```bash
sudo ufw allow 80
docker compose up -d
```

### Test your API endpoint

```
https://api.rareimagery.net/jsonapi/node/creator_x_profile
https://api.rareimagery.net/jsonapi/commerce_store/online
```

You should see JSON responses. If you do, Drupal is live as a headless backend.

---

## Step 3: Set Up Vercel for Wildcard Subdomains

### Add domain in Vercel

1. Go to your Vercel project → **Settings → Domains**
2. Add `rareimagery.net`
3. Add `*.rareimagery.net` (wildcard)
4. Vercel will verify via the Cloudflare DNS records you set above

### Environment variables in Vercel

Go to **Settings → Environment Variables** and add:

```
DRUPAL_API_URL=https://api.rareimagery.net
DRUPAL_API_TOKEN=your_drupal_api_token   # see Step 4
NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
```

---

## Step 4: Secure the API (Simple Token Auth)

Install the Simple OAuth or Key Auth module on Drupal:

```bash
docker compose exec drupal composer require drupal/simple_oauth
docker compose exec drupal drush en simple_oauth -y
docker compose exec drupal drush cr
```

Configure at **Configuration → Simple OAuth → Generate keys**, then create a client for your Next.js app. Store the token as `DRUPAL_API_TOKEN` in Vercel.

---

## Step 5: Next.js — Read Subdomain + Fetch from Drupal

### `middleware.ts` — detect the creator subdomain

```typescript
import { NextRequest, NextResponse } from 'next/server'

export function middleware(req: NextRequest) {
  const host = req.headers.get('host') || ''
  const baseDomain = process.env.NEXT_PUBLIC_BASE_DOMAIN || 'rareimagery.net'
  const subdomain = host.replace(`.${baseDomain}`, '').replace(baseDomain, '')

  if (subdomain && subdomain !== 'www' && subdomain !== '') {
    // Rewrite to /stores/[creator] route
    return NextResponse.rewrite(new URL(`/stores/${subdomain}${req.nextUrl.pathname}`, req.url))
  }

  return NextResponse.next()
}
```

### `app/stores/[creator]/page.tsx` — fetch creator data from Drupal

```typescript
async function getCreatorStore(username: string) {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/node/creator_x_profile?filter[field_x_username]=${username}&include=field_linked_store`,
    {
      headers: {
        Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}`,
        'Content-Type': 'application/vnd.api+json',
      },
      next: { revalidate: 60 }, // ISR — revalidate every 60s
    }
  )
  return res.json()
}

export default async function CreatorStorePage({ params }: { params: { creator: string } }) {
  const data = await getCreatorStore(params.creator)
  const profile = data?.data?.[0]?.attributes

  if (!profile) return <div>Creator not found</div>

  return (
    <main>
      <img src={profile.field_pfp_url} alt="PFP" />
      <h1>{profile.field_x_username}</h1>
      <p>{profile.field_bio}</p>
      {/* Products, top posts, etc. */}
    </main>
  )
}
```

---

## Step 6: Deploy & Verify

```bash
# Push your Next.js code to GitHub — Vercel auto-deploys
git add .
git commit -m "connect drupal headless API + wildcard subdomain routing"
git push
```

**Test these URLs after deploy:**

| URL | Expected result |
|-----|----------------|
| `https://rareimagery.net` | Main Next.js site |
| `https://api.rareimagery.net/jsonapi` | Drupal JSON:API root |
| `https://testcreator.rareimagery.net` | Creator storefront (Next.js reads subdomain → fetches Drupal) |

---

## Full Stack Summary

| Layer | Tech | Location | URL |
|-------|------|----------|-----|
| DNS + SSL | Cloudflare | Cloud | — |
| Frontend | Next.js 14 | Vercel | `*.rareimagery.net` |
| CMS / API | Drupal 10.3 | Hostinger VPS | `api.rareimagery.net` |
| Database | PostgreSQL 16 | Hostinger VPS | Internal |
| AI | Grok (xAI) | API | Via Drupal AI module |

---

## Next Steps

- **"Build the Grok 1-click X import"** — auto-populate Creator X Profile via button
- **"Add Drupal Commerce checkout"** — so creators can sell products through the API
- **"Set up ISR + on-demand revalidation"** — so creator store pages update instantly when Drupal data changes
- **"Add Stripe to Next.js"** — payments handled at the Vercel layer
