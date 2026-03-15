# RareImagery — External Integration Map

Every external service the platform connects to: credentials, endpoints, error handling, and configuration.

---

## 1. Drupal JSON:API (Backend CMS)

The primary data store for all platform entities.

| Property | Value |
|---|---|
| **Base URL** | `DRUPAL_API_URL` (default: `http://72.62.80.155`) |
| **Protocol** | JSON:API (`application/vnd.api+json`) |
| **Database** | PostgreSQL 16 (`rare-postgres` container) |

### Authentication (Dual Strategy)

**Read operations — Basic Auth:**
```
drupalAuthHeaders() → { Authorization: "Basic base64(user:pass)" }
Env: DRUPAL_API_USER, DRUPAL_API_PASS
Fallback: Bearer token via DRUPAL_API_TOKEN
```

**Write operations — Cookie Session Auth:**
```
drupalWriteHeaders() → { Cookie: "SESS...=...", X-CSRF-Token: "..." }
Login: POST /user/login?_format=json { name, pass }
CSRF:  GET /session/token
Cache: 10-minute in-memory TTL
```

Why two methods: Basic Auth returns 403 on Drupal Commerce write operations. Cookie auth with CSRF token is required for all POST/PATCH/DELETE.

### Key Entities

| Entity Type | JSON:API Path | Key Fields |
|---|---|---|
| Creator Profile | `node/creator_x_profile` | `field_x_username`, `field_store_theme`, `field_linked_store`, `field_top_posts`, `field_metrics` |
| Commerce Store | `commerce_store/online` | `field_store_slug`, `field_store_status`, `field_printful_api_key`, `field_follower_count` |
| Product (5 types) | `commerce_product/{default,clothing,digital_download,crafts,printful}` | `title`, `body`, `field_images`, `field_subscriber_only` |
| Product Variation | `commerce_product_variation/{bundle}` | `sku`, `price`, `field_stock`, `attributes` |
| Order | `commerce_order/default` | `state`, `order_items`, `billing_profile` |
| Follow Flag | `flagging/follow_creator` | `field_follower_store_id`, `flagged_entity` |
| User | `user/user` | `mail`, `field_phone_number`, `field_shop_name`, `field_store` |
| Currency | `commerce_currency/commerce_currency` | USD UUID: `7be59a35-eea8-4d2d-8be4-b113aafad8d4` |

### Query Patterns

```
Filter:      ?filter[field_name]=value
Relationship filter: ?filter[stores.meta.drupal_internal__target_id]=123
Includes:    ?include=field_linked_store,field_profile_picture
Pagination:  ?page[offset]=0&page[limit]=50
Sparse fields: ?fields[commerce_product--default]=title
```

### Caching
- Read: `{ next: { revalidate: 60 } }` (60s ISR)
- Dynamic: `{ cache: "no-store" }` (orders, social, console)

**Source:** `src/lib/drupal.ts`

---

## 2. X (Twitter) API v2

User data import and OAuth authentication.

| Property | Value |
|---|---|
| **Base URL** | `https://api.twitter.com/2` |
| **Auth** | OAuth 2.0 User Context (per-user access tokens) |
| **Env Vars** | `X_CLIENT_ID`, `X_CLIENT_SECRET` |

### Endpoints Used

| Endpoint | Purpose | Max Results |
|---|---|---|
| `GET /users/{id}` | Profile data (name, bio, metrics, images, verified) | 1 |
| `GET /users/{id}/tweets` | Recent tweets with metrics + media | 10 |
| `GET /users/{id}/followers` | Follower list with metrics | 20 (top 8 kept) |
| `GET /users/{id}/following` | Following list (for X seed import) | varies |

### User Fields Requested
```
user.fields=name,username,description,profile_image_url,profile_banner_url,public_metrics,verified
tweet.fields=public_metrics,created_at,attachments
expansions=attachments.media_keys
media.fields=url,preview_image_url
```

### Token Lifecycle
- Obtained via NextAuth TwitterProvider (OAuth 2.0 PKCE)
- Stored in JWT: `xAccessToken`, `xAccessTokenSecret`
- Session max age: 8 hours

### Error Handling
- Non-200 responses: logged, return empty data (profile sync is fire-and-forget)
- Rate limits: caller receives `{ ok: false, status: 429 }`

**Source:** `src/lib/x-import.ts`, `src/lib/auth.ts`

---

## 3. xAI Grok

AI-powered profile enhancement and content analysis.

| Property | Value |
|---|---|
| **API URL** | `https://api.x.ai/v1/chat/completions` |
| **Model** | `grok-3` |
| **Auth** | Bearer token via `XAI_API_KEY` |

### Usage

| Feature | Temperature | Response Format | Timeout |
|---|---|---|---|
| Profile enhancement | 0.5 | `json_object` | 20s |
| X feed proxy | — | — | — |

### Profile Enhancement Output
```json
{
  "storeBio": "AI-written 2-3 sentence store bio",
  "suggestedProducts": [{"name", "description", "category"}],
  "recommendedTheme": "minimal",
  "topThemes": ["theme1", "theme2", ...],
  "audienceSentiment": "Positive"
}
```

### Graceful Degradation
- If `XAI_API_KEY` not set: skips enhancement, returns null
- On API error: returns null, original X data used as-is
- Parse failure: returns null with error log

**Source:** `src/lib/grok.ts`

---

## 4. Anthropic Claude

AI page builder for generating React components.

| Property | Value |
|---|---|
| **SDK** | `@anthropic-ai/sdk` |
| **Model** | `claude-haiku-4-5-20241022` |
| **Auth** | `ANTHROPIC_API_KEY` |
| **Streaming** | Enabled |

### Usage
- **Feature:** Page builder component generation
- **Rate Limit:** 10 requests/hour per user (in-memory tracking)
- **System Prompt:** Base rules + theme-specific styling instructions (6 themes)
- **Output:** Self-contained React functional components with Tailwind CSS

### Theme Prompts
Each theme has a specific visual language instruction:
- **xai3:** Dark, premium, X-inspired (zinc-950, indigo-500, monospace stats)
- **minimal:** Clean, light, whitespace (white/gray-50, system fonts)
- **neon:** Cyberpunk (black, cyan/magenta glow, pulse animations)
- **editorial:** Magazine (cream, serif headers, gallery layout)
- **myspace:** Y2K retro (glitter, blink, marquee, emoji tiles)
- **xmimic:** X/Twitter clone (black, blue-500, timeline cards)

**Source:** `src/app/api/chat/route.ts`

---

## 5. Stripe

Payment processing for store setup, product purchases, and subscriptions.

| Property | Value |
|---|---|
| **SDK** | `stripe` npm package |
| **API Version** | `2026-02-25.clover` |
| **Auth** | `STRIPE_SECRET_KEY` |
| **Webhook Secret** | `STRIPE_WEBHOOK_SECRET` |

### Features Used

| Feature | Purpose |
|---|---|
| Checkout Sessions (payment) | Store setup ($11), listing fees ($0.05), product purchases |
| Checkout Sessions (subscription) | $6/month store maintenance, fan tier subscriptions |
| Webhooks | Payment completion → store creation, product creation, subscription lifecycle |
| Prices (dynamic) | Created on-the-fly for subscriptions |
| Subscriptions | Auto-recurring billing, cancellation → store suspension |

### Webhook Events Handled

| Event | Handler |
|---|---|
| `checkout.session.completed` (store_setup) | Create store + profile + start subscription |
| `checkout.session.completed` (product_listing) | Create product from session metadata |
| `customer.subscription.deleted` | Suspend store (`field_store_status: "suspended"`) |
| `invoice.payment_failed` | Log warning (Stripe auto-retries) |

### Fee Structure
- Store setup: $5 one-time + $6 first month = $11
- Monthly: $6/month recurring
- Listing fee: $0.05 per product after 50
- Platform fee: 2.9% + $0.30 per order

### Initialization
Lazy singleton pattern — Stripe client created on first use:
```typescript
let _stripe: Stripe | null = null;
export function getStripeClient(): Stripe {
  if (!_stripe) _stripe = new Stripe(key, { apiVersion: "2026-02-25.clover" });
  return _stripe;
}
```

**Source:** `src/lib/stripe.ts`, `src/lib/payments.ts`, `src/app/api/webhooks/stripe/route.ts`

---

## 6. X Money (Planned)

Future primary payment provider. Currently a stub implementation.

| Property | Value |
|---|---|
| **Status** | Not yet available (throws error on use) |
| **Auth** | `XMONEY_API_KEY` (env var check) |
| **Priority** | Preferred over Stripe when available |

### Planned Endpoints
```
POST https://api.x.com/2/payments/intents          → Create payment
POST https://api.x.com/2/payments/subscriptions     → Create subscription
GET  https://api.x.com/2/payments/intents/{id}      → Verify payment
DELETE https://api.x.com/2/payments/subscriptions/{id} → Cancel subscription
```

### Provider Resolution
```
if (XMONEY_API_KEY) → XMoneyProvider  ← preferred
else if (STRIPE_SECRET_KEY) → StripeProvider  ← current
else → Error("No payment provider configured")
```

**Source:** `src/lib/payments.ts`

---

## 7. Printful (Print-on-Demand)

Product catalog sync and order fulfillment.

| Property | Value |
|---|---|
| **API URL** | `https://api.printful.com` |
| **Auth** | Per-store API key stored in Drupal (`field_printful_api_key`) |

### Endpoints Used

| Endpoint | Purpose |
|---|---|
| `GET /store` | Verify API key, get store info |
| `GET /store/products` | Fetch product catalog for sync |

### Product Sync Flow
1. Fetch products from Printful
2. For each: check if already synced (by `field_printful_product_id`)
3. Create `commerce_product_variation--printful` in Drupal
4. Create `commerce_product--printful` in Drupal
5. Upload thumbnail image (fire-and-forget)

### Order Fulfillment (Drupal-side)
- `PrintfulOrderSubscriber` listens for order events
- Submits orders to Printful API
- `PrintfulWebhookController` handles status updates
- Billing: customer pays store price → Printful charges owner for production

**Source:** `src/app/api/printful/connect/route.ts`, `src/app/api/printful/sync/route.ts`

---

## 8. Brevo (Sendinblue) SMTP

Transactional email delivery.

| Property | Value |
|---|---|
| **Host** | `SMTP_HOST` (default: `smtp-relay.brevo.com`) |
| **Port** | `SMTP_PORT` (default: `587`) |
| **Auth** | `SMTP_USER`, `SMTP_PASS` |
| **From** | `EMAIL_FROM` (default: `notifications@rareimagery.net`) |
| **Library** | `nodemailer` |

### Email Templates
All emails use a branded dark-theme HTML wrapper (`emailWrapper()`):
- Gradient logo header (indigo → purple)
- Dark card container (zinc-900 bg, zinc-800 border)
- Responsive, max-width 560px

### Email Types Sent

| Function | Trigger | Recipient |
|---|---|---|
| `notifyAdminNewStore()` | Store created | Admin |
| `notifyStoreApproved()` | Store approved | Store owner |
| `notifyStoreRejected()` | Store rejected | Store owner |
| `notifyNewSale()` | Product purchased | Store owner |
| Health alert | Critical health check | Admin |

### Graceful Degradation
- If `SMTP_USER` not set: logs warning, returns `false`
- On send failure: catches error, returns `false`

**Source:** `src/lib/notifications.ts`

---

## 9. Telnyx

SMS notifications for store owners.

| Property | Value |
|---|---|
| **API URL** | `https://api.telnyx.com/v2/messages` |
| **Auth** | Bearer token via `TELNYX_API_KEY` |
| **From** | `TELNYX_FROM_NUMBER` |

### SMS Types Sent

| Trigger | Message |
|---|---|
| Store approved | "Your store is now live at {url}" |
| Store rejected | "Application needs attention, check email" |
| New sale | "New sale! {product} for {amount} on {store}" |

### Graceful Degradation
- If `TELNYX_API_KEY` or `TELNYX_FROM_NUMBER` not set: logs warning, returns `false`
- On failure: catches error, returns `false`

**Source:** `src/lib/notifications.ts`

---

## 10. Vercel

Frontend hosting and serverless functions.

| Property | Value |
|---|---|
| **Platform** | Vercel |
| **Framework** | Next.js 16 (App Router) |
| **Domain** | `rareimagery.net` |
| **Config** | `frontend/vercel.json` |

### Cron Jobs
```json
{ "path": "/api/cron/frontend-agent", "schedule": "*/30 * * * *" }
```
- Runs health agent every 30 minutes
- Auth: `CRON_SECRET` Bearer token
- Max duration: 60 seconds

### CDN Headers
```
Cache-Control: s-maxage=60, stale-while-revalidate=300
```
Applied to all routes — 60s fresh, 5m stale-while-revalidate.

### Image Domains (next.config.ts)
```
72.62.80.155          — Drupal server
*.rareimagery.net     — Wildcard subdomains
pbs.twimg.com         — X profile images
```

**Source:** `frontend/vercel.json`, `frontend/next.config.ts`

---

## Environment Variables Summary

### Required for Operation

| Variable | Service | Purpose |
|---|---|---|
| `DRUPAL_API_URL` | Drupal | Backend API base URL |
| `DRUPAL_API_USER` | Drupal | Basic Auth username |
| `DRUPAL_API_PASS` | Drupal | Basic Auth password |
| `NEXTAUTH_SECRET` | NextAuth | JWT signing secret |
| `NEXTAUTH_URL` | NextAuth | Canonical URL |
| `X_CLIENT_ID` | X OAuth | OAuth 2.0 client ID |
| `X_CLIENT_SECRET` | X OAuth | OAuth 2.0 client secret |
| `STRIPE_SECRET_KEY` | Stripe | API secret key |
| `STRIPE_WEBHOOK_SECRET` | Stripe | Webhook signature secret |

### Optional / Feature Flags

| Variable | Service | Purpose |
|---|---|---|
| `DRUPAL_API_TOKEN` | Drupal | Bearer token (alternative to user/pass) |
| `XAI_API_KEY` | xAI Grok | Profile enhancement |
| `ANTHROPIC_API_KEY` | Claude | Page builder AI |
| `XMONEY_API_KEY` | X Money | Payment provider (not yet available) |
| `SMTP_USER` / `SMTP_PASS` | Brevo | Email notifications |
| `TELNYX_API_KEY` / `TELNYX_FROM_NUMBER` | Telnyx | SMS notifications |
| `CRON_SECRET` | Vercel | Cron job authentication |
| `ADMIN_X_USERNAMES` | Auth | Comma-separated admin X handles |
| `CONSOLE_ADMIN_EMAIL` / `PASSWORD` | Auth | Admin credentials login |
| `INVITE_CODES` | Auth | Valid invite codes |
| `NEXT_PUBLIC_BASE_DOMAIN` | Routing | Base domain for subdomains |
