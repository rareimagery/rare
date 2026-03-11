# Subdomain Creation – RareImagery X Marketplace
### How `[creator].rareimagery.net` Goes Live Instantly

---

## The Core Concept

Subdomain creation is **a database task, not a DNS task.**

The Cloudflare wildcard `*.rareimagery.net` already routes every possible subdomain to Vercel. Creating a store with the slug `rareimagery` is all that's needed — the subdomain is live the moment that record exists in Drupal. No DNS changes. No API calls to Cloudflare. No waiting.

```
Creating slug "rareimagery" in Drupal
              │
              ▼
rareimagery.rareimagery.net ✅ LIVE IMMEDIATELY
(wildcard was already handling this)
```

---

## Cloudflare DNS Setup (One-Time)

Do this once and never touch DNS again for new stores.

| Type | Name | Value | Proxy |
|------|------|-------|-------|
| CNAME | `@` | `cname.vercel-dns.com` | ✅ |
| CNAME | `www` | `cname.vercel-dns.com` | ✅ |
| CNAME | `*` | `cname.vercel-dns.com` | ✅ |
| CNAME | `console` | `cname.vercel-dns.com` | ✅ |
| A | `api` | `72.62.80.155` | ✅ |

> The `*` wildcard record is the key. It covers every `[creator].rareimagery.net` subdomain automatically — existing and future.

---

## Vercel Domain Setup (One-Time)

In your Vercel project → **Settings → Domains**, add:

```
rareimagery.net
*.rareimagery.net
console.rareimagery.net
```

Vercel verifies these via the Cloudflare CNAME records above.

---

## How Next.js Reads the Subdomain

Every request to `*.rareimagery.net` hits Vercel. The middleware intercepts it, reads the subdomain, and rewrites the route internally.

### `middleware.ts`

```typescript
import { NextRequest, NextResponse } from 'next/server'
import { getToken } from 'next-auth/jwt'

const RESERVED_SUBDOMAINS = [
  'console', 'www', 'api', 'admin', 'app',
  'mail', 'support', 'help', 'blog', 'login'
]

export async function middleware(req: NextRequest) {
  const host = req.headers.get('host') || ''
  const baseDomain = process.env.NEXT_PUBLIC_BASE_DOMAIN || 'rareimagery.net'
  const subdomain = host.replace(`.${baseDomain}`, '').replace(baseDomain, '')

  // Console — protected admin area
  if (subdomain === 'console') {
    const token = await getToken({ req })
    if (!token && !req.nextUrl.pathname.startsWith('/login')) {
      return NextResponse.redirect(new URL('/login', req.url))
    }
    return NextResponse.next()
  }

  // Creator store — rewrite to /stores/[creator]
  if (subdomain && !RESERVED_SUBDOMAINS.includes(subdomain)) {
    return NextResponse.rewrite(
      new URL(`/stores/${subdomain}${req.nextUrl.pathname}`, req.url)
    )
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}
```

---

## Reserved Slugs

Block these at store creation time — they conflict with system subdomains.

```typescript
export const RESERVED_SLUGS = [
  'console', 'admin', 'api', 'www', 'app', 'mail',
  'support', 'help', 'blog', 'shop', 'store', 'login',
  'signup', 'dashboard', 'rareimagery'
]
```

---

## Slug Validation Rules

```typescript
// Valid: lowercase letters, numbers, hyphens. 3–30 chars.
export function isValidSlug(slug: string): boolean {
  if (RESERVED_SLUGS.includes(slug)) return false
  return /^[a-z0-9-]{3,30}$/.test(slug)
}
```

---

## How the Creator Store Page Reads Drupal Data

### `app/stores/[creator]/page.tsx`

```typescript
async function getCreatorStore(slug: string) {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/commerce_store/online` +
    `?filter[field_store_slug]=${slug}&include=field_linked_x_profile`,
    {
      headers: { Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}` },
      next: { revalidate: 60 }, // ISR — refresh every 60s
    }
  )
  return res.json()
}

export default async function CreatorStorePage({
  params,
}: {
  params: { creator: string }
}) {
  const data = await getCreatorStore(params.creator)
  const store = data?.data?.[0]

  if (!store) return <div>Store not found</div>

  const profile = store?.relationships?.field_linked_x_profile?.data

  return (
    <main>
      <h1>{store.attributes.name}</h1>
      {/* Render X profile data, products, etc. */}
    </main>
  )
}
```

---

## Full Subdomain Lifecycle

```
1. Admin creates store with slug "rareimagery" in console
            │
            ▼
2. Drupal stores field_store_slug = "rareimagery"
            │
            ▼
3. Visitor hits rareimagery.rareimagery.net
            │
            ▼
4. Cloudflare wildcard *.rareimagery.net → Vercel
            │
            ▼
5. Next.js middleware reads subdomain "rareimagery"
            │
            ▼
6. Rewrites to /stores/rareimagery internally
            │
            ▼
7. Page fetches Drupal: ?filter[field_store_slug]=rareimagery
            │
            ▼
8. Renders branded creator storefront ✅
```

---

## Environment Variables

```bash
NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
```
