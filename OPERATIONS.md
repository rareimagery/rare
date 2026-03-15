# RareImagery.net — Operations Guide

## Quick Reference

| System | Location | Deployment |
|--------|----------|------------|
| **Frontend** | `frontend/src/` | Vercel (auto-deploy on push to main) |
| **Backend** | `web/modules/custom/` | VPS 72.62.80.155 (Docker) |
| **Database** | PostgreSQL 16 | VPS container `rare-postgres` |
| **Repo** | `rareimagery/rare.git` | Single repo, everything tracked |

---

## Architecture

```
Browser → Vercel (Next.js 16) → Drupal JSON:API (72.62.80.155)
                               → Stripe API
                               → Printful API
                               → xAI/Grok API
                               → Anthropic/Claude API
```

---

## 5 Agents

Each agent has a detailed spec in `.claude/agents/`. Spin up the right one for the task.

| Agent | File | Use When |
|-------|------|----------|
| **Next.js** | `.claude/agents/nextjs.md` | Themes, components, pages, Tailwind, Vercel |
| **Connection** | `.claude/agents/drupal-nextjs-connection.md` | API routes, auth, data mapping, JSON:API |
| **Drupal** | `.claude/agents/drupal.md` | Modules, entities, Commerce, server config |
| **xAI Import** | `.claude/agents/xai-import.md` | X profile fetch, Grok AI, data sync |
| **Store Creation** | `.claude/agents/store-creation.md` | Wizard flow, approval, provisioning |

### How to Use Agents

Tell Claude which domain you're working in:
- "Add a new theme" → **Next.js agent**
- "Fix checkout not creating orders" → **Connection agent**
- "Add a new product field" → **Drupal agent**
- "Grok isn't returning profile data" → **xAI Import agent**
- "Store wizard skips step 3" → **Store Creation agent**

For cross-cutting tasks, multiple agents run in parallel.

---

## Project Structure

```
c:\rare\
├── frontend/                    # Next.js 16 (Vercel)
│   └── src/
│       ├── app/                 # 32 routes (pages + API)
│       │   ├── api/             # 17 API endpoints
│       │   ├── stores/[creator] # Public store pages
│       │   ├── products/[slug]  # Product detail
│       │   ├── build/           # Store creation entry
│       │   ├── console/         # Admin dashboard
│       │   └── login/           # Auth
│       ├── components/          # 25 components (7,953 LOC)
│       │   ├── themes/          # 6 themes (3,405 LOC)
│       │   └── builder/         # Page builder (4 files)
│       └── lib/                 # 9 utility files
│           ├── drupal.ts        # API client (765 LOC)
│           ├── x-import.ts      # X data fetch (445 LOC)
│           ├── grok.ts          # Grok AI (125 LOC)
│           └── notifications.ts # Email/SMS (274 LOC)
│
├── web/modules/custom/          # Drupal backend
│   ├── rareimagery_xstore/      # Core platform (286 files)
│   │   ├── src/Controller/      # 4 controllers
│   │   ├── src/EventSubscriber/ # 3 subscribers (fees, Stripe, Printful)
│   │   ├── src/Service/         # 5 services
│   │   ├── src/Plugin/rest/     # 12 REST resources
│   │   ├── src/Resolver/        # 2 resolvers
│   │   └── config/install/      # 220 YAML configs
│   ├── rareimagery_ai/          # AI admin chat (15 files)
│   └── rareimagery_x_import/    # X profile import (25 files)
│
├── web/themes/custom/rareimagery/  # Drupal admin theme
├── docker/                      # nginx, php-fpm, host-nginx configs
├── scripts/                     # 23 PHP setup/debug scripts
├── .claude/agents/              # 5 agent definitions
├── docker-compose.yml           # Local dev (postgres + drupal)
├── Makefile                     # Dev workflow commands
├── Dockerfile                   # Drupal image
└── deploy.sh                    # VPS deployment
```

---

## Frontend Routes

### Public
| Route | File | Purpose |
|-------|------|---------|
| `/` | `app/page.tsx` | Landing page + creator grid |
| `/stores/[creator]` | `app/stores/[creator]/page.tsx` | Public store (themed) |
| `/products/[slug]` | `app/products/[slug]/page.tsx` | Product detail (465 LOC) |
| `/build` | `app/build/page.tsx` | Store creation wizard entry |
| `/login` | `app/login/page.tsx` | NextAuth sign-in |

### Console (Auth Required)
| Route | File | Purpose |
|-------|------|---------|
| `/console` | `app/console/page.tsx` | Dashboard |
| `/console/stores` | `app/console/stores/page.tsx` | Store list |
| `/console/stores/new` | `app/console/stores/new/page.tsx` | Create store |
| `/console/stores/[id]` | `app/console/stores/[id]/page.tsx` | Edit store |
| `/console/stores/[id]/theme` | `app/console/stores/[id]/theme/page.tsx` | Theme editor (379 LOC) |

### API Routes (17)
| Route | Method | Purpose |
|-------|--------|---------|
| `/api/auth/[...nextauth]` | * | NextAuth (X OAuth) |
| `/api/stores/create` | POST | Create store in Drupal |
| `/api/stores/provision` | POST | Provision subdomain |
| `/api/stores/select-theme` | POST | Update theme |
| `/api/stores/approve` | POST | Admin approval |
| `/api/stores/enhance-profile` | POST | X fetch + Grok AI |
| `/api/stores/import-x-data` | POST | Raw X import |
| `/api/stores/products` | GET | Product list |
| `/api/checkout` | POST | Stripe checkout |
| `/api/webhooks/stripe` | POST | Stripe webhooks |
| `/api/chat` | POST | Claude Haiku page builder |
| `/api/builds` | GET/POST/DELETE | Saved builds CRUD |
| `/api/printful/connect` | POST | Printful OAuth |
| `/api/printful/products` | GET | Printful catalog |
| `/api/printful/sync` | POST | Sync products |
| `/api/notifications/preferences` | POST | Notification settings |

---

## Themes (6)

| Theme | Component | Lines | Style |
|-------|-----------|-------|-------|
| xai3 | `Xai3Theme.tsx` | 975 | Modern 3-column (default) |
| xmimic | `XMimicTheme.tsx` | 1,342 | X.com single-column mimic |
| myspace | `MySpaceTheme.tsx` | 1,151 | Y2K nostalgia |
| minimal | `MinimalTheme.tsx` | 509 | Clean, simple |
| editorial | `EditorialTheme.tsx` | 353 | Magazine layout |
| neon | `NeonTheme.tsx` | 331 | Cyberpunk glow |

Stored as `field_store_theme` on `creator_x_profile` nodes in Drupal.

---

## Commerce Model

### Entity Relationships
```
X Creator → creator_x_profile (node)
              ↓ field_linked_store
           commerce_store (type: creator)
              ↓
           commerce_product (3 types)
              ↓
           commerce_product_variation (3 types)
              ↓
           commerce_order (3 types, split by product type)
```

### Product Types
| Type | Fulfillment | Variation |
|------|-------------|-----------|
| `physical_pod` | Printful auto-fulfill | pod_variation (size, color) |
| `physical_custom` | Manual | custom_variation (size, color, material) |
| `digital_download` | Instant file delivery | digital_variation (file) |

### Platform Fees (PlatformFeeSubscriber)
- Physical orders: **$1.00** per order
- Digital orders: **$0.05** per order
- Applied as locked, non-taxable adjustment

### Stripe Connect Payment Flow
1. Customer pays full amount → platform Stripe
2. `application_fee_amount` → platform keeps fee
3. `transfer_data.destination` → creator gets remainder

---

## Auth

### Frontend (NextAuth)
- Provider: X (Twitter) OAuth
- Roles: `admin`, `store_owner`, `creator`
- Session: `{ role, storeSlug, xUsername, xAccessToken, xId }`
- Middleware protects `/console/*` routes

### Drupal API
- Method: Basic Auth via `drupalAuthHeaders()`
- Custom module: `jsonapi_basic_auth`
- Env: `DRUPAL_API_USER` + `DRUPAL_API_PASS`

---

## Environment Variables

### Required (.env)
```
# Database
POSTGRES_DB=rare_drupal
POSTGRES_USER=rare_user
POSTGRES_PASSWORD=
POSTGRES_PORT=5432

# Drupal
DRUPAL_PORT=80
DRUPAL_API_USER=rare
DRUPAL_API_PASS=

# APIs
XAI_API_KEY=
X_CONSUMER_KEY=            # Drupal/backend integrations
X_CONSUMER_SECRET=
X_CLIENT_ID=               # NextAuth X OAuth 2.0 login
X_CLIENT_SECRET=
X_API_BEARER_TOKEN=
ANTHROPIC_API_KEY=
STRIPE_SECRET_KEY=
STRIPE_PUBLISHABLE_KEY=
STRIPE_WEBHOOK_SECRET=
PRINTFUL_API_KEY=

# Frontend (.env.local)
NEXTAUTH_SECRET=
NEXTAUTH_URL=https://rareimagery.net
DRUPAL_API_URL=http://72.62.80.155
```

---

## Common Commands

### Deploy
```bash
# Frontend (auto on push)
git push origin main

# Backend
ssh root@72.62.80.155
cd /var/www/rareimagery-marketplace
git pull origin main
docker exec rare-drupal /opt/drupal/vendor/bin/drush cache:rebuild
```

### Local Dev
```bash
make up                    # Start Docker containers
make cr                    # Clear Drupal cache
make drush CMD="status"    # Run Drush command
cd frontend && npm run dev # Start Next.js dev server
```

### Makefile Targets
```bash
make up / down / build     # Docker lifecycle
make install               # Fresh Drupal install
make drush CMD="..."       # Any Drush command
make cr                    # Cache rebuild
make export / import       # Config management
make reindex               # Rebuild search index
make test / lint           # Code quality
```

---

## Codebase Stats

| Area | Files | Lines |
|------|-------|-------|
| Frontend src/ | 50+ | ~5,000 |
| Themes | 6 | 3,405 |
| Components | 25 | 7,953 |
| API routes | 17 | 4,102 |
| rareimagery_xstore | 286 | 2,191 PHP + 220 YAML |
| rareimagery_ai | 15 | 1,167 PHP |
| rareimagery_x_import | 25 | 606 PHP |
| Scripts | 23 | ~2,000 PHP |
| **Total custom code** | **~420** | **~17,000+** |

---

## Gotchas

- Local Docker DB has **ZERO data** — all real data on remote VPS
- `drush config:set ... false` doesn't work — use `0`
- Basic Auth requires custom `jsonapi_basic_auth` module on server
- `.env.local` has credentials — never commit
- Frontend and backend are separate git histories inside one repo
- `frontend/` has its own `.git` pointing to `rare-frontend.git`
- Vercel must be pointed at `rare.git` with root directory `frontend`
