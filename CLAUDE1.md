# RareImagery — Claude Code Project Context

## Project Overview

**Site:** rareimagery.net
**Brand:** RareImagery — X Creator Marketplace
**Concept:** X (Twitter) creators sign in, import their profile via Grok AI, and get branded storefronts on subdomains (e.g. `elonmusk.rareimagery.net`)
**Stack:** Next.js 16 (Vercel) + Drupal 10 (headless CMS) + Stripe + Printful

## Architecture

```
rare/
├── frontend/           → Next.js 16 App Router (Vercel)
│   └── src/
│       ├── app/        → App Router pages + API routes
│       ├── components/ → React components (client + server)
│       └── lib/        → Data layer, integrations, utilities
├── web/                → Drupal 10 (Docker / Cloudways)
│   └── modules/custom/rareimagery_x_import/
│       └── src/Service/ → XApiService, GrokService
├── scripts/            → PHP setup scripts (drush php:script)
└── hosting/            → Hosting docs
```

### Frontend — Next.js 16 (Vercel)
- **App Router** with server components + `"use client"` where needed
- **NextAuth v4** — TwitterProvider (OAuth 2.0 PKCE) + CredentialsProvider (admin/store owner)
- **JWT strategy** — stores xAccessToken, xRefreshToken, xUsername, xId, role
- **Tailwind CSS 4** — dark theme (zinc/indigo palette)
- **Wildcard subdomains** — Cloudflare DNS → Vercel, middleware rewrites `[creator].rareimagery.net` → `/stores/[creator]`
- **Stripe** for checkout, **Printful** for print-on-demand
- **Nodemailer** (Brevo SMTP) + **Telnyx** for notifications

### Backend — Drupal 10 (headless)
- Exposes data via **JSON:API** at `/jsonapi/`
- **Drupal Commerce** — stores, products, variations, orders
- Custom module: `rareimagery_x_import` (X API + Grok AI services)
- All frontend writes use `Bearer ${DRUPAL_API_TOKEN}` auth
- Docker Compose for local dev (Postgres on port 5433)

### Key Data Model
```
commerce_store (online)
├── field_store_slug        (string, unique subdomain)
├── field_store_status      (list: pending/approved/rejected)
├── field_store_theme       (string_long, JSON theme config)
└── field_linked_x_profile  (entity_reference → node)

node (creator_x_profile)
├── field_x_username, field_bio_description, field_follower_count
├── field_top_posts, field_top_followers, field_metrics (JSON)
├── field_profile_picture, field_background_banner (image)
├── field_store_theme (string: default/minimal/neon/editorial/myspace)
├── field_myspace_* (background, music_url, glitter_color, accent_color)
└── field_linked_store (entity_reference → commerce_store)

user
├── field_phone_number, field_notification_channel, field_sms_alert_level
```

### Key Integration Points
- Store CRUD: `POST/PATCH /jsonapi/commerce_store/online`
- Creator profiles: `POST/PATCH /jsonapi/node/creator_x_profile`
- Products: `GET/POST /jsonapi/commerce_product/default`
- Users: `GET/PATCH /jsonapi/user/user`
- X API v2: OAuth 2.0 user tokens (users.read, tweet.read, follows.read, offline.access)
- Grok AI (xAI): `grok-3` on Next.js, `grok-3-mini` on Drupal

---

## Agent Routing

| Domain | Agent | Owns |
|--------|-------|------|
| Next.js + Vercel | `nextjs-developer` | `/frontend/src/` — components, pages, API routes, lib, deployment |
| Drupal API | `drupal-api` | `/web/`, `/scripts/` — modules, fields, JSON:API, Docker |
| Data Layer | `data-integration` | `/frontend/src/lib/` — Drupal client, X import, Grok, notifications |
| Design System | `brand-designer` | Tailwind config, theme system, visual consistency |

**Rules:**
- Agents own their domain — never have one agent modify another's files
- Run agents in parallel when changes are independent
- Route integration changes through `data-integration` agent first
- **Read `AGENT_STATE.md` before starting any work** — check for pending handoffs and active projects
- **Update `AGENT_STATE.md` when done** — log what you changed and what downstream agents need

---

## Development Conventions

### Next.js
- App Router with async server components (no `getStaticProps`)
- API routes in `/app/api/` as `route.ts` with named exports (GET, POST, PATCH)
- API calls centralized in `/lib/` — never inline fetch calls in components
- Component files: PascalCase (`StoreBuilderWizard.tsx`)
- Auth checks: `getToken()` for API routes, `getServerSession()` for pages

### Drupal
- Content types follow Commerce hierarchy + Creator X Profile
- Field machine names: `field_` prefix
- Setup scripts in `/scripts/` run via `drush php:script`
- JSON:API for both reads and writes (POST/PATCH with Bearer token)

### Shared
- Never commit `.env` files — use `.env.example`
- CORS must allow `*.rareimagery.net` subdomains + Vercel preview URLs
- All store creation requires admin approval (`field_store_status: "pending"`)

---

## Environment Variables

```bash
# Frontend (.env.local)
DRUPAL_API_URL=https://[cloudways-domain]
DRUPAL_API_TOKEN=
NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
X_CLIENT_ID=
X_CLIENT_SECRET=
NEXTAUTH_SECRET=
NEXTAUTH_URL=
CONSOLE_ADMIN_EMAIL=
CONSOLE_ADMIN_PASSWORD=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
XAI_API_KEY=
SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
EMAIL_FROM=notifications@rareimagery.net
TELNYX_API_KEY=
TELNYX_FROM_NUMBER=
```

---

## Current Features (implemented)
- [x] X OAuth sign-in with token auto-refresh
- [x] Grok AI profile analysis + store builder wizard
- [x] Multi-tenant creator stores on subdomains
- [x] 5 store themes (default, minimal, neon, editorial, myspace)
- [x] Admin approval workflow (pending/approved/rejected)
- [x] Console dashboard for admin + store owners
- [x] Product management + Printful POD integration
- [x] Stripe checkout
- [x] Email (Brevo) + SMS (Telnyx) notifications
- [x] Notification preferences per user
