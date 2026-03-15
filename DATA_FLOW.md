# RareImagery — Data Flow Reference

End-to-end traces for every major operation. Each flow shows the complete data journey from user action through all system boundaries.

---

## 1. X Login + Profile Auto-Provision

User authenticates via X and their profile is automatically synced to Drupal.

```
Browser: "Sign in with X" button
  │
  ▼
NextAuth TwitterProvider (X OAuth 2.0 PKCE)
  │ → Redirects to X authorization page
  │ → User grants access
  │ → X returns access_token, providerAccountId
  │
  ▼
NextAuth JWT Callback (src/lib/auth.ts:116)
  │ → Extracts: xUsername, xId, xImage, xBannerUrl, xAccessToken
  │ → Role check: ADMIN_X_USERNAMES.includes(xUsername) ? "admin" : "creator"
  │
  ├── Check if profile exists (fire-and-forget async):
  │     │
  │     ▼
  │   findProfileByUsername(xUsername) → GET /jsonapi/node/creator_x_profile?filter[field_x_username]=X
  │     │
  │     ├── If NOT found:
  │     │     POST /jsonapi/node/creator_x_profile
  │     │     { title: "X X Profile", field_x_username: X, field_store_theme: "xai3" }
  │     │
  │     ▼
  │   syncXDataToDrupal(accessToken, xId, xUsername)
  │     │
  │     ▼
  │   fetchXData(accessToken, userId) → X API v2
  │     ├── GET /users/{userId}?user.fields=name,username,description,profile_image_url,...
  │     ├── GET /users/{userId}/tweets?max_results=10&tweet.fields=public_metrics,created_at,...
  │     └── GET /users/{userId}/followers?max_results=20&user.fields=public_metrics,...
  │     │
  │     ▼ Compute metrics:
  │     engagementScore, avgLikes, avgRetweets, avgViews,
  │     extractThemes(), estimatePostingFrequency()
  │     │
  │     ▼
  │   patchProfile(uuid, attributes) → PATCH /jsonapi/node/creator_x_profile/{uuid}
  │     { field_follower_count, field_bio_description, field_top_posts, field_top_followers, field_metrics }
  │     │
  │     ▼
  │   uploadImageToDrupal() × 2
  │     ├── Profile picture → POST /jsonapi/node/creator_x_profile/{uuid}/field_profile_picture
  │     └── Banner → POST /jsonapi/node/creator_x_profile/{uuid}/field_background_banner
  │
  ▼
JWT token set in session cookie (8-hour max age)
Browser receives session with: xUsername, xId, role, xAccessToken
```

**Auth methods used:** X OAuth 2.0 (user), Basic Auth (profile lookup), Cookie Auth (profile write)
**Errors:** All caught internally — login succeeds even if sync fails
**Source:** `src/lib/auth.ts`, `src/lib/x-import.ts`

---

## 2. Store Creation

Creator sets up a new store through the wizard.

```
Browser: StoreBuilderWizard form submission
  │ (storeName, slug, ownerEmail, currency, xUsername, bio, ...)
  │
  ▼
POST /api/stores/create (src/app/api/stores/create/route.ts)
  │
  ├── Validate: session exists (401 if not)
  ├── Validate: agreedToTerms === true
  ├── Validate: isValidSlug(slug) → /^[a-z0-9-]{3,30}$/ + not reserved
  ├── Validate: isSlugTaken(slug) → GET /jsonapi/commerce_store/online?filter[field_store_slug]=X
  │
  ▼ Create Drupal Store:
  POST /jsonapi/commerce_store/online (Cookie Auth)
  {
    name, field_store_slug, mail, timezone: "America/New_York",
    address: { country_code: "US", address_line1: "N/A", locality: "New York", ... },
    field_store_status: "pending",
    relationships: { default_currency → UUID 7be59a35-... (USD) }
  }
  │
  ▼ Create X Profile + Link to Store:
  POST /jsonapi/node/creator_x_profile (Cookie Auth)
  {
    title: "X X Profile", field_x_username,
    field_bio_description, field_follower_count, field_metrics, ...,
    relationships: { field_linked_store → storeId }
  }
  │
  ├── Fire-and-forget: notifyAdminNewStore() → Email to admin
  │
  ▼
Response: { success, storeId, storeDrupalId, profileNodeId, slug, url }
```

**Status flow:** `pending` → admin reviews → `approved` (or `rejected`)
**Source:** `src/app/api/stores/create/route.ts`

---

## 3. Store Setup Payment (Stripe)

Creator pays $11 ($5 setup + $6 first month) to activate store.

```
Browser: "Launch Store" button
  │
  ▼
POST /api/checkout (src/app/api/checkout/route.ts)
  │ { storeSlug, xUsername }
  │
  ▼
Stripe: checkout.sessions.create
  { mode: "payment", unit_amount: 1100, metadata: { storeSlug, xUsername, type: "store_setup" } }
  │
  ▼
Browser: Redirect to Stripe Checkout page
  │ → Customer enters card details → Pays $11
  │
  ▼
Stripe webhook → POST /api/webhooks/stripe
  │ event.type === "checkout.session.completed"
  │ metadata.type === "store_setup"
  │
  ├── createDrupalStore(storeSlug, storeName) → POST /jsonapi/commerce_store/online
  ├── findXProfile(xUsername) → GET /jsonapi/node/creator_x_profile?filter=...
  ├── linkProfileToStore(profileId, storeId) → PATCH /jsonapi/node/creator_x_profile/{id}
  └── createMonthlySubscription(customerId, storeSlug) → Stripe subscription ($6/month)
  │
  ▼
Browser: Redirect to /console/upgrade-success
```

**Subscription lifecycle:**
- `invoice.payment_failed` → Stripe auto-retries, store stays active
- `customer.subscription.deleted` → `disableStore()` sets `field_store_status: "suspended"`

**Source:** `src/app/api/checkout/route.ts`, `src/app/api/webhooks/stripe/route.ts`

---

## 4. Product Listing (with Fee Gate)

Creator adds a product, with a $0.05 fee after 50 listings.

```
Browser: ProductManager form → POST /api/stores/products
  │ { title, description, price, storeId, productType, imageUrl, subscriberOnly, minTier }
  │
  ▼
countStoreProducts(storeId)
  │ → Queries all 3 product types in parallel
  │ → Uses meta.count or data.length
  │
  ├── If count < 50: createProductDirect()
  │     │
  │     ▼
  │   1. POST /jsonapi/commerce_product_variation/{bundle}
  │      { sku: "{storeId}-{timestamp}", price: { number, currency_code: "USD" } }
  │   2. POST /jsonapi/commerce_product/{bundle}
  │      { title, body, field_subscriber_only, relationships: { stores, variations } }
  │   3. Fire-and-forget: attachProductImage() → download URL → POST to field_images
  │     │
  │     ▼
  │   Response: { id, title, price, sku, product_type }
  │
  └── If count ≥ 50: Stripe listing fee
        │
        ▼
      stripe.checkout.sessions.create
        { unit_amount: 5 ($0.05), metadata: { type: "product_listing", title, description, price, store_id, ... } }
        │
        ▼
      Response: { requiresPayment: true, checkoutUrl, productCount, freeLimit: 50 }
        │
        ▼
      Browser: Redirect to Stripe → Pay $0.05
        │
        ▼
      Webhook: checkout.session.completed (type=product_listing)
        → createProductFromMetadata(session.metadata)
        → Creates product same as "direct" path above
```

**Source:** `src/app/api/stores/products/route.ts`

---

## 5. Product Purchase (Customer Checkout)

Customer buys products from a creator's store.

```
Browser: AddToCartBlock → Cart FAB → Checkout button
  │
  ▼
POST /api/checkout/product (src/app/api/checkout/product/route.ts)
  │ { items: [{productId, variationId, title, price, quantity}], storeId, buyerXId, sellerXId }
  │
  ▼
getPaymentProvider() → StripeProvider (or XMoneyProvider when available)
  │
  ▼
StripeProvider.createCheckout()
  │ → Convert items to Stripe line_items
  │ → Calculate platform fee: round(subtotal * 0.029) + 30 cents
  │ → Add "Platform processing fee" line item
  │ → stripe.checkout.sessions.create({ mode: "payment", ... })
  │
  ▼
Response: { checkoutUrl, paymentId, provider: "stripe" }
  │
  ▼
Browser: Redirect to Stripe Checkout → Customer pays
  │
  ▼
Stripe webhook → POST /api/webhooks/stripe
  │ → Order created in Drupal Commerce
  │ → notifyNewSale() → Email + SMS to store owner
```

**Source:** `src/app/api/checkout/product/route.ts`, `src/lib/payments.ts`

---

## 6. Follow / Unfollow

One store follows another through the social layer.

```
Browser: FollowButton click
  │
  ▼
POST /api/social/follow (follow) or DELETE (unfollow)
  │ { targetXUsername }
  │
  ▼ (Follow):
  getStoreByXUsername(targetXUsername) → resolve store UUID
  checkFollowStatus(followerStoreId, targetStoreId)
    → GET /jsonapi/flagging/follow_creator?filter[entity_id]=target&filter[flag_id]=follow_creator
    → Find flagging with matching field_follower_store_id
  │
  ├── If already following → 409 Conflict
  │
  ▼
  createFollow(followerStoreId, targetStoreId, targetStoreInternalId)
    │ → POST /jsonapi/flagging/follow_creator (Cookie Auth)
    │   { field_follower_store_id, field_follow_source: "rareimagery",
    │     relationships: { flagged_entity → target store, flag_id → "follow_creator" } }
    │
    ├── updateFollowerCount(targetStoreId, +1)
    │     GET store → current count → PATCH { field_follower_count: max(0, current + 1) }
    └── updateFollowingCount(followerStoreId, +1)
          GET store → current count → PATCH { field_following_count: max(0, current + 1) }
  │
  ▼ (Unfollow):
  removeFollow(flaggingId, followerStoreId, targetStoreId)
    │ → DELETE /jsonapi/flagging/follow_creator/{flaggingId}
    │
    ├── updateFollowerCount(targetStoreId, -1)
    └── updateFollowingCount(followerStoreId, -1)
```

**Source:** `src/lib/social.ts`

---

## 7. AI Page Builder

Creator generates custom page sections using Claude.

```
Browser: FloatingBuilder → prompt textarea → submit
  │
  ▼
POST /api/chat (src/app/api/chat/route.ts)
  │ { prompt, theme }
  │
  ├── Auth: getToken() → verify session
  ├── Rate limit: 10 requests/hour per user (in-memory Map)
  │
  ▼
Anthropic Claude API (streaming)
  │ Model: claude-haiku-4-5-20241022
  │ System prompt: BASE_RULES + THEME_PROMPTS[theme]
  │ → Returns React/JSX component code with Tailwind CSS
  │
  ▼
ReadableStream → Browser (streaming response)
  │
  ▼
LivePreview component
  │ → Babel standalone transforms JSX in browser
  │ → Renders in sandboxed iframe with:
  │     - React 19 UMD
  │     - Tailwind CSS CDN
  │     - Auto-resize via postMessage height detection
  │
  ▼ (Save):
POST /api/builds
  │ { label, code, storeSlug, published: false }
  │ → Saves to Drupal field_page_builds on store entity
  │
  ▼ (Publish):
PATCH /api/builds
  │ { buildId, published: true }
  │ → Build appears on public store page via StoreBuildRenderer
```

**Source:** `src/app/api/chat/route.ts`, `src/components/builder/`

---

## 8. Printful Product Sync

Store owner connects Printful for print-on-demand products.

```
Browser: PrintfulManager → enter API key → "Connect"
  │
  ▼
POST /api/printful/connect
  │ { apiKey, storeId }
  │
  ├── Verify key: GET https://api.printful.com/store (with Authorization header)
  ├── Save to Drupal: PATCH commerce_store → field_printful_api_key, field_printful_store_id
  │
  ▼
Response: { connected: true, printfulStoreName, printfulStoreId }

Browser: "Sync Products" button
  │
  ▼
POST /api/printful/sync
  │ { storeId }
  │
  ├── Fetch API key from Drupal store
  ├── GET https://api.printful.com/store/products
  │
  ▼ For each Printful product:
  ├── Check: already synced? (filter by field_printful_product_id)
  │     → If yes: skip
  │
  ├── 1. POST /jsonapi/commerce_product_variation--printful
  │      { sku, price: { number: retailPrice }, field_printful_variant_id }
  │
  ├── 2. POST /jsonapi/commerce_product--printful
  │      { title, field_printful_product_id, relationships: { stores, variations } }
  │
  └── 3. Fire-and-forget: upload thumbnail image
  │
  ▼
Response: { synced: N, skipped: M, total: N+M }
```

**Order fulfillment** (Drupal-side):
```
Customer order placed → PrintfulOrderSubscriber (Drupal event)
  → POST to Printful API with order details
  → Printful prints & ships
  → Printful webhook → PrintfulWebhookController → order status update
```

**Source:** `src/app/api/printful/connect/route.ts`, `src/app/api/printful/sync/route.ts`

---

## 9. Theme Selection

Creator changes their store's visual theme.

```
Browser: ThemeSelector radio buttons → "Save Theme"
  │
  ▼
POST /api/stores/select-theme (src/app/api/stores/select-theme/route.ts)
  │ { theme, profileId }
  │
  ├── Validate: theme ∈ ["xai3", "default", "minimal", "neon", "editorial", "myspace"]
  │
  ▼
PATCH /jsonapi/node/creator_x_profile/{profileId}
  │ { field_store_theme: theme }
  │
  ▼
Response: { success: true, theme }
  │
  ▼
Store page /stores/[creator]:
  │ getCreatorProfile(username) → profile.store_theme
  │ → Renders matching theme component:
  │     "xai3" → Xai3Theme
  │     "minimal" → MinimalTheme
  │     "neon" → NeonTheme
  │     "editorial" → EditorialTheme
  │     "myspace" → MySpaceTheme
  │     "xmimic" → XMimicTheme
```

**Source:** `src/app/api/stores/select-theme/route.ts`, `src/app/stores/[creator]/page.tsx`

---

## 10. Health Agent Cron

Automated system health checks running every 30 minutes on Vercel.

```
Vercel Cron (*/30 * * * *) → GET /api/cron/frontend-agent
  │
  ├── Verify: CRON_SECRET header matches
  │
  ▼
runAgent() (src/lib/frontend-agent.ts)
  │
  ├── 1. checkDrupalReachability()
  │      GET {DRUPAL_API}/jsonapi (10s timeout)
  │      → If unreachable: CRITICAL issue, return immediately
  │
  ├── 2. getAllCreatorProfiles()
  │      → Fetch all profiles from Drupal
  │
  ├── 3. For each profile (batches of 5):
  │      ├── checkStorePage(username)
  │      │     GET {BASE_URL}/stores/{username} (15s timeout)
  │      │     → If status ≥ 400 and store is "approved":
  │      │         revalidatePath(`/stores/${username}`)
  │      │         Record as issue
  │      │
  │      └── checkStoreProducts(profile)
  │            getStoreProducts(linked_store_id) → count
  │
  ├── 4. Check critical API routes:
  │      GET {BASE_URL}/api/social/picks (10s timeout)
  │      → If status ≥ 500: record issue
  │
  ├── 5. Classify status:
  │      CRITICAL issues → "critical"
  │      Any issues → "degraded"
  │      No issues → "healthy"
  │
  └── 6. If critical: sendEmail() to admin with full JSON report
  │
  ▼
Response: HealthReport { timestamp, status, storeChecks, apiRouteChecks, issues, ... }
```

**Source:** `src/lib/frontend-agent.ts`, `frontend/vercel.json`

---

## 11. Grok Profile Enhancement

AI-powered profile analysis during store setup.

```
Browser: StoreBuilderWizard "Enhance with AI" button
  │
  ▼
POST /api/stores/enhance-profile
  │ { xAccessToken, xId, xUsername }
  │
  ├── fetchXData(xAccessToken, xId) → X API v2
  │     → Returns: posts, followers, metrics, bio
  │
  ▼
enhanceCreatorProfile(xData) (src/lib/grok.ts)
  │
  ├── Prepare input: top 6 posts (text + metrics)
  │
  ▼
xAI Grok API: POST https://api.x.ai/v1/chat/completions
  │ { model: "grok-3", temperature: 0.5, response_format: { type: "json_object" } }
  │ → System: "You are an AI assistant helping X creators set up their storefront..."
  │ → User: profile summary + available themes + JSON schema
  │
  ▼
Parse response:
  │ → storeBio (AI-written 2-3 sentence bio)
  │ → suggestedProducts (3-5 product ideas)
  │ → recommendedTheme (validated against allowed list)
  │ → topThemes (5 content themes)
  │ → audienceSentiment
  │
  ▼
Response: { xData, enhancements: GrokEnhancements }
```

**Source:** `src/lib/grok.ts`, `src/lib/x-import.ts`

---

## 12. Store Page Rendering

How a creator's public store page is built on each request.

```
Browser: GET /stores/{creator}
  │
  ▼
Next.js App Router: src/app/stores/[creator]/page.tsx
  │
  ├── fetchCreatorData(handle) → parallel:
  │     ├── getCreatorProfile(handle) → GET /jsonapi/node/creator_x_profile?filter=...&include=...
  │     └── getProductsByStoreSlug(handle) → GET /jsonapi/commerce_store/online?filter=... → getStoreProducts(id)
  │
  ├── mapCreatorProfile(node, included):
  │     → Resolve profile_picture from file--file includes
  │     → Resolve banner from file--file includes
  │     → Parse field_top_posts: JSON strings → TopPost[]
  │     → Parse field_top_followers: JSON strings → TopFollower[]
  │     → Parse field_metrics: JSON string → Metrics
  │     → Resolve linked_store from commerce_store includes
  │     → Parse subscription_tiers from store's field_subscription_tiers
  │
  ├── Check store_status:
  │     → "approved": render store
  │     → "pending": show pending message
  │     → "rejected"/"suspended": show unavailable
  │
  ▼ Render based on profile.store_theme:
  <StoreNav />
  <ThemeComponent
    profile={profile}
    products={products}
    pfp, banner, topPosts, topFollowers, metrics, bio, ...
  />
  <StoreBuildRenderer builds={publishedBuilds} />  // AI-generated sections
  <BuilderGate />  // Shows builder if logged in as owner
```

**Caching:** 60-second revalidation on Drupal reads + Vercel CDN `s-maxage=60, stale-while-revalidate=300`

**Source:** `src/app/stores/[creator]/page.tsx`, `src/lib/drupal.ts`
