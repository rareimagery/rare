# RareImagery — Project Context & Architecture

> **Purpose:** Single source of truth for architecture decisions, technical specs, and working conventions. Upload as Project Knowledge on platform.claude.com for full context in every conversation.
>
> **Last updated:** March 16, 2026

---

## 1. What Is RareImagery?

RareImagery (rareimagery.net) is a creator platform targeting **X (formerly Twitter) creators**. It provides a storefront and personal site layer built *on top of* X — complementing it, never competing with it.

Every creator gets a branded subdomain (`creatorname.rareimagery.net`) with a site builder. Commerce is an **optional add-on**, not the default entry point.

### Core Identity

- **Site-first model:** `creator_x_profile` is the root entity; commerce is activated on demand.
- **MySpace-era Y2K aesthetics** as the differentiating design identity.
- **Hard constraint:** The platform must fully comply with X's rules and policies at all times.
- **Clean separation of concerns:** RareImagery's systems never touch X's native systems (e.g., X Subscriptions run entirely through X — RareImagery has zero involvement).

---

## 2. Tech Stack

| Layer | Technology | Details |
|-------|-----------|---------|
| **Backend (headless CMS)** | Drupal 10.3 + PostgreSQL 16 | Hostinger KVM2 Ubuntu VPS (`72.62.80.155`); Docker containers (`rare-drupal`, `rare-postgres`); JSON:API for headless delivery |
| **Frontend** | Next.js 16.1.6 (App Router) + React 19 + TypeScript 5 + Tailwind CSS 4 | Vercel Pro hosting |
| **DNS & CDN** | Cloudflare | Wildcard subdomain routing; CDN/caching layer |
| **Payments** | Stripe 20.x | One-time + recurring billing via `add_invoice_items`; webhook handling |
| **AI — Theme/Import** | xAI Grok API | X profile imports, theme generation, product suggestions (`src/lib/grok.ts`) |
| **AI — Chat/Generation** | Anthropic Claude API (`@anthropic-ai/sdk` 0.78) | Site generation (`src/lib/ai/generate-site.ts`), theme chat, page builder |
| **AI — Drupal Admin** | Claude + Grok (tool-use agents) | `rareimagery_ai` Drupal module with `AiAgent`, `ClaudeClient`, `XaiClient` |
| **Auth** | X OAuth 2.0 (creator login) + email/password; NextAuth 4.x (session management) | `src/lib/auth.ts` |
| **Print-on-Demand** | Printful | 13 API routes; `src/lib/printful.ts` (20KB client); `rareimagery_xstore` Drupal integration |
| **Email** | Brevo (free SMTP) via Nodemailer 7 | `src/lib/notifications.ts` |
| **SMS** | Telnyx (pay-as-you-go) | Opt-in secondary notification channel |
| **Planning & Docs** | Claude (this AI) | Produces structured `.md` spec/planning documents |

---

## 3. Infrastructure

### Remote Server (Production)
- **Host:** Hostinger KVM2 Ubuntu VPS at `72.62.80.155`
- **SSH:** `ssh root@72.62.80.155`
- **Docker Containers:**
  - `rare-drupal` — Drupal 10.3 on PHP 8.3-Apache (port 80)
  - `rare-postgres` — PostgreSQL 16 (port 5432)
- **Network:** `marketplace-net` (Docker bridge)
- **Drush:** `docker exec rare-drupal /opt/drupal/vendor/bin/drush`

### Local Docker (Development)
- Same `docker-compose.yml` — port 8080 (Drupal), port 5433 (Postgres)
- **Warning:** Local DB has ZERO data — all real data is on remote server

### Vercel (Frontend)
- Next.js deployed on Vercel Pro
- Environment variables configured for production + development
- Wildcard subdomains at $0 cost

### Cloudflare (DNS/CDN)
- Wildcard DNS: `*.rareimagery.net`
- CDN/caching reduces Vercel bandwidth usage

---

## 4. Drupal Backend Architecture

### Custom Modules (3)

#### `rareimagery_xstore` (Core Platform Module)
The main module powering the entire marketplace:
- **11 REST Resources:** StoreCreate, StoreList, StoreProfile, Checkout, CurrentUserStore, DashboardAnalytics, PrintfulSyncTrigger, ShippingRates, StripeConnectOnboarding, SubscriptionCheckout/Portal/Status, XProfilePreview
- **6 Controllers:** StorePage, DashboardApp, PrintfulWebhook, StripeSubscriptionWebhook, InviteCode
- **7 Services:** StoreManager, PrintfulSync, SubscriptionManager, CreatorTheme, XProfileScraper
- **3 Event Subscribers:** PlatformFee, PrintfulOrder, StripeConnect
- **2 Entity Resolvers:** CreatorStore, StoreOrderType
- **3 Forms:** PrintfulSettings, XaiSettings, InviteCode
- **Dependencies:** Full Drupal Commerce stack, Stripe, Commerce License/File, Search API, Facets, Pathauto, JSON:API, REST

#### `rareimagery_ai` (AI Admin Module)
AI-powered Drupal administration via Claude & Grok with tool-use:
- `AiChatController`, `ClaudeClient`, `XaiClient`, `AiAgent`, `ToolRegistry`
- `AiSettingsForm` for API key configuration

#### `rareimagery_x_import` (X Profile Import)
One-click X profile import via Grok:
- `XApiService`, `GrokService`
- Import & settings forms

### Custom Theme
- **`rareimagery`** — Based on Olivero, 10 regions, custom global styles

### Content Types
- **`creator_x_profile`** — Root entity with fields: `field_x_username`, `field_store_theme`, `field_bio_description`, `field_follower_count`, `field_top_posts`, `field_top_followers`, `field_metrics`, `field_profile_picture`, `field_background_banner`, `field_linked_store`, `field_page_builds`

### Auth Mechanisms
- **Basic Auth** (`drupalAuthHeaders()`) — JSON:API reads; requires custom `jsonapi_basic_auth` module
- **Cookie/Session Auth** (`drupalWriteHeaders()`) — All POST/PATCH operations; logs in via `/user/login?_format=json`, caches session cookie + CSRF token for 10 min
- **Admin user:** `rare` (uid 1, `is_admin: true`)

### Commerce Store Required Fields
- `default_currency` — JSON:API **relationship** (not attribute), UUID: `7be59a35-eea8-4d2d-8be4-b113aafad8d4` for USD
- `timezone` — String, e.g. `"America/New_York"`
- `address` — Requires all subfields: `country_code`, `address_line1`, `locality`, `administrative_area`, `postal_code`

---

## 5. Next.js Frontend Architecture

### Route Structure (29 route groups, 55 API routes)

**Public Routes:**
| Route | Purpose |
|-------|---------|
| `/` | Home page |
| `/login`, `/signup` | Authentication |
| `/stores/[creator]` | Public creator storefront |
| `/products/[slug]` | Product detail pages |
| `/build` | Floating builder page |
| `/eula`, `/privacy`, `/terms` | Legal pages |

**Console Routes:**
| Route | Purpose |
|-------|---------|
| `/console` | Main dashboard |
| `/console/stores`, `/console/stores/new`, `/console/stores/[id]/theme` | Store management |
| `/console/stores/[id]/theme/chat` | AI theme customization chat |
| `/console/builder` | AI-powered page builder |
| `/console/products` | Product catalog management |
| `/console/orders` | Order history & details |
| `/console/printful` | Printful integration console |
| `/console/shipping` | Shipping configuration |
| `/console/subscriptions` | Subscription tier management |
| `/console/social` | Social features (followers, picks, shoutouts) |
| `/console/accounting` | Revenue & payment tracking |
| `/console/admin`, `/console/admin/subscribers` | Admin panel |
| `/console/settings` | Account settings |
| `/console/setup` | First-time setup flow |
| `/console/support` | Support portal |
| `/console/upgrade-success` | Upgrade confirmation |

**Mobile App Deep Linking:**
- `/.well-known/apple-app-site-association` (iOS)
- `/.well-known/assetlinks.json` (Android)

### API Routes (55 endpoints)

**Authentication:** NextAuth handler, email/password registration, debug auth
**Stores:** Create, provision, select-theme, generate-theme, theme-chat, enhance-profile (Grok), import-x-data, products, approve
**Checkout & Orders:** Stripe checkout, product checkout, order list/detail, shipping rates
**Printful (13 routes):** Catalog browsing, product sync, mockup generation, order creation, shipping/tax estimation, webhook setup, connection status
**Subscriptions:** Tiers, checkout, status, X subscription management
**Social:** Follow/unfollow, followers list, conversations, picks, shoutouts, X seed import
**AI/Chat:** General chat, site generation, build save/load
**Cron:** `api-agent` (X API health monitoring), `frontend-agent` (frontend sync)
**Webhooks:** Stripe events, X events, Printful fulfillment events
**Other:** Notification preferences, accounting, app-config lookup, X feed proxy, invite verification

### Components (45 files)

**Theme Components (6 themes):**
| Theme | File | Size |
|-------|------|------|
| Xai3 (default) | `Xai3Theme.tsx` | 31KB |
| X Mimic | `XMimicTheme.tsx` | 46KB |
| MySpace | `MySpaceTheme.tsx` | 41KB |
| Minimal | `MinimalTheme.tsx` | 18KB |
| Neon | `NeonTheme.tsx` | 18KB |
| Editorial | `EditorialTheme.tsx` | 16KB |

**Builder Components (5):**
`FloatingBuilder.tsx`, `BuildLibrary.tsx`, `BuilderGate.tsx`, `StoreBuildRenderer.tsx`, `LivePreview.tsx`

**Commerce Components:**
`AddToCartBlock`, `ProductGallery`, `ProductManager` (21KB), `ProductTabs`, `PrintfulManager` (38KB — largest component), `ProvisionButton`, `UpgradeButton`

**Social Components:**
`FollowButton`, `MyPicks`, `MyPicksManager`, `ShoutoutWall`, `CreatorProfileCard`, `RareProjectConversations`, `StoreRareProjectConversations`

**Subscription Components:**
`SubscribeOnXButton`, `SubscriberGate`, `SubscriberTierControl`, `SubscriptionTierManager` (12KB), `SubscriptionTiers`, `SupporterBadge`

**Auth & Navigation:**
`AuthButton`, `InviteGate`, `Providers`, `ConsoleSidebar`, `ConsoleUserMenu`, `ConsoleContext`, `Sidebar`, `StoreNav`

**Store Setup:**
`StoreBuilderWizard` (19KB), `ThemeSelector`, `StoreApprovalButton`, `XSeedImport`, `NotificationPreferences`

### Lib Layer (33 utility files)

**Core Integrations:**
| File | Size | Purpose |
|------|------|---------|
| `drupal.ts` | 36KB | JSON:API client, auth helpers, entity types |
| `printful.ts` | 20KB | Printful API client with sync/order helpers |
| `x-import.ts` | 15KB | X API v2 data import (profiles, posts, followers) |
| `social.ts` | 14KB | Follow system, picks, shoutouts |
| `payments.ts` | 11KB | Payment provider abstraction (X Money ready) |
| `notifications.ts` | 10KB | Email (Brevo) & SMS (Telnyx) |
| `api-agent.ts` | 9KB | X API health monitoring cron |
| `frontend-agent.ts` | 8KB | Frontend background task runner |
| `auth.ts` | 8KB | NextAuth.js config (X OAuth + email/password) |
| `ai/generate-site.ts` | 9KB | Anthropic SDK site generation |
| `grok.ts` | — | Grok AI enhancement (themes, products) |
| `stripe.ts` | — | Stripe client initialization |
| `drupalBuilds.ts` | — | Page build persistence |
| `ownership.ts` | — | Store ownership validation |
| `rate-limit.ts` | — | Request rate limiting |

**X API Subdirectory (`x-api/`, 11 files):**
`client.ts`, `conversations.ts`, `errors.ts`, `fetch-with-retry.ts`, `post.ts`, `timeline.ts`, `types.ts`, `usage.ts`, `user.ts`, `webhook-subscriptions.ts`

**Webhook Handlers (`webhooks/`):**
`process-event.ts` (event router), `handlers/shoutout.ts`, `handlers/sync-profile.ts`, `handlers/update-feed.ts`

### Layout & Fonts
Root layout (`src/app/layout.tsx`): **Sora**, **DM Sans**, **JetBrains Mono**, **Inter**

---

## 6. Pricing Model

- **One-time setup fee:** $5 (collected by RareImagery via Stripe)
- **Monthly maintenance:** $2/month (collected by RareImagery via Stripe)
- **X Subscription:** Separate $2/month — runs through X natively. RareImagery has **no involvement** in this charge.

### Stripe Implementation
- First invoice uses `add_invoice_items` to combine the one-time $5 + first $2/month.
- Prices stored in **environment variables** to avoid redeploys on price changes.
- Stripe webhooks handle payment events at `/api/webhooks/stripe`.
- Pricing model is explicitly noted as subject to future evolution.
- X Money planned as future primary payment provider (see `payments.ts` abstraction layer).

---

## 7. Subdomain Architecture

- **Wildcard DNS** is a one-time Cloudflare setup (`*.rareimagery.net`).
- New storefronts are **purely Drupal database operations** — no Vercel action required per creator.
- Wildcard subdomains on Vercel cost **$0**.
- Cloudflare handles DNS resolution → Vercel serves Next.js → app reads subdomain slug → fetches `creator_x_profile` from Drupal via JSON:API.

---

## 8. MySpace Theme Bot

A Grok-powered chatbot that outputs a **JSON theme config** (not JSX/CSS directly) that Next.js renders.

### Ten Subculture Presets

1. Emo / Dark Emo
2. Scene Kid
3. Pop Princess
4. Hip-Hop / Rap
5. Indie / Alt
6. Gamer / Neon Cyber
7. Cottagecore
8. Y2K / McBling
9. Goth / Dark Romantic
10. Skate / Streetwear

Each preset includes full hex palettes, typography rules, and decoration rules.

### Hard Limits

- Mobile responsiveness required
- Products above the fold
- No autoplay audio
- Reduced motion fallback **always enabled**

### Documentation

- Rules: `MYSPACE_THEME_BOT_RULES.md` (25.5KB comprehensive ruleset)
- Showcase: `best_creations.json` (grows over time as creators rate themes in the console)

---

## 9. Floating Page Builder

5-component system under `src/components/builder/`:

| Component | Purpose |
|-----------|---------|
| `FloatingBuilder.tsx` | Draggable AI assistant panel (Generate / Preview / Saved Builds tabs) |
| `BuildLibrary.tsx` | Component/section library |
| `BuilderGate.tsx` | Access control gate |
| `StoreBuildRenderer.tsx` | Render published builds on storefronts |
| `LivePreview.tsx` | Real-time preview during editing |

Saved builds stored as **JSON** on Drupal's `field_page_builds` field. Persistence via `src/lib/drupalBuilds.ts`.

---

## 10. Social Layer

### Implemented Features (Phase 1)

| Feature | Components | API Routes |
|---------|-----------|------------|
| **Follow/Unfollow** | `FollowButton.tsx` | `POST /api/social/follow`, `GET /api/social/followers` |
| **Shoutout Walls** | `ShoutoutWall.tsx` | `POST /api/social/shoutouts` |
| **My Picks** | `MyPicks.tsx`, `MyPicksManager.tsx` | `POST /api/social/picks` |
| **Conversations** | `RareProjectConversations.tsx`, `StoreRareProjectConversations.tsx` | `GET /api/social/conversations`, `GET /api/social/conversations/[username]` |
| **X Data Seeding** | `XSeedImport.tsx` | `POST /api/social/seed-from-x` |
| **Creator Profiles** | `CreatorProfileCard.tsx` | Via Drupal JSON:API |

Business logic: `src/lib/social.ts` (14KB)

### Phase 2+ (Planned)

- Creator Circles — Collectives with shared badges
- Collab Drops — Co-released products with revenue split modeling
- Discover Page — Public creator discovery
- Social Proof Widgets — Embeddable on storefronts

---

## 11. Printful Integration

Full print-on-demand pipeline with 13 dedicated API routes:

| Capability | Route(s) |
|-----------|----------|
| Catalog browsing | `GET /api/printful/catalog`, `GET /api/printful/catalog/[productId]` |
| Account connection | `POST /api/printful/connect`, `GET /api/printful/status` |
| Product sync | `POST /api/printful/sync`, `GET /api/printful/products` |
| Mockup generation | `POST /api/printful/mockups`, `GET /api/printful/mockups/[taskKey]` |
| Order creation | `POST /api/printful/orders`, `POST /api/printful/orders/estimate` |
| Shipping/Tax | `GET /api/printful/shipping-rates`, `POST /api/printful/tax` |
| Webhooks | `POST /api/printful/webhook`, `POST /api/printful/webhook/setup` |

Frontend: `PrintfulManager.tsx` (38KB — largest component)
Backend: `src/lib/printful.ts` (20KB client), Drupal `PrintfulSyncService` + `PrintfulOrderSubscriber`

---

## 12. Subscription System

- **Subscription Tiers** managed via `/console/subscriptions`
- **Components:** `SubscriptionTierManager` (12KB), `SubscriptionTiers`, `SubscriberTierControl`, `SubscriberGate`, `SubscribeOnXButton`, `SupporterBadge`
- **API Routes:** `/api/subscriptions/tiers`, `/api/subscriptions/checkout`, `/api/subscriptions/status`, `/api/x-subscription`
- **Drupal:** `SubscriptionManagerService`, `SubscriptionCheckoutResource`, `SubscriptionPortalResource`, `SubscriptionStatusResource`
- **Webhooks:** `StripeSubscriptionWebhookController`, `StripeConnectSubscriber`

---

## 13. AI Integration

### Dual AI Provider Architecture

| Provider | SDK | Use Cases |
|----------|-----|-----------|
| **xAI Grok** | REST API (`src/lib/grok.ts`) | X profile imports, theme recommendations, product suggestions |
| **Anthropic Claude** | `@anthropic-ai/sdk` 0.78 (`src/lib/ai/generate-site.ts`) | Site generation, theme chat, page builder content |

### Drupal AI Module (`rareimagery_ai`)
- Tool-use agent architecture: `AiAgent` orchestrates `ClaudeClient` + `XaiClient`
- `ToolRegistry` for extensible tool definitions
- Admin chat interface for AI-powered Drupal administration

### AI-Powered Features
- Theme generation & customization chat (`/api/stores/generate-theme`, `/api/stores/theme-chat`)
- Profile enhancement (`/api/stores/enhance-profile`)
- Site content generation (`/api/site/generate`, `/api/chat`)
- Page builder content (`/api/builds`)

---

## 14. Cron & Monitoring Agents

| Agent | File | Route | Purpose |
|-------|------|-------|---------|
| **API Agent** | `src/lib/api-agent.ts` (9KB) | `POST /api/cron/api-agent` | Monitors X API health, token validity, rate limits |
| **Frontend Agent** | `src/lib/frontend-agent.ts` (8KB) | `POST /api/cron/frontend-agent` | Frontend background sync tasks |

Protected by `CRON_SECRET` environment variable.

---

## 15. Notifications

- **Email:** Brevo free SMTP via Nodemailer 7 (`src/lib/notifications.ts`)
- **SMS:** Telnyx pay-as-you-go (opt-in)
- **Preferences:** `NotificationPreferences.tsx` component + `/api/notifications/preferences` route
- **Admin alerts:** Configurable via `ADMIN_X_USERNAMES` env var

---

## 16. Ad System

- **Engagement-only Creator Cards** built from Grok-imported X profile data.
- Placements: console and storefronts.
- **Hard cap:** 2–3 per page.
- **Never** interrupts active workflows.

---

## 17. Mobile Apps (Future)

White-label iOS/Android apps as a **paid upsell**.

### Two-Tier Model

| Tier | Description |
|------|-------------|
| **Tier 1** | Hosted under RareImagery's App Store accounts |
| **Tier 2** | White-label under the creator's own developer accounts |

### Implementation
- `app-config.json` as single source of truth per app (`/api/app-config/[slug]` route exists).
- Checkout kept as a **webview** to avoid PCI scope in native apps.
- Build pipeline: Fastlane + CI for white-label builds.
- Deep linking scaffolded: `apple-app-site-association` + `assetlinks.json` routes exist.

---

## 18. Scaling & Cost Model

### VPS Tier Upgrade Triggers
- ~100 creators: evaluate upgrade
- ~300 creators: likely required

### Primary Scaling Costs to Watch
1. **Vercel bandwidth** beyond Pro's monthly allowance (partially mitigated by Cloudflare CDN)
2. **Stripe flat fee** at high volume of low-value transactions
3. **Grok + Claude API costs** — negligible per signup; page builder usage is the variable

### Cost Mitigation
- Saved builds eliminate repeat AI API costs
- Cloudflare caching reduces Vercel bandwidth
- Storing generated components as JSON in Drupal is effectively free

---

## 19. Key Architectural Principles

1. **Complement X, never compete with or circumvent it.** Hard constraint on all feature design.
2. **Site-first, not commerce-first.** Commerce feels like an upgrade, not a requirement.
3. **AI outputs JSON configs, not code.** Next.js handles all rendering. AI layer is swappable.
4. **New storefronts = database operations.** No infrastructure changes per creator signup.
5. **Saved builds eliminate repeat API costs.** JSON in Drupal is effectively free storage.
6. **Prices in env vars.** No redeploys for price changes.
7. **Dual auth strategy.** Basic Auth for reads, cookie/session auth for writes.
8. **Dual AI providers.** Grok for X-native tasks, Claude for generation/chat.

---

## 20. Environment Variables

Configured across localhost (`.env.local`), Vercel (production + development), and Drupal server:

| Variable | Purpose |
|----------|---------|
| `DRUPAL_API_URL` | Drupal backend URL |
| `DRUPAL_API_USER` / `DRUPAL_API_PASS` | Drupal Basic Auth |
| `NEXTAUTH_SECRET` / `NEXTAUTH_URL` | NextAuth session |
| `X_CLIENT_ID` / `X_CLIENT_SECRET` | X OAuth 2.0 |
| `X_CONSUMER_KEY` / `X_CONSUMER_SECRET` | X API (OAuth 1.0a) |
| `X_API_BEARER_TOKEN` | X App-only auth |
| `XAI_API_KEY` | Grok AI |
| `ANTHROPIC_API_KEY` | Claude AI |
| `STRIPE_*` | Stripe payments |
| `SMTP_HOST` / `SMTP_PORT` / `SMTP_USER` / `SMTP_PASS` | Brevo email |
| `TELNYX_API_KEY` / `TELNYX_FROM_NUMBER` | SMS |
| `CRON_SECRET` | Cron job auth |
| `INVITE_CODES` | Invite gate |
| `ADMIN_X_USERNAMES` | Admin notification targets |

---

## 21. Working Conventions

### Document Production
- Robert provides direction or raw content; Claude produces clean, structured `.md` spec/planning documents.
- Large documents are split into focused, single-concern files when scope grows.
- Decisions are made incrementally across sessions with corrections applied when earlier docs drift from actual intent.

### Development Environment
- **Editor:** VS Code with Claude Code extension
- **CLI:** Claude Code terminal
- **API keys:** Configured across all environments (localhost, Vercel, Drupal server)

### Tone & Expertise Level
- Robert has decades of experience in both hardware and software development.
- Skip beginner explanations. Communicate at a senior/staff engineer level.
- Be direct. Flag tradeoffs and risks proactively.

---

## 22. Spec Documents Index

63 markdown documents in the project root, key ones:

| Document | Size | Content |
|----------|------|---------|
| `RAREIMAGERY_COMPLETE.md` | 68KB | Comprehensive platform documentation |
| `MYSPACE_THEME_BOT_RULES.md` | 25.5KB | Theme bot behavior, presets, hard limits |
| `Xai3_theme.md` / `Xai1_theme.md` | 33.5KB ea. | Theme specifications |
| `PRINTFUL_API_REFERENCE.md` | 13.6KB | Printful integration reference |
| `PRINTFUL_API_TEST_PLAYBOOK.md` | 20.8KB | Printful testing procedures |
| `API_CATALOG.md` | — | Full API endpoint catalog |
| `INTEGRATION_MAP.md` | — | System integration diagram |
| `DATA_FLOW.md` | — | Data flow documentation |
| `ALGORITHMS.md` | — | Algorithm documentation |
| `OPERATIONS.md` | — | Operational procedures |
| `1_SUBDOMAIN_CREATION.md` → `6_VERCEL_DEPLOY.md` | — | Sequential setup guides |
| `RAREIMAGERY_PRICING_AND_X_INTEGRATION.md` | — | Pricing & X integration spec |
| `cost-to-run.md` | — | Cost modeling |
| `creator-mobile-app-plan.md` | — | Mobile app architecture |
| `X_auth_setup.md` / `X_auth_through_next.md` | — | Auth implementation guides |
| `X_SUBSCRIPTIONS_SUPPORT_TIER.md` | — | Subscription tier spec |
| `EULA.md` / `TOS.md` | — | Legal documents |
| `best_creations.json` | — | Theme showcase data |

---

## 23. Development Scripts

### Makefile Commands
`up`, `down`, `build`, `logs` (Docker) | `drush`, `cr`, `export`, `import` (Drupal) | `fe-install`, `fe-dev-storefront`, `fe-build`, `fe-lint` (Frontend) | `printful-sync` (POD)

### PHP Setup Scripts (`/scripts/`, 30+)
Database/Config: `setup_drupal_fields.php`, `setup_product_types.php`, `configure_form_displays.php`
Data: `create_demo_users.php`, `create_demo_profiles.php`, `create_demo_products.php`
Integrations: `setup_printful.php`, `setup_x_subscription_fields.php`
Utilities: `check_and_fix_jsonapi.php`, `grant_api_integration_permissions.php`, `healthcheck.sh`

---

## 24. Open Questions & Future Work

- Pricing model evolution at scale
- VPS tier upgrades at ~100 and ~300 creator thresholds
- Phase 2+ social features: Creator Circles, Collab Drops, social proof widgets
- Bandwidth billing beyond Vercel Pro's monthly allowance
- `best_creations.json` curation pipeline
- White-label mobile app rollout timeline
- X Money as primary payment provider (abstraction layer ready in `payments.ts`)
- Vercel preview environment variable setup (requires branch name)
