# RareImagery.net — Complete Platform Documentation

---

# Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Environment Setup](#2-environment-setup)
3. [Docker & Local Dev](#3-docker--local-dev)
4. [Drupal Backend](#4-drupal-backend)
5. [Commerce & Payments](#5-commerce--payments)
6. [Next.js Frontend](#6-nextjs-frontend)
7. [Authentication & Roles](#7-authentication--roles)
8. [Drupal ↔ Next.js API Connection](#8-drupal--nextjs-api-connection)
9. [xAI & Grok Import](#9-xai--grok-import)
10. [Store Creation Flow](#10-store-creation-flow)
11. [Themes & Page Builder](#11-themes--page-builder)
12. [Unified Console Dashboard](#12-unified-console-dashboard)
13. [Invite Code System](#13-invite-code-system)
14. [Next.js API Routes](#14-nextjs-api-routes)
15. [Frontend Components](#15-frontend-components)
16. [Drupal Custom Modules](#16-drupal-custom-modules)
17. [Subdomain Routing & Store Pages](#17-subdomain-routing--store-pages)
18. [Library Files & Utilities](#18-library-files--utilities)
19. [Infrastructure & Deployment](#19-infrastructure--deployment)
20. [Quick Start](#20-quick-start)

---

# 1. Architecture Overview

## System Diagram

```
                    ┌─────────────────────────────────────┐
                    │           Browser / Client           │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │     Vercel (Next.js 16 Frontend)     │
                    │     rareimagery.net                   │
                    │                                      │
                    │  Pages: /, /stores/[creator],         │
                    │         /build, /console, /products   │
                    │  API Routes: /api/stores/*, /api/chat │
                    └──┬──────┬──────┬──────┬──────┬───────┘
                       │      │      │      │      │
          ┌────────────▼┐  ┌──▼───┐ ┌▼────┐ ┌▼───┐ ┌▼────────┐
          │ Drupal 10.3  │  │Stripe│ │xAI/ │ │X   │ │Anthropic│
          │ JSON:API     │  │  API │ │Grok │ │API │ │Claude   │
          │ 72.62.80.155 │  │      │ │     │ │v2  │ │Haiku    │
          └──────┬───────┘  └──────┘ └─────┘ └────┘ └─────────┘
                 │
          ┌──────▼───────┐
          │ PostgreSQL 16 │
          │ rare-postgres  │
          └───────────────┘
```

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Frontend | Next.js (App Router) | 16.1.6 |
| UI | React + TypeScript | 19.2.3 |
| Styling | Tailwind CSS | 4.x |
| Auth | NextAuth | 4.24.13 |
| Backend | Drupal (headless) | 10.3 |
| Commerce | Drupal Commerce | 3.x |
| Database | PostgreSQL | 16 |
| Payments | Stripe + Stripe Connect | — |
| POD | Printful API | — |
| AI (frontend) | xAI Grok | grok-3 |
| AI (page builder) | Anthropic Claude | haiku-4.5 |
| Hosting (frontend) | Vercel | — |
| Hosting (backend) | Hostinger VPS (Ubuntu 24.04) | — |
| Containers | Docker + Docker Compose | — |

## Repository Structure

Single repo: `github.com/rareimagery/rare.git`

```
c:\rare\
├── frontend/                    # Next.js 16 app (deployed to Vercel)
│   └── src/
│       ├── app/                 # 32 routes (pages + 17 API endpoints)
│       ├── components/          # 25 components (7,953 LOC)
│       └── lib/                 # 9 utility modules
│
├── web/modules/custom/          # Drupal backend (deployed to VPS)
│   ├── rareimagery_xstore/      # Core platform module (286 files)
│   ├── rareimagery_ai/          # AI admin chat (15 files)
│   └── rareimagery_x_import/    # X profile import (25 files)
│
├── web/themes/custom/           # Drupal theme
├── docker/                      # Docker configs (nginx, php, host-nginx)
├── scripts/                     # 23 PHP setup scripts
├── docs/                        # This documentation
├── .claude/agents/              # 5 agent definitions
├── docker-compose.yml           # Local dev containers
├── Makefile                     # Dev workflow
├── Dockerfile                   # Drupal image
└── deploy.sh                    # VPS deployment
```

## Codebase Stats

| Area | Files | Lines |
|------|-------|-------|
| Frontend components | 25 | 7,953 |
| Frontend API routes | 17 | 4,102 |
| Frontend lib | 9 | ~1,800 |
| rareimagery_xstore | 286 | 2,191 PHP + 220 YAML |
| rareimagery_ai | 15 | 1,167 PHP |
| rareimagery_x_import | 25 | 606 PHP |
| **Total** | **~420** | **~17,000+** |

---

# 2. Environment Setup

## Prerequisites

- Docker + Docker Compose
- Node.js 22+ and npm
- Git
- SSH access to 72.62.80.155 (for backend deployment)

## Environment Files

### 1. Root `.env` (Backend — Docker + Drupal)

```bash
cp .env.example .env
```

Fill in:
```
# Database
POSTGRES_DB=rare_drupal
POSTGRES_USER=rare_user
POSTGRES_PASSWORD=<strong-password>
POSTGRES_PORT=5432

# Drupal
DRUPAL_PORT=80

# X/Twitter API
XAI_API_KEY=<from console.x.ai>
X_CONSUMER_KEY=<from developer.x.com>
X_CONSUMER_SECRET=<from developer.x.com>
```

### 2. Frontend `.env.local` (Next.js — Vercel)

```bash
cp frontend/.env.example frontend/.env.local
```

Fill in:
```
# Drupal connection
DRUPAL_BASE_URL=http://72.62.80.155
DRUPAL_API_USER=rare
DRUPAL_API_PASS=<drupal-admin-password>

# NextAuth
NEXTAUTH_SECRET=<random-32-char-string>
NEXTAUTH_URL=https://rareimagery.net

# X OAuth (same keys as root .env)
X_CONSUMER_KEY=<from developer.x.com>
X_CONSUMER_SECRET=<from developer.x.com>

# AI
XAI_API_KEY=<from console.x.ai>
ANTHROPIC_API_KEY=<from console.anthropic.com>

# Payments
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Printful
PRINTFUL_API_KEY=<from printful.com/dashboard>
```

## API Keys — Where to Get Them

| Key | Source | Purpose |
|-----|--------|---------|
| `XAI_API_KEY` | console.x.ai | Grok AI for profile enhancement |
| `X_CONSUMER_KEY/SECRET` | developer.x.com | X OAuth login + API v2 |
| `ANTHROPIC_API_KEY` | console.anthropic.com | Claude Haiku page builder |
| `STRIPE_*` | dashboard.stripe.com | Payments + Connect |
| `PRINTFUL_API_KEY` | printful.com/dashboard | Print-on-demand |

## Security Rules

- `.env` and `.env.local` are in `.gitignore` — never commit
- Drupal admin password only in `.env.local`, never in code
- All API keys are server-side only (never exposed to browser)
- Stripe webhook secret validates incoming webhooks

---

# 3. Docker & Local Dev

## Docker Services

`docker-compose.yml` defines 2 services:

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `rare-postgres` | postgres:16-alpine | 5432 | Database |
| `rare-drupal` | Custom (Dockerfile) | 80 | Drupal + Apache |

Network: `marketplace-net` (bridge)

## Start Local Dev

```bash
make up              # Start containers
docker ps            # Verify: rare-drupal, rare-postgres
make install         # Fresh Drupal install (first time only)
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

---

# 4. Drupal Backend

## 3 Custom Modules

| Module | Files | Purpose |
|--------|-------|---------|
| `rareimagery_xstore` | 286 | Core platform — commerce, stores, fulfillment |
| `rareimagery_ai` | 15 | AI admin chat (Claude + Grok) |
| `rareimagery_x_import` | 25 | X/Twitter profile import |

All located in `web/modules/custom/`.

## rareimagery_xstore (Core Module)

### Services (5)
| Service | File | Purpose |
|---------|------|---------|
| StoreManagerService | `src/Service/StoreManagerService.php` | Store CRUD, node creation |
| PrintfulSyncService | `src/Service/PrintfulSyncService.php` | Printful product sync |
| XProfileScraperService | `src/Service/XProfileScraperService.php` | Scrape X profile data |
| CreatorThemeService | `src/Service/CreatorThemeService.php` | Theme field management |
| SubscriptionManagerService | `src/Service/SubscriptionManagerService.php` | Stripe subscriptions |

### Controllers (5)
| Controller | Route | Purpose |
|-----------|-------|---------|
| StorePageController | `/store/[handle]` | Public storefront |
| DashboardAppController | `/dashboard` | Vite SPA mount |
| PrintfulWebhookController | `/api/printful/webhook` | Printful order events |
| StripeSubscriptionWebhookController | `/api/stripe/webhook` | Subscription events |
| InviteCodeController | `/admin/config/rareimagery/invites/*` | Enable/disable invite codes |

### Event Subscribers (3)
| Subscriber | Event | Purpose |
|-----------|-------|---------|
| PlatformFeeSubscriber | ORDER_PRE_SAVE | Add $1.00/$0.05 fees |
| StripeConnectSubscriber | Payment events | Route payouts to creators |
| PrintfulOrderSubscriber | Order events | Trigger POD fulfillment |

### REST Resources (13)
See [API Connection](#8-drupal--nextjs-api-connection) for the full list.

### Config (220 YAML files)
All in `config/install/`. Defines:
- 3 product types + 3 variation types + 3 order types
- Product attributes (size, color, material)
- Content types (x_creator_store, creator_profile_theme)
- 5 taxonomy vocabularies
- Faceted search config
- Stripe payment gateway
- Checkout flow
- Pathauto patterns

## Entity Model

### Content Types (Nodes)

**x_creator_store** — The store profile
| Field | Type | Purpose |
|-------|------|---------|
| field_x_username | text | X handle |
| field_store_theme | list | Theme name (xai3, myspace, etc.) |
| field_bio_description | text_long | Store bio |
| field_follower_count | integer | X followers |
| field_top_posts | text_long | JSON array |
| field_top_followers | text_long | JSON array |
| field_metrics | text_long | JSON object |
| field_profile_picture | image | Profile pic |
| field_background_banner | image | Banner image |
| field_linked_store | entity_reference | → commerce_store |

**creator_profile_theme** — Theme customization
- 40+ fields for colors, fonts, backgrounds, music, social links

### Taxonomies
- product_category, design_style, audience, animal_type, breed

## rareimagery_ai (AI Module)

Admin chat at `/admin/ai/chat` with tool-use:
- `ClaudeClient.php` — Anthropic API
- `XaiClient.php` — xAI/Grok API
- `ToolRegistry.php` — Drupal action tools (647 LOC)
- `AiAgent.php` — Orchestrator

## rareimagery_x_import

One-click X import:
- `XApiService.php` — X API v2 client
- `GrokService.php` — Grok enrichment
- `XProfileImportForm.php` — Admin import UI

## Setup Scripts

23 PHP scripts in `scripts/` for bootstrapping:
```bash
php scripts/setup_drupal_fields.php      # Create all custom fields
php scripts/setup_product_types.php      # Configure product types
php scripts/create_demo_profiles.php     # Create demo creators
php scripts/create_demo_stores.php       # Create demo stores
php scripts/create_demo_products.php     # Create demo products
```

## Admin Access

- Username: `rare` (uid 1, is_admin)
- Drush: `docker exec rare-drupal /opt/drupal/vendor/bin/drush`
- JSON:API write enabled: `drush config:set jsonapi.settings read_only 0`

---

# 5. Commerce & Payments

## Entity Relationships

```
X Creator (signs in via OAuth)
    ↓
creator_x_profile (node) — stores X data, theme, bio
    ↓ field_linked_store
commerce_store (type: creator) — transactional store entity
    ↓
commerce_product (3 types) — items for sale
    ↓
commerce_product_variation (3 types) — size/color/file variants
    ↓
commerce_order (3 types) — split by product type in cart
```

## 3 Product Types

| Type | Bundle | Fulfillment | Use Case |
|------|--------|-------------|----------|
| Physical POD | `physical_pod` | Printful auto-fulfill | T-shirts, mugs, posters |
| Physical Custom | `physical_custom` | Manual shipping | Handmade, signed items |
| Digital Download | `digital_download` | Instant file delivery | Presets, PDFs, art files |

### Variation Types
| Variation | Attributes | Fields |
|-----------|-----------|--------|
| `pod_variation` | size, color | Printful product/variant ID |
| `custom_variation` | size, color, material | — |
| `digital_variation` | — | File attachment |

## 3 Order Types

Orders split automatically via `StoreOrderTypeResolver`:

| Order Type | Triggered By | Fulfillment |
|-----------|-------------|-------------|
| `pod_order` | Cart has pod_variation | PrintfulOrderSubscriber |
| `custom_order` | Cart has custom_variation | Manual |
| `digital_order` | Cart has digital_variation | Instant download |

Mixed carts create **separate orders per type** in a single checkout.

## SKU Convention

```
[STORE]-[TYPE]-[PRODUCT]-[VARIANT]
Example: RAREIMAGERY-POD-001-SM-BLK
```

## Platform Fees

`PlatformFeeSubscriber` adds fees on ORDER_PRE_SAVE:

| Order Type | Fee | Label |
|-----------|-----|-------|
| pod_order | $1.00 | RareImagery Platform Fee |
| custom_order | $1.00 | RareImagery Platform Fee |
| digital_order | $0.05 | RareImagery Platform Fee |

Fees are **locked** (customer can't remove) and **non-taxable**.

## Stripe Connect Payment Flow

```
Customer pays $25.00
    ↓
Platform Stripe account receives $25.00
    ↓
application_fee_amount = $1.00 (platform keeps)
    ↓
transfer_data.destination = creator's Stripe Connect account
    ↓
Creator receives $24.00
```

### Stripe Connect Onboarding
1. Creator clicks "Connect Stripe" in dashboard
2. `/api/stores/stripe-connect/onboarding` creates Connect account
3. Creator completes Stripe onboarding flow
4. Stripe account ID saved to store entity

### Subscription Model (Creator Fees)
- **$100** one-time store launch fee
- **$5/month** recurring maintenance
- 30-day trial before first recurring charge
- Managed via `SubscriptionManagerService`
- Webhook: `StripeSubscriptionWebhookController`

## Stripe Billing — Store Subscriptions

| Event | Action |
|-------|--------|
| `checkout.session.completed` | Activate store, save subscription ID |
| `invoice.paid` | Confirm active status |
| `invoice.payment_failed` | Set `past_due`, unpublish store |
| `customer.subscription.updated` | Sync status |
| `customer.subscription.deleted` | Set `canceled`, unpublish store |

## Printful Integration

### Sync Flow
```
Creator connects Printful → PrintfulSyncService.syncProducts()
    ↓
Fetches products/variants from Printful API
    ↓
Creates/updates commerce_product (physical_pod) + pod_variation entities
    ↓
Stores Printful product_id and variant_id on each variation
```

### Order Flow
```
Customer places pod_order → PrintfulOrderSubscriber fires
    ↓
Sends order to Printful API with shipping address
    ↓
Printful prints and ships
    ↓
PrintfulWebhookController receives status updates
    ↓
Order status updated in Drupal
```

## Cron Safety Net

`hook_cron()` in `rareimagery_xstore.module`:
- Unpublishes stores with `past_due` subscriptions
- Deletes abandoned `pending` stores older than 24 hours (plus linked commerce_store)

---

# 6. Next.js Frontend

## Stack

- Next.js 16.1.6 (App Router)
- React 19.2.3
- TypeScript 5
- Tailwind CSS v4
- NextAuth 4.24.13
- Anthropic SDK (Claude Haiku)
- Stripe SDK

## Directory Structure

```
frontend/src/
├── app/
│   ├── page.tsx                          # Landing page (creator grid)
│   ├── layout.tsx                        # Root layout (Sora, DM Sans, JetBrains Mono)
│   ├── globals.css                       # Tailwind imports
│   ├── login/page.tsx                    # NextAuth login
│   ├── build/page.tsx                    # Store creation entry (242 LOC)
│   ├── stores/[creator]/page.tsx         # Public store page (176 LOC)
│   ├── products/[slug]/page.tsx          # Product detail (465 LOC)
│   ├── console/                          # Admin dashboard
│   │   ├── layout.tsx                    # Sidebar layout + ConsoleContext
│   │   ├── page.tsx                      # Dashboard home
│   │   ├── products/page.tsx             # Product management
│   │   ├── theme/page.tsx                # Theme selector
│   │   ├── printful/page.tsx             # POD management
│   │   ├── settings/page.tsx             # Store settings
│   │   ├── setup/page.tsx                # Store creation wizard
│   │   ├── admin/page.tsx                # All stores (admin only)
│   │   ├── stores/page.tsx               # Redirect → admin
│   │   ├── stores/new/page.tsx           # Create store
│   │   ├── stores/[id]/page.tsx          # Edit store
│   │   └── stores/[id]/theme/page.tsx    # Theme editor (379 LOC)
│   └── api/                              # 17+ API routes
│
├── components/
│   ├── themes/                           # 6 theme implementations
│   │   ├── Xai3Theme.tsx                 # Default (975 LOC)
│   │   ├── XMimicTheme.tsx               # X.com mimic (1,342 LOC)
│   │   ├── MySpaceTheme.tsx              # Y2K nostalgia (1,151 LOC)
│   │   ├── MinimalTheme.tsx              # Clean (509 LOC)
│   │   ├── EditorialTheme.tsx            # Magazine (353 LOC)
│   │   └── NeonTheme.tsx                 # Cyberpunk (331 LOC)
│   ├── ConsoleContext.tsx                # Console state context
│   ├── ConsoleSidebar.tsx                # Sidebar navigation
│   ├── ConsoleUserMenu.tsx               # User dropdown
│   ├── StoreBuilderWizard.tsx            # 5-step creation (636 LOC)
│   ├── BuildPageClient.tsx               # X import orchestrator
│   ├── InviteGate.tsx                    # Invite code gate
│   ├── ThemeSelector.tsx                 # Theme picker (125 LOC)
│   ├── PrintfulManager.tsx               # POD products (368 LOC)
│   ├── ProductManager.tsx                # Product CRUD (192 LOC)
│   ├── ProductTabs.tsx                   # Product display (321 LOC)
│   ├── ProductGallery.tsx                # Image gallery (157 LOC)
│   ├── AddToCartBlock.tsx                # Cart interaction (258 LOC)
│   ├── StoreApprovalButton.tsx           # Approve/reject (77 LOC)
│   ├── NotificationPreferences.tsx       # Alert settings (140 LOC)
│   ├── FloatingBuilder.tsx               # Page builder (178 LOC)
│   ├── LivePreview.tsx                   # Builder preview (72 LOC)
│   ├── BuildLibrary.tsx                  # Saved builds (69 LOC)
│   ├── BuilderGate.tsx                   # Builder auth (22 LOC)
│   ├── Sidebar.tsx                       # Navigation (228 LOC)
│   ├── StoreNav.tsx                      # Store nav bar
│   ├── UpgradeButton.tsx                 # Stripe checkout
│   ├── ProvisionButton.tsx               # Profile provision
│   ├── AuthButton.tsx                    # Login/logout (34 LOC)
│   └── Providers.tsx                     # Session provider
│
├── lib/
│   ├── drupal.ts                         # API client + types (765 LOC)
│   ├── x-import.ts                       # X data fetch (445 LOC)
│   ├── notifications.ts                  # Email/SMS (274 LOC)
│   ├── grok.ts                           # Grok AI (125 LOC)
│   ├── drupalBuilds.ts                   # Build storage (85 LOC)
│   ├── x-subscription.ts                 # Subscription check (75 LOC)
│   ├── mock-products.ts                  # Demo data (48 LOC)
│   ├── slugs.ts                          # URL slugs (31 LOC)
│   └── stripe.ts                         # Stripe helpers (16 LOC)
│
└── middleware.ts                          # Subdomain routing
```

## Key Patterns

### Data Fetching
- Server components fetch directly from Drupal via `drupal.ts`
- Client components call `/api/*` routes (never Drupal directly)
- `drupalAuthHeaders()` adds Basic Auth for all Drupal calls

### Styling
- Tailwind v4 utility classes
- Dark theme: zinc-950 backgrounds, white text
- Fonts: Sora (headings), DM Sans (body), JetBrains Mono (code)

### Static Generation
- Store pages use `generateStaticParams()` for SSG
- Product pages are pre-rendered at build time
- Revalidation: 60s server cache + 300s stale-while-revalidate

### Config Files

| File | Purpose |
|------|---------|
| `next.config.ts` | Image domains (72.62.80.155, *.rareimagery.net, pbs.twimg.com) |
| `vercel.json` | Cache headers |
| `tsconfig.json` | Strict TS, `@/*` path alias |

---

# 7. Authentication & Roles

## Two Auth Systems

| System | Method | Purpose |
|--------|--------|---------|
| **NextAuth** | X (Twitter) OAuth | User sessions in the browser |
| **Drupal Basic Auth** | Username/password | Server-to-server API calls |

## NextAuth Providers

### 1. X (Twitter) OAuth 2.0 — Primary

- **Scopes:** `users.read`, `tweet.read`, `follows.read`, `offline.access`
- **Data fetched at login:** profile_image_url, profile_banner_url, public_metrics, description, verified
- **Token stored in JWT:** xUsername, xId, xImage, xBannerUrl, xAccessToken, xRefreshToken, xTokenExpires
- **Auto-refresh:** X access token refreshed 5 minutes before expiry via refresh_token grant
- **Post-login sync:** `syncXDataToDrupal()` fires (non-blocking) to update the Drupal profile with latest X data

### 2. Credentials — Fallback

- Email/password login against Drupal user accounts via Basic Auth
- Used for admin or store owners who need direct Drupal access
- Returns Drupal user's store info (shopName, storeSlug)

## Role Assignment

Roles are set in the JWT callback:

```
const adminXUsernames = process.env.ADMIN_X_USERNAMES.split(",");
token.role = adminXUsernames.includes(xUsername) ? "admin" : "creator";
```

| Role | Who | Access |
|------|-----|--------|
| `admin` | X accounts listed in `ADMIN_X_USERNAMES` env var | Full console + admin panel + invite bypass |
| `creator` | Any other X login | Console for their own store only |
| `store_owner` | Credentials login | Console for their own store only |

## Session Shape

```typescript
session.user.xUsername    // X handle (no @)
session.user.xId          // X numeric user ID
session.user.xAccessToken // For X API calls
session.user.xBannerUrl   // Profile banner
session.user.role         // "admin" | "creator" | "store_owner"
session.user.shopName     // Store display name
session.user.storeSlug    // Subdomain slug
```

## Drupal Basic Auth (Server-to-Server)

Next.js API routes call Drupal JSON:API using Basic Auth:

```typescript
// frontend/src/lib/drupal.ts
export function drupalAuthHeaders(): Record<string, string> {
  const user = process.env.DRUPAL_API_USER;
  const pass = process.env.DRUPAL_API_PASS;
  const encoded = Buffer.from(`${user}:${pass}`).toString("base64");
  return {
    Authorization: `Basic ${encoded}`,
    "Content-Type": "application/vnd.api+json",
    Accept: "application/vnd.api+json",
  };
}
```

Custom `jsonapi_basic_auth` module at `/opt/drupal/web/modules/custom/jsonapi_basic_auth/` enables this.

## Login Flow

1. User clicks "Sign in with X" on `/login`
2. Redirected to X OAuth consent screen
3. X returns authorization code → NextAuth exchanges for access + refresh tokens
4. JWT callback enriches token with X profile data
5. Admin role check against `ADMIN_X_USERNAMES`
6. `syncXDataToDrupal()` fires in background to update Drupal profile
7. User redirected to `/console`

## Environment Variables

| Variable | Purpose |
|----------|---------|
| `X_CLIENT_ID` | X OAuth 2.0 Client ID |
| `X_CLIENT_SECRET` | X OAuth 2.0 Client Secret |
| `NEXTAUTH_SECRET` | JWT signing secret |
| `NEXTAUTH_URL` | Base URL (`https://rareimagery.net`) |
| `ADMIN_X_USERNAMES` | Comma-separated admin X handles |

## X Developer Portal Requirements

Callback URL must be set to: `https://rareimagery.net/api/auth/callback/twitter`

OAuth 2.0 must be enabled with PKCE and the scopes listed above.

---

# 8. Drupal ↔ Next.js API Connection

## Data Flow

```
Browser (React) → Next.js API Route → Drupal JSON:API → PostgreSQL
                                    → Stripe API
                                    → Printful API
                                    → xAI/Grok API
```

**Rule:** The browser never calls Drupal directly. All requests go through Next.js API routes which add auth headers and transform data.

## Drupal Client (`frontend/src/lib/drupal.ts`)

The core API client (765 LOC) provides:

### Auth Helper
```typescript
drupalAuthHeaders()  // Returns Basic Auth + JSON:API headers
```

### Data Fetching Functions
| Function | Returns | Endpoint |
|----------|---------|----------|
| `getAllCreatorProfiles()` | `CreatorProfile[]` | `/jsonapi/node/creator_x_profile` |
| `getCreatorProfile(username)` | `CreatorProfile` | `/jsonapi/node/creator_x_profile?filter[field_x_username]=...` |
| `getProductsByStore(storeId)` | `Product[]` | `/jsonapi/commerce_product?filter[store]=...` |
| `getProductBySlug(slug)` | `ProductDetail` | `/jsonapi/commerce_product?filter[path]=...` |

### Type Definitions
```typescript
interface CreatorProfile {
  id: string;
  x_username: string;
  bio: string;
  follower_count: number;
  profile_picture_url: string;
  background_banner_url: string;
  store_theme: string;
  top_posts: any[];
  top_followers: any[];
  metrics: any;
  linked_store_id: string;
}
```

## Drupal REST Resources (Backend)

13 custom REST resources in `rareimagery_xstore`:

| Resource | Plugin ID | Purpose |
|----------|----------|---------|
| StoreCreateResource | `store_create` | POST store creation |
| StoreListResource | `store_list` | GET all stores |
| StoreProfileResource | `store_profile` | GET single store |
| CurrentUserStoreResource | `current_user_store` | GET logged-in user's store |
| XProfilePreviewResource | `x_profile_preview` | GET preview |
| DashboardAnalyticsResource | `dashboard_analytics` | GET revenue/orders |
| CheckoutResource | `checkout` | POST order |
| ShippingRatesResource | `shipping_rates` | POST rate calculation |
| PrintfulSyncTriggerResource | `printful_sync_trigger` | POST sync |
| StripeConnectOnboardingResource | `stripe_connect_onboarding` | POST seller setup |
| SubscriptionCheckoutResource | `subscription_checkout` | POST subscription |
| SubscriptionPortalResource | `subscription_portal` | POST billing portal |
| SubscriptionStatusResource | `subscription_status` | GET subscription |

## JSON:API Patterns

### Read (GET)
```typescript
const res = await fetch(
  `${DRUPAL_BASE_URL}/jsonapi/node/creator_x_profile?filter[field_x_username]=${username}&include=field_profile_picture`,
  { headers: drupalAuthHeaders() }
);
```

### Create (POST)
```typescript
const res = await fetch(`${DRUPAL_BASE_URL}/jsonapi/node/creator_x_profile`, {
  method: "POST",
  headers: drupalAuthHeaders(),
  body: JSON.stringify({
    data: {
      type: "node--creator_x_profile",
      attributes: { title: storeName, field_x_username: handle },
    },
  }),
});
```

### Update (PATCH)
```typescript
const res = await fetch(`${DRUPAL_BASE_URL}/jsonapi/node/creator_x_profile/${uuid}`, {
  method: "PATCH",
  headers: drupalAuthHeaders(),
  body: JSON.stringify({
    data: {
      type: "node--creator_x_profile",
      id: uuid,
      attributes: { field_store_theme: "myspace" },
    },
  }),
});
```

---

# 9. xAI & Grok Import

## What It Does

When a creator signs in with X, the system:
1. Fetches their X profile (avatar, banner, bio, followers, posts)
2. Sends that data to Grok AI for enhancement
3. Auto-fills the store creation wizard with AI-generated content

## Data Flow

```
X OAuth Login → NextAuth session (xAccessToken, xId)
       ↓
/api/stores/enhance-profile (POST)
       ↓
fetchXData(xAccessToken, xId)  →  X API v2
       ↓
enhanceCreatorProfile(xData)   →  Grok API (grok-3)
       ↓
Returns: { xData, grokEnhancements }
       ↓
StoreBuilderWizard auto-fills all fields
       ↓
/api/stores/create → Drupal (saves creator_x_profile node)
```

## X Data Fetch (`frontend/src/lib/x-import.ts`, 445 LOC)

### `fetchXData(accessToken, userId)`

Calls X API v2 to get:
```typescript
interface XImportData {
  username: string;
  displayName: string;
  bio: string;
  followerCount: number;
  followingCount: number;
  profileImageUrl: string;
  bannerUrl: string;
  topPosts: XPost[];
  topFollowers: XFollower[];
  metrics: {
    followers: number;
    following: number;
    totalPosts: number;
    avgLikes: number;
    postingFrequency: string;
  };
}
```

### `syncXDataToDrupal(xData, drupalProfileId)`

Writes X data to Drupal JSON:API:
- Uploads profile picture → `field_profile_picture`
- Uploads banner → `field_background_banner`
- PATCHes all text fields (bio, followers, posts, metrics)

## Grok AI Enhancement (`frontend/src/lib/grok.ts`, 125 LOC)

### `enhanceCreatorProfile(xData)`

Sends X data to Grok with a structured prompt. Returns:

```typescript
interface GrokEnhancements {
  storeBio: string;
  suggestedProducts: Array<{
    name: string;
    description: string;
    category: string;
  }>;
  recommendedTheme: string;
  topThemes: string[];
  audienceSentiment: string;
}
```

API Config: `https://api.x.ai/v1/chat/completions`, model `grok-3`, temperature 0.7, JSON mode enabled.

## Drupal Fields Updated

| Field | Source | Data |
|-------|--------|------|
| field_x_username | X API | Handle |
| field_bio_description | Grok or X API | AI bio or raw bio |
| field_follower_count | X API | Integer |
| field_profile_picture | X API → Drupal file | Uploaded image |
| field_background_banner | X API → Drupal file | Uploaded image |
| field_top_posts | X API | JSON array |
| field_top_followers | X API | JSON array |
| field_metrics | X API + Grok | JSON (includes AI analysis) |

---

# 10. Store Creation Flow

## End-to-End Flow

```
Visitor lands on /build
    ↓
"Sign in with X" → X OAuth → NextAuth session
    ↓
BuildPageClient auto-calls /api/stores/enhance-profile
    ↓
X data + Grok enhancements returned
    ↓
StoreBuilderWizard (5 steps):
    Step 1: Store Info (name, subdomain, email, currency)
    Step 2: Creator Profile (bio, images, posts — auto-filled)
       → POST /api/stores/create → Drupal creates store + profile
    Step 3: Choose Theme (Grok-recommended default)
       → POST /api/stores/select-theme
    Step 4: Add Products (optional)
    Step 5: "Store Submitted for Review"
       → Email sent to admin
    ↓
Admin visits /console/admin → sees pending store
    ↓
Clicks Approve → POST /api/stores/approve
    ↓
Email sent to creator → store goes live at {slug}.rareimagery.net
```

## Key Components

### Entry Point: `/build` page
File: `frontend/src/app/build/page.tsx` (242 LOC)
- **Not signed in:** Marketing landing with "Sign in with X" CTA
- **Signed in as creator:** Shows `BuildPageClient` → wizard
- **Signed in as store_owner:** Redirects to `/console`

### Orchestrator: `BuildPageClient`
File: `frontend/src/components/BuildPageClient.tsx` (182 LOC)
1. Calls `/api/stores/enhance-profile` on mount
2. Shows loading state while X data + Grok run
3. Passes results to `StoreBuilderWizard`

### Wizard: `StoreBuilderWizard`
File: `frontend/src/components/StoreBuilderWizard.tsx` (636 LOC)

**Step 1 — Store Info:** Store name, subdomain, contact email, currency
**Step 2 — Creator Profile:** X data auto-filled, Grok product suggestions
**Step 3 — Choose Theme:** ThemeSelector with Grok-recommended default
**Step 4 — Add Products:** ProductManager (optional)
**Step 5 — Submitted:** Confirmation with subdomain preview

## Admin Approval

### Approval: `StoreApprovalButton`
File: `frontend/src/components/StoreApprovalButton.tsx` (77 LOC)
- Approve → sets status, sends email to creator
- Reject → sets status with reason, sends rejection email

### Notifications
File: `frontend/src/lib/notifications.ts` (274 LOC)
- Uses Brevo SMTP for email, Telnyx for SMS
- Notification types: store submitted, approved, rejected, new sale

## Store Statuses

| Status | Meaning | Visible at |
|--------|---------|-----------|
| `pending` | Submitted, awaiting review | /console (admin) |
| `approved` | Live on subdomain | Public storefront |
| `rejected` | Denied with reason | /console (creator notified) |

## Monetization

| Fee | Amount | When |
|-----|--------|------|
| Store launch | $100 one-time | On approval |
| Maintenance | $5/month | Recurring via Stripe |
| Physical order | $1.00 | Per order (automatic) |
| Digital order | $0.05 | Per order (automatic) |

---

# 11. Themes & Page Builder

## 6 Store Themes

Each theme is a React component that renders the entire store page with the creator's data.

| Theme | Component | Lines | Aesthetic |
|-------|-----------|-------|-----------|
| **xai3** | `Xai3Theme.tsx` | 975 | Modern 3-column grid, dark, default |
| **xmimic** | `XMimicTheme.tsx` | 1,342 | X.com single-column feed mimic |
| **myspace** | `MySpaceTheme.tsx` | 1,151 | Y2K nostalgia, glitter, music player |
| **minimal** | `MinimalTheme.tsx` | 509 | Clean, simple, light accents |
| **editorial** | `EditorialTheme.tsx` | 353 | Magazine-style layout |
| **neon** | `NeonTheme.tsx` | 331 | Cyberpunk glow, neon borders |

All in `frontend/src/components/themes/`.

### Storage
Theme name stored as `field_store_theme` on `creator_x_profile` nodes in Drupal.

### Rendering
```
/stores/[creator]/page.tsx
    ↓
getCreatorProfile(creator) → gets field_store_theme
    ↓
Switch on theme value → renders matching theme component
```

### Theme Props
Every theme receives: `profile` (CreatorProfile), `products` (Product[]), `topPosts`, `topFollowers`

## Theme Selector
File: `frontend/src/components/ThemeSelector.tsx` (125 LOC)
- 6 theme cards with previews
- Calls `/api/stores/select-theme` on selection
- Used in store creation wizard and console theme editor

## MySpace Theme Special Features
- Accent color, glitter color, background image, music URL
- Full theme editor at `/console/stores/[id]/theme/page.tsx` (379 LOC)
- Preset themes: Y2K Pink, Dark Emo, Neon Cyber, Scene Gold
- Effects: glitter text, cursor trails, marquee, mood, visitor counter

## Tailwind Page Builder

A floating AI chatbot that generates Tailwind CSS components.

### Architecture
```
FloatingBuilder.tsx → /api/chat → Claude Haiku 4.5 → generated code
                   → /api/builds → Drupal (save/load builds)
```

### Components

| File | Lines | Purpose |
|------|-------|---------|
| `FloatingBuilder.tsx` | 178 | Draggable panel, 3 tabs |
| `LivePreview.tsx` | 72 | Sandboxed iframe with Babel + Tailwind CDN |
| `BuildLibrary.tsx` | 69 | List/load/delete saved builds |
| `BuilderGate.tsx` | 22 | Only shows builder if user owns the store |

### 3 Tabs
1. **Generate** — Text prompt → Claude Haiku → code output + copy button
2. **Preview** — Sandboxed iframe renders the generated component live
3. **Saved Builds** — Load or delete previously saved builds

Store owners see a purple "Page Builder" button in the bottom-right corner of their storefront.

---

# 12. Unified Console Dashboard

The console is the single entry point for creators and admins after login. Sidebar-based layout where everything lives under `/console/*`.

## Route Map

| Route | Role | Purpose |
|-------|------|---------|
| `/console` | All | My Store dashboard — overview card, quick actions |
| `/console/products` | Creator/Admin | Product CRUD via `ProductManager` |
| `/console/theme` | Creator/Admin | Theme selector (6 themes) |
| `/console/printful` | Creator/Admin | Printful POD connection & sync |
| `/console/settings` | Creator/Admin | Store details, notifications, X profile |
| `/console/setup` | Creator/Admin | Inline store creation wizard |
| `/console/admin` | Admin only | All-stores table with approvals |

## Key Files

### Layout & Context

- **`src/app/console/layout.tsx`** — Server component. Fetches session + user's store data from Drupal. Wraps children in `<ConsoleContextProvider>`. Renders `<ConsoleSidebar>` + content area.

- **`src/components/ConsoleContext.tsx`** — React context:
  ```
  role, xUsername, hasStore, storeId, storeDrupalId,
  profileNodeId, storeName, storeSlug, storeStatus, currentTheme
  ```

- **`src/components/ConsoleSidebar.tsx`** — Vertical sidebar (w-64). Nav links, admin section, active link highlighting, user menu.

### Dashboard Page

- **`src/app/console/page.tsx`** — If `hasStore`: store overview card. If `!hasStore`: CTA to `/console/setup`. If admin: "Platform Admin" card.

### Tab Pages (thin wrappers)

- **`products/page.tsx`** → `<ProductManager>`
- **`theme/page.tsx`** → `<ThemeSelector>`
- **`printful/page.tsx`** → `<PrintfulManager>`
- **`settings/page.tsx`** → Store details + `<NotificationPreferences>`

### Admin & Setup

- **`admin/page.tsx`** — All-stores table, admin-only role check, `<StoreApprovalButton>` per store
- **`setup/page.tsx`** — `<BuildPageClient>` inside console. Admins bypass `<InviteGate>`.

## Layout Wireframe

```
┌──────────────────┬─────────────────────────────────────────────┐
│  RareImagery     │                                             │
│                  │  My Store Dashboard                         │
│  ○ My Store      │  ┌─────────────────────────────────────┐   │
│  ○ Products      │  │ Store Name    [Open Live Store →]    │   │
│  ○ Theme         │  │ slug.rareimagery.net                 │   │
│  ○ Printful      │  │ Status: ● Approved    Theme: xai3   │   │
│  ○ Settings      │  └─────────────────────────────────────┘   │
│  ─────────────── │                                             │
│  Platform Admin  │  Quick Actions                              │
│  ○ All Stores    │  [+ Add Product] [Change Theme] [Settings]  │
│                  │                                             │
│  ─────────────── │  Platform (admin only)                      │
│  @rareimagery    │  3 stores pending approval → View           │
│  [Sign Out]      │                                             │
└──────────────────┴─────────────────────────────────────────────┘
```

## Data Flow

1. User logs in via X OAuth → NextAuth sets session
2. `console/layout.tsx` queries Drupal for user's store data
3. Passes to `<ConsoleContextProvider>`
4. All child pages read from context — zero additional API calls for basic info

---

# 13. Invite Code System

The platform is invite-only for new creators. Only the admin can generate invite codes, managed entirely through Drupal.

## How It Works

1. Admin generates codes via Drupal at `/admin/config/rareimagery/invites`
2. Admin shares a code (e.g. `RARE-RJ5FSN`)
3. Friend signs in with X, lands on `/console/setup`
4. `<InviteGate>` blocks the store creation form
5. Friend enters code → frontend calls `/api/invite/verify`
6. API checks Drupal for matching published `invite_code` node with remaining uses
7. On success: increments `field_current_uses`, stores verification in localStorage
8. Friend proceeds to create their store

## Drupal Side

### Content Type: `invite_code`

| Field | Type | Purpose |
|-------|------|---------|
| `title` | string | Node title (same as code) |
| `field_invite_code` | string | The actual code |
| `field_max_uses` | integer | Maximum allowed uses |
| `field_current_uses` | integer | How many times used |
| `status` | boolean | Published = active |

### Admin Form: `InviteCodeForm`

**Route:** `/admin/config/rareimagery/invites`
- Generate section: prefix (default "RARE"), max uses (1-100), optional note
- Code format: `{PREFIX}-{6 random alphanumeric chars}`
- Existing codes table with enable/disable actions

### Routes

```yaml
rareimagery_xstore.invite_codes:     /admin/config/rareimagery/invites
rareimagery_xstore.invite_disable:   /admin/config/rareimagery/invites/disable/{nid}
rareimagery_xstore.invite_enable:    /admin/config/rareimagery/invites/enable/{nid}
```

## Frontend Side

### InviteGate Component (`src/components/InviteGate.tsx`)
- Wraps store creation content
- Calls `/api/invite/verify` on submit
- Stores `inviteVerified=true` in localStorage
- Admins bypass entirely

### Verify API Route (`src/app/api/invite/verify/route.ts`)
- POST `{ code: string }`
- Queries Drupal JSON:API for matching published invite_code node
- Checks `field_current_uses < field_max_uses`
- Increments usage on success (fire-and-forget PATCH)

## Existing Codes

5 codes on production (all max_uses=1):
`RARE-RJ5FSN`, `RARE-YXHLHN`, `RARE-9GDPRN`, `RARE-TGBNR3`, `RARE-3RR2UE`

---

# 14. Next.js API Routes

All API routes live under `src/app/api/` and run server-side on Vercel.

## Authentication

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/auth/[...nextauth]` | GET/POST | NextAuth handler — X OAuth 2.0 + credentials login |

## Store Management

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/stores/provision` | POST | Create creator profile node in Drupal |
| `/api/stores/create` | POST | Create store + linked X profile |
| `/api/stores/select-theme` | PATCH | Update theme selection |
| `/api/stores/theme` | PATCH | Save MySpace theme customization |
| `/api/stores/products` | GET/POST/DELETE | Product CRUD |
| `/api/stores/enhance-profile` | POST | X data + Grok AI analysis |
| `/api/stores/import-x-data` | POST | Full X data sync to Drupal |
| `/api/stores/approve` | PATCH | Admin approve/reject store |

## Payments

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/checkout` | POST | Create Stripe checkout session |
| `/api/webhooks/stripe` | POST | Handle Stripe events |

## Page Builder

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/builds` | GET/POST/DELETE | Page build CRUD (max 20 per store) |
| `/api/chat` | POST | Claude Haiku generates Tailwind components |

## Printful

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/printful/connect` | POST | Connect Printful account |
| `/api/printful/products` | GET | Fetch Printful products |
| `/api/printful/sync` | POST | Sync products to Commerce |

## Other

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/invite/verify` | POST | Verify invite code against Drupal |
| `/api/proxy/x-feed/[userId]` | GET | Server-side X posts proxy (5-min cache) |
| `/api/notifications/preferences` | GET/PATCH | Notification settings |
| `/api/app-config/[slug]` | GET | Per-store mobile app config |
| `/.well-known/apple-app-site-association` | GET | iOS Universal Links |
| `/.well-known/assetlinks.json` | GET | Android App Links |

## Drupal Communication Pattern

All routes use `drupalAuthHeaders()` from `src/lib/drupal.ts` (Basic Auth with `DRUPAL_API_USER` + `DRUPAL_API_PASS`).

---

# 15. Frontend Components

All components live in `src/components/`.

## Console

| Component | File | Purpose |
|-----------|------|---------|
| `ConsoleContext` | `ConsoleContext.tsx` | React context — role, store info, theme |
| `ConsoleSidebar` | `ConsoleSidebar.tsx` | Sidebar nav with active links, admin section |
| `ConsoleUserMenu` | `ConsoleUserMenu.tsx` | User avatar + sign out dropdown |

## Store Creation

| Component | File | Purpose |
|-----------|------|---------|
| `BuildPageClient` | `BuildPageClient.tsx` | X import + Grok AI orchestrator |
| `StoreBuilderWizard` | `StoreBuilderWizard.tsx` | Multi-step wizard |
| `InviteGate` | `InviteGate.tsx` | Invite code verification gate |
| `ProvisionButton` | `ProvisionButton.tsx` | One-click profile provision |

## Products

| Component | File | Purpose |
|-----------|------|---------|
| `ProductManager` | `ProductManager.tsx` | CRUD UI for products |
| `ProductGallery` | `ProductGallery.tsx` | Image gallery with thumbnails |
| `ProductTabs` | `ProductTabs.tsx` | Description, specs, delivery tabs |
| `AddToCartBlock` | `AddToCartBlock.tsx` | Add-to-cart with variant selection |

## Themes

| Component | File | Style |
|-----------|------|-------|
| `ThemeSelector` | `ThemeSelector.tsx` | 6-theme visual picker |
| `Xai3Theme` | `themes/Xai3Theme.tsx` | Default — X-feed center column |
| `MinimalTheme` | `themes/MinimalTheme.tsx` | Clean white e-commerce |
| `NeonTheme` | `themes/NeonTheme.tsx` | Dark with neon accents |
| `EditorialTheme` | `themes/EditorialTheme.tsx` | Magazine-style layout |
| `MySpaceTheme` | `themes/MySpaceTheme.tsx` | Retro MySpace with music/effects |
| `XMimicTheme` | `themes/XMimicTheme.tsx` | X-like interface with sidebar |

## Management

| Component | File | Purpose |
|-----------|------|---------|
| `PrintfulManager` | `PrintfulManager.tsx` | Printful connection + sync |
| `NotificationPreferences` | `NotificationPreferences.tsx` | Email/SMS toggles |
| `StoreApprovalButton` | `StoreApprovalButton.tsx` | Admin approve/reject |
| `UpgradeButton` | `UpgradeButton.tsx` | Stripe checkout |

## Page Builder

| Component | File | Purpose |
|-----------|------|---------|
| `FloatingBuilder` | `FloatingBuilder.tsx` | Floating toolbar |
| `BuilderGate` | `BuilderGate.tsx` | Auth gate |
| `BuildLibrary` | `BuildLibrary.tsx` | Saved builds list |
| `LivePreview` | `LivePreview.tsx` | Sandboxed preview |

## Navigation & Auth

| Component | File | Purpose |
|-----------|------|---------|
| `StoreNav` | `StoreNav.tsx` | Store page nav bar |
| `Sidebar` | `Sidebar.tsx` | XMimic theme sidebar |
| `AuthButton` | `AuthButton.tsx` | Sign in/out button |
| `Providers` | `Providers.tsx` | NextAuth SessionProvider |

---

# 16. Drupal Custom Modules

Three custom modules at `web/modules/custom/`.

## 1. rareimagery_xstore — Core Platform

### Controllers

| Controller | Route | Purpose |
|------------|-------|---------|
| `StorePageController` | `/store/{x_handle}` | Public storefront |
| `DashboardAppController` | `/dashboard` | Creator dashboard SPA |
| `PrintfulWebhookController` | `/api/printful/webhook` | Printful webhooks |
| `StripeSubscriptionWebhookController` | `/api/stripe/subscription-webhook` | Stripe webhooks |
| `InviteCodeController` | `/admin/config/rareimagery/invites/*` | Invite enable/disable |

### Services

| Service | Purpose |
|---------|---------|
| `StoreManagerService` | Store CRUD, ownership checks |
| `CreatorThemeService` | Theme config (~40 fields) |
| `PrintfulSyncService` | Sync products, submit orders, shipping rates |
| `SubscriptionManagerService` | Stripe Billing lifecycle |
| `XProfileScraperService` | Scrape X profiles via fxtwitter |

### Event Subscribers

| Subscriber | Event | Purpose |
|------------|-------|---------|
| `PlatformFeeSubscriber` | Order presave | Platform fees ($1.00/$0.05) |
| `PrintfulOrderSubscriber` | Order placed | Auto-submit POD to Printful |
| `StripeConnectSubscriber` | Payment intent | Split payment to creator |

### Commerce Resolvers

| Resolver | Purpose |
|----------|---------|
| `CreatorStoreResolver` | Resolve commerce_store from X handle |
| `StoreOrderTypeResolver` | Route items to pod/custom/digital orders |

### Admin Forms

| Form | Route | Purpose |
|------|-------|---------|
| `PrintfulSettingsForm` | `/admin/config/rareimagery/printful` | Printful API config |
| `XaiSettingsForm` | `/admin/config/rareimagery/xai` | Grok model + toggles |
| `InviteCodeForm` | `/admin/config/rareimagery/invites` | Generate/manage invite codes |

### REST Resources (13)

| Resource | Method | Path |
|----------|--------|------|
| `StoreCreateResource` | POST | `/api/store/create` |
| `StoreProfileResource` | GET | `/api/store/{handle}/profile` |
| `StoreListResource` | GET | `/api/store/list` |
| `CurrentUserStoreResource` | GET | `/api/dashboard/my-stores` |
| `XProfilePreviewResource` | GET | `/api/store/preview/{handle}` |
| `DashboardAnalyticsResource` | GET | `/api/dashboard/analytics` |
| `CheckoutResource` | POST | `/api/checkout` |
| `ShippingRatesResource` | POST | `/api/shipping-rates` |
| `PrintfulSyncTriggerResource` | POST | `/api/printful/sync` |
| `StripeConnectOnboardingResource` | POST | `/api/stripe-onboarding` |
| `SubscriptionCheckoutResource` | POST | `/api/subscription/checkout` |
| `SubscriptionPortalResource` | POST | `/api/subscription/portal` |
| `SubscriptionStatusResource` | GET | `/api/subscription/status` |

### Module Hooks

- **`hook_theme()`** — 3 render templates: store_page, storefront_app, dashboard_app
- **`hook_ENTITY_TYPE_insert()`** — Auto-creates linked commerce_store on x_creator_store creation
- **`hook_cron()`** — Unpublishes past_due stores; deletes abandoned pending stores (24h)

## 2. rareimagery_ai — AI Admin Chat

AI-powered admin with tool-use (Claude + Grok).

### Services

| Service | Purpose |
|---------|---------|
| `AiAgent` | Routes chat to Claude or Grok |
| `ClaudeClient` | Anthropic API with tool-use (10 iterations) |
| `XaiClient` | xAI/Grok in OpenAI function-calling format |
| `ToolRegistry` | 18 tools for Drupal admin operations |

### Routes

| Route | Purpose |
|-------|---------|
| `/admin/config/rareimagery/ai` | AI settings |
| `/admin/ai` | AI Admin chat |
| `/api/ai/chat` | Chat API |
| `/api/ai/tool` | Direct tool execution |

### 18 AI Tools

`list_content`, `get_content`, `create_content`, `update_content`, `delete_content`, `list_users`, `manage_user`, `list_commerce_products`, `list_commerce_orders`, `get_config`, `set_config`, `clear_cache`, `site_status`, `recent_logs`, `sql_query`, `manage_module`

## 3. rareimagery_x_import — X Profile Import

### Services

| Service | Purpose |
|---------|---------|
| `XApiService` | X API v2 — profiles, tweets, followers |
| `GrokService` | Engagement score, themes, product recommendations |

### Forms

| Form | Route | Purpose |
|------|-------|---------|
| `XProfileImportForm` | `/admin/rareimagery/x-import` | Import X profile |
| `XImportSettingsForm` | `/admin/config/rareimagery/x-import` | API keys |

### Import Workflow

1. Enter X username → fetch profile + 20 tweets + 8 followers
2. Optional Grok analysis → engagement score, themes, suggestions
3. Download images → create `creator_x_profile` node → link to commerce_store

---

# 17. Subdomain Routing & Store Pages

Each creator gets a subdomain: `{slug}.rareimagery.net`

## Middleware (`src/middleware.ts`)

1. Extract hostname from request
2. Skip: localhost, IPs, reserved subdomains (www, api, admin, console, app, mail, support, help, blog, login)
3. If subdomain found: rewrite to `/stores/{subdomain}` internally
4. Browser URL stays as `{slug}.rareimagery.net`

Matcher excludes: `_next/static`, `_next/image`, `favicon.ico`, `api/`, `.well-known/`

## Store Page (`src/app/stores/[creator]/page.tsx`)

Server component:
1. Fetch creator profile from Drupal
2. Fetch store products
3. Check status — "Coming Soon" if not approved
4. Render theme component based on `field_store_theme`

## Theme Rendering

| Theme | Component | Description |
|-------|-----------|-------------|
| `xai3` | `Xai3Theme` | Default — X-feed center column |
| `minimal` | `MinimalTheme` | Clean white e-commerce |
| `neon` | `NeonTheme` | Dark with neon glow |
| `editorial` | `EditorialTheme` | Magazine-style |
| `myspace` | `MySpaceTheme` | Retro with music/glitter |
| `xmimic` | `XMimicTheme` | X-like with sidebar |

## DNS

- `*.rareimagery.net` → Vercel (wildcard)
- Middleware handles per-request rewrite

## Product Detail Pages (`/products/[slug]`)

Image gallery, variant selection, add to cart, product tabs, related products, JSON-LD SEO, trust badges.

## Mobile App Support

- `/.well-known/apple-app-site-association` — iOS Universal Links per subdomain
- `/.well-known/assetlinks.json` — Android App Links
- `/api/app-config/[slug]` — Full store config JSON for mobile apps

---

# 18. Library Files & Utilities

All in `src/lib/`.

## drupal.ts — Core Drupal Integration (~765 lines)

| Function | Returns | Used By |
|----------|---------|---------|
| `drupalAuthHeaders()` | Basic Auth headers | All API routes |
| `getCreatorProfile(username)` | Single profile | Store pages |
| `getAllCreatorProfiles()` | All creators | Homepage |
| `getCreatorStoreBySlug(slug)` | Store by slug | Console layout |
| `getStoreProducts(storeId)` | Products | Store pages |
| `getProductBySlug(slug)` | Product detail | Product pages |
| `getAllProductSlugs()` | All slugs | Static generation |
| `getRelatedProducts(product)` | Related items | Product detail |
| `fetchCreatorData(handle)` | Profile + products | Convenience |

Types: `CreatorProfile`, `ProductDetail`, `Product`, `TopPost`, `TopFollower`, `Metrics`

## x-import.ts — X Data Sync (~445 lines)

| Function | Purpose |
|----------|---------|
| `fetchXData(accessToken, userId)` | Fetch profile, tweets, followers from X API v2 |
| `findProfileByUsername(username)` | Look up Drupal profile |
| `patchProfile(uuid, attributes)` | PATCH Drupal node |
| `uploadImageToDrupal(...)` | Download + upload image |
| `syncXDataToDrupal(...)` | Fire-and-forget sync on login |

## grok.ts — Grok AI (~125 lines)

`enhanceCreatorProfile(xData)` → Returns suggested bio, product ideas, recommended theme.

## notifications.ts — Email & SMS (~275 lines)

| Function | Purpose |
|----------|---------|
| `sendEmail(opts)` | Brevo SMTP |
| `sendSMS(to, message)` | Telnyx |
| `notifyAdminNewStore()` | New store alert |
| `notifyStoreApproved()` | Approval notification |
| `notifyStoreRejected()` | Rejection notification |
| `notifyNewSale()` | Sale notification |

## Other Files

| File | Purpose |
|------|---------|
| `drupalBuilds.ts` | Page builder CRUD |
| `x-subscription.ts` | Check if user follows @rareimagery |
| `stripe.ts` | Lazy-loaded Stripe client |
| `slugs.ts` | Slug validation |
| `mock-products.ts` | Dev fallback data |

---

# 19. Infrastructure & Deployment

## Architecture

```
┌─────────────────────────────────┐
│         Vercel (Frontend)       │
│  Next.js 16 — App Router       │
│  *.rareimagery.net              │
└──────────┬──────────────────────┘
           │ JSON:API + REST
           ▼
┌─────────────────────────────────┐
│     VPS 72.62.80.155            │
│  ┌────────────────────────┐     │
│  │  rare-drupal (port 80) │     │
│  │  Drupal 10.3 + PHP 8.3 │     │
│  │  Apache                 │     │
│  └──────────┬─────────────┘     │
│             │                    │
│  ┌──────────▼─────────────┐     │
│  │  rare-postgres (5432)  │     │
│  │  PostgreSQL 16          │     │
│  └────────────────────────┘     │
└─────────────────────────────────┘
```

## Hosting

| Component | Host | URL |
|-----------|------|-----|
| Frontend | Vercel | `https://rareimagery.net`, `https://*.rareimagery.net` |
| Backend | Hostinger VPS | `http://72.62.80.155` |
| Database | Same VPS (Docker) | Port 5432 (internal) |

## Environment Variables

### Frontend (Vercel)

| Variable | Purpose |
|----------|---------|
| `DRUPAL_API_URL` | Drupal base URL |
| `DRUPAL_API_USER` / `DRUPAL_API_PASS` | Basic Auth |
| `X_CLIENT_ID` / `X_CLIENT_SECRET` | X OAuth |
| `NEXTAUTH_SECRET` / `NEXTAUTH_URL` | JWT auth |
| `ADMIN_X_USERNAMES` | Admin X handles |
| `NEXT_PUBLIC_BASE_DOMAIN` | `rareimagery.net` |
| `STRIPE_SECRET_KEY` | Payments |
| `SMTP_HOST` / `SMTP_USER` / `SMTP_PASS` | Brevo email |
| `TELNYX_API_KEY` | SMS |

### Backend (Docker)

| Variable | Purpose |
|----------|---------|
| `POSTGRES_DB` / `POSTGRES_USER` / `POSTGRES_PASSWORD` | Database |
| `XAI_API_KEY` | Grok AI |
| `X_CONSUMER_KEY` / `X_CONSUMER_SECRET` | X API |

## PHP Configuration (`docker/php/drupal.ini`)

Memory: 256M, Upload: 64M, Max execution: 300s, OPcache + APCu enabled

## Deployment

- **Frontend:** Vercel auto-deploys on push to main
- **Backend:** Manual via SSH + `docker exec` commands
- **No CI/CD pipeline**

### Deploy Backend Changes
```bash
ssh root@72.62.80.155
cd /var/www/rareimagery-marketplace
git pull origin main
docker exec rare-drupal /opt/drupal/vendor/bin/drush cache:rebuild
```

## DNS

- `rareimagery.net` → Vercel
- `*.rareimagery.net` → Vercel (wildcard for creator subdomains)
- Reserved: www, api, admin, console, app, mail, support, help, blog, login

## Database Backup

```bash
ssh root@72.62.80.155
docker exec rare-postgres pg_dump -U rare_user rare_drupal > backup_$(date +%Y%m%d).sql
```

## Common Issues

| Problem | Fix |
|---------|-----|
| Vercel 404 | Check domain assignment, root directory = `frontend` |
| Drupal 403 | Verify `jsonapi_basic_auth` enabled, check credentials |
| No creators on homepage | VPS down or port 80 blocked |
| Images not loading | Whitelist domain in `next.config.ts` |
| Local DB empty | Normal — all data lives on remote VPS |
| `drush config:set ... false` | Use `0` instead of `false` for booleans |

---

# 20. Quick Start

```bash
# 1. Clone
git clone https://github.com/rareimagery/rare.git && cd rare

# 2. Environment
cp .env.example .env           # Fill in secrets
cp frontend/.env.example frontend/.env.local

# 3. Backend
make up && make install

# 4. Frontend
cd frontend && npm install && npm run dev
```

## Agents

5 specialized agents in `.claude/agents/`:

```
.claude/agents/
├── nextjs.md                  # Frontend: themes, components, pages
├── drupal-nextjs-connection.md # API integration layer
├── drupal.md                  # Backend: modules, entities, config
├── xai-import.md              # X/Twitter data import via Grok
└── store-creation.md          # Store creation wizard flow
```
