# RareImagery — API Route Catalog

Complete reference for all 38 Next.js API routes. Each entry lists the HTTP method, auth requirement, request/response shapes, and key implementation details.

## Auth Legend

| Symbol | Meaning |
|---|---|
| `[JWT]` | NextAuth JWT token via `getToken()` — requires active session |
| `[Session]` | Server session via `getServerSession()` |
| `[Admin]` | JWT + `token.role === "admin"` check |
| `[Webhook]` | Stripe signature verification (`stripe.webhooks.constructEvent`) |
| `[Cron]` | Vercel `CRON_SECRET` Bearer token |
| `[Public]` | No authentication required |

---

## Store Management

### POST /api/stores/create `[Session]`

Creates a Drupal commerce store + creator X profile.

```
Request:  { storeName, slug, ownerEmail, currency, agreedToTerms, xUsername, bioDescription?, followerCount?, topPosts?, topFollowers?, metrics?, myspace*? }
Response: { success, storeId, storeDrupalId, profileNodeId, slug, url }
Errors:   400 (terms not agreed, invalid slug), 409 (slug taken), 500 (Drupal error)
```

**Details:** Validates slug via `isValidSlug()`, checks uniqueness, creates store with `field_store_status: "pending"`, links X profile, fires admin notification email.

**Source:** `src/app/api/stores/create/route.ts`

---

### POST /api/stores/provision `[JWT]`

Creates creator X profile for new users on first login. Lighter than `/create`.

```
Request:  { xUsername }
Response: { success, profileId, alreadyExisted, url }
```

**Source:** `src/app/api/stores/provision/route.ts`

---

### PATCH /api/stores/approve `[Admin]`

Admin approves, rejects, or pends a store.

```
Request:  { storeId, status: "approved" | "rejected" | "pending" }
Response: { success }
Errors:   401 (not admin), 404 (store not found)
```

**Details:** Sends email + SMS notification to store owner. Looks up owner phone via `creator_x_profile → uid → user.field_phone_number`.

**Source:** `src/app/api/stores/approve/route.ts`

---

### PATCH /api/stores/select-theme `[JWT]`

Updates creator profile's visual theme.

```
Request:  { theme: "xai3"|"default"|"minimal"|"neon"|"editorial"|"myspace", profileId }
Response: { success, theme }
Errors:   400 (invalid theme)
```

**Source:** `src/app/api/stores/select-theme/route.ts`

---

### PATCH /api/stores/theme `[JWT]`

Updates MySpace theme configuration (JSON).

```
Request:  { profileId, themeConfig: Record<string, any> }
Response: { success }
```

**Source:** `src/app/api/stores/theme/route.ts`

---

### POST /api/stores/generate-theme `[JWT]`

AI-generates a complete MySpace-era theme config via Grok.

```
Request:  { profileId, quizResponses, xData }
Response: { themeConfig: { background, profileSection, pfp, top8, music, ... } }
```

**Source:** `src/app/api/stores/generate-theme/route.ts`

---

### POST /api/stores/theme-chat `[JWT]`

Interactive Grok chat for iterative MySpace theme building.

```
Request:  { messages, currentSection, profileId }
Response: streaming ReadableStream (theme section code)
```

**Source:** `src/app/api/stores/theme-chat/route.ts`

---

## Products

### GET /api/stores/products `[Public]`

Lists all products for a store across all types.

```
Query:    ?storeId={drupal_internal_store_id}
Response: { products: [{ id, drupal_id, title, description, price, currency, sku, image_url, product_type, variation_id, subscriber_only, min_tier, status }] }
```

**Details:** Queries `default`, `digital_download`, `crafts` types in parallel. Resolves variation prices and file images from JSON:API includes.

---

### POST /api/stores/products `[JWT]`

Creates a new product. Charges $0.05 listing fee if store has ≥50 products.

```
Request:  { title, description?, price, storeId, productType?, imageUrl?, subscriberOnly?, minTier? }

Response (under 50): { id, title, price, sku, product_type }
Response (over 50):  { requiresPayment, checkoutUrl, sessionId, productCount, freeLimit: 50 }
```

**Details:** Creates variation first (with auto-generated SKU), then product, then attaches image (fire-and-forget). Product types map: `default→default`, `digital_download→digital_download`, `physical_custom→crafts`.

---

### PATCH /api/stores/products `[JWT]`

Updates an existing product's attributes and/or variation price.

```
Request:  { productId, productType?, variationId?, title?, description?, price?, imageUrl?, subscriberOnly?, minTier? }
Response: { updated: true }
```

---

### DELETE /api/stores/products `[JWT]`

Deletes a product.

```
Request:  { productId, productType? }
Response: { deleted: true }
```

**Source:** `src/app/api/stores/products/route.ts`

---

## X Data Import

### POST /api/stores/import-x-data `[JWT]`

Fetches fresh X data and syncs to Drupal profile.

```
Request:  { xAccessToken, xId, xUsername }
Response: { success, imported: { followerCount, postCount, topFollowerCount, metrics } }
```

**Details:** Calls `fetchXData()` → X API v2, then `patchProfile()` → Drupal PATCH, then uploads profile picture + banner images.

**Source:** `src/app/api/stores/import-x-data/route.ts`

---

### POST /api/stores/enhance-profile `[JWT]`

Fetches X data + enhances with Grok AI.

```
Request:  { xAccessToken, xId, xUsername }
Response: { xData: XImportData, enhancements: { storeBio, suggestedProducts, recommendedTheme, topThemes, audienceSentiment } }
```

**Source:** `src/app/api/stores/enhance-profile/route.ts`

---

## Social

### POST /api/social/follow `[JWT]`

Toggle follow/unfollow a store.

```
Request:  { targetXUsername, action?: "follow" | "unfollow" }
Response: { success, isFollowing, flaggingId? }
Errors:   400 (no own store), 409 (already following)
```

**Details:** Creates/deletes Drupal Flag `follow_creator` flagging. Updates denormalized follower/following counts on both stores.

**Source:** `src/app/api/social/follow/route.ts`

---

### GET /api/social/followers `[Public]`

Lists followers or following for a store.

```
Query:    ?storeId={uuid}&type=followers|following  OR  ?xUsername={handle}&type=...
Response: { followers|following: [{ storeId, storeName, storeSlug, xUsername, profilePictureUrl, followerCount, isMutual }] }
```

**Source:** `src/app/api/social/followers/route.ts`

---

### GET /api/social/picks `[Public]` | POST `[JWT]`

Featured store picks.

```
GET Response:  { picks: [{ storeId, storeName, storeSlug, xUsername, ... }] }
POST Request:  { picks: [...] }
POST Response: { success }
```

**Source:** `src/app/api/social/picks/route.ts`

---

### GET /api/social/shoutouts `[Public]` | POST `[JWT]`

Community shoutouts between stores.

```
GET Query:    ?targetStoreId={uuid}
GET Response: { shoutouts: [...] }
POST Request: { targetStoreId, message }
```

**Source:** `src/app/api/social/shoutouts/route.ts`

---

### GET /api/social/seed-from-x `[JWT]`

Cross-references X following list with existing RareImagery stores.

```
Query:    (uses session X tokens)
Response: { matched: FollowerInfo[], total: number }
```

**Details:** Fetches X following handles, queries each against Drupal creator profiles, returns matched stores.

**Source:** `src/app/api/social/seed-from-x/route.ts`

---

## Commerce

### GET /api/orders `[JWT]`

Lists orders for a store with filtering.

```
Query:    ?storeId={id}&state=pending|completed|cancelled&page=0
Response: { orders: [...], total, page, perPage: 20 }
```

**Details:** Queries `commerce_order/default` with includes for order_items and billing_profile.

**Source:** `src/app/api/orders/route.ts`

---

### GET /api/orders/[id] `[JWT]`

Fetches a single order with full details.

```
Response: { order: { id, state, items, billingAddress, total, created } }
```

**Source:** `src/app/api/orders/[id]/route.ts`

---

### GET /api/shipping `[JWT]`

Lists shipments for a store.

```
Query:    ?storeId={id}&state=pending|ready|shipped|delivered|cancelled&page=0
Response: { shipments: [...], total, page, perPage: 20 }
```

**Source:** `src/app/api/shipping/route.ts`

---

### GET /api/accounting `[JWT]`

Revenue analytics and fee breakdown.

```
Query:    ?storeId={id}&period=30|90|365
Response: { gross, net, fees, orderCount, transactions: [...], dailyRevenue: [...] }
```

**Details:** Platform fee: 2.9% + $0.30/order.

**Source:** `src/app/api/accounting/route.ts`

---

## Checkout & Payments

### POST /api/checkout `[JWT]`

Creates Stripe checkout for store setup ($5 + $6 first month = $11).

```
Request:  { storeSlug, xUsername }
Response: { url: "https://checkout.stripe.com/..." }
```

**Source:** `src/app/api/checkout/route.ts`

---

### POST /api/checkout/product `[JWT]`

Creates checkout session for product purchases via payment provider.

```
Request:  { items: CheckoutItem[], storeId, buyerXId?, sellerXId?, successUrl, cancelUrl }
Response: { checkoutUrl, paymentId, provider: "stripe"|"xmoney" }
```

**Details:** Uses `getPaymentProvider()` abstraction. Adds platform processing fee (2.9% + $0.30).

**Source:** `src/app/api/checkout/product/route.ts`

---

## Subscriptions

### POST /api/subscriptions/checkout `[JWT]`

Creates subscription checkout for a creator's fan tier.

```
Request:  { tierId, tierName, amount, currency, interval, storeId, sellerXId }
Response: { checkoutUrl, subscriptionId, provider }
```

**Source:** `src/app/api/subscriptions/checkout/route.ts`

---

### GET /api/subscriptions/status `[JWT]`

Checks if user has active subscription to a store.

```
Query:    ?storeId={uuid}
Response: { active, tierName?, expiresAt? }
```

**Source:** `src/app/api/subscriptions/status/route.ts`

---

### GET /api/subscriptions/tiers `[Public]` | POST `[Session]`

Manage subscription tier definitions.

```
GET Query:    ?storeId={uuid}
GET Response: { tiers: SubscriptionTier[] }
POST Request: { storeId, tiers: SubscriptionTier[] }
```

**Source:** `src/app/api/subscriptions/tiers/route.ts`

---

### GET /api/x-subscription `[Public]` | POST `[Session]`

X creator subscription tier management.

```
GET Query:    ?profileId={uuid}
GET Response: { tier: string | null }
POST Request: { profileId, tier }
```

**Source:** `src/app/api/x-subscription/route.ts`

---

## Printful

### POST /api/printful/connect `[Drupal Auth]`

Verifies Printful API key and saves to store.

```
Request:  { apiKey, storeId }
Response: { connected, printfulStoreName, printfulStoreId }
```

**Source:** `src/app/api/printful/connect/route.ts`

---

### POST /api/printful/sync `[Drupal Auth]`

Syncs Printful catalog to Drupal Commerce products.

```
Request:  { storeId }
Response: { synced, skipped, total }
```

**Source:** `src/app/api/printful/sync/route.ts`

---

### GET /api/printful/status `[Public]`

Checks Printful connection status.

```
Query:    ?storeId={uuid}
Response: { connected, printfulStoreId? }
```

**Source:** `src/app/api/printful/status/route.ts`

---

### GET /api/printful/products `[Public]`

Lists Printful-type products for a store.

```
Query:    ?storeId={drupal_internal_id}
Response: { products: [...] }
```

**Source:** `src/app/api/printful/products/route.ts`

---

## AI & Builder

### POST /api/chat `[JWT]`

Rate-limited Grok chat for component generation.

```
Request:  { prompt, theme }
Response: ReadableStream (streaming JSX code)
Errors:   429 (rate limit: 10/hour)
```

**Details:** Model: `grok-3-mini`. System prompt includes BASE_RULES + theme-specific styling instructions.

**Source:** `src/app/api/chat/route.ts`

---

### GET /api/builds `[JWT]` | POST `[JWT]`

Manage saved page builder builds.

```
GET Query:    ?storeSlug={slug}
GET Response: { builds: [{ id, label, code, published, createdAt }] }
POST Request: { label, code, storeSlug, published? }
POST Response: { buildId }
```

**Details:** Max 20 builds per store.

**Source:** `src/app/api/builds/route.ts`

---

## Webhooks & Cron

### POST /api/webhooks/stripe `[Webhook]`

Handles Stripe webhook events.

**Events handled:**
| Event | Action |
|---|---|
| `checkout.session.completed` (type=store_setup) | Create Drupal store + link profile + start $6/month subscription |
| `checkout.session.completed` (type=product_listing) | Call `createProductFromMetadata()` |
| `customer.subscription.deleted` | Set store status to "suspended" |
| `invoice.payment_failed` | Log warning (Stripe auto-retries) |

**Source:** `src/app/api/webhooks/stripe/route.ts`

---

### GET /api/cron/frontend-agent `[Cron]`

System health monitoring. Runs every 30 minutes via Vercel cron.

```
Response: HealthReport { timestamp, status, storeChecks, apiRouteChecks, issues, ... }
Status:   200 (healthy/degraded), 503 (critical)
```

**Source:** `src/app/api/cron/frontend-agent/route.ts`

---

## Configuration & Proxy

### GET /api/app-config/[slug] `[Public]`

Fetches or auto-generates app config for a store.

```
Response: { config: Record<string, any> }
Cache:    300 seconds
```

**Source:** `src/app/api/app-config/[slug]/route.ts`

---

### GET /api/proxy/x-feed/[userId] `[Public]`

Server-side X feed proxy via Grok API.

```
Query:    ?excludeReplies=true
Response: { posts: [...] }
Cache:    5-minute in-memory cache
```

**Source:** `src/app/api/proxy/x-feed/[userId]/route.ts`

---

### POST /api/invite/verify `[Public]`

Verifies an invite code.

```
Request:  { code }
Response: { valid, remaining? }
```

**Source:** `src/app/api/invite/verify/route.ts`

---

### GET /api/notifications/preferences `[JWT]` | PATCH `[JWT]`

User notification settings.

```
GET Response: { phone?, smsAlertLevel?, notificationChannel? }
PATCH Request: { phone?, smsAlertLevel?, notificationChannel? }
```

**Source:** `src/app/api/notifications/preferences/route.ts`

---

### GET /api/auth/[...nextauth] | POST `[NextAuth]`

NextAuth.js handler for X OAuth 2.0 and credentials authentication.

**Providers:** TwitterProvider (X), CredentialsProvider (admin email + Drupal users)
**Session:** JWT strategy, 8-hour max age

**Source:** `src/app/api/auth/[...nextauth]/route.ts` → `src/lib/auth.ts`
