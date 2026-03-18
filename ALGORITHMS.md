# RareImagery — Algorithms & Business Logic Reference

Every computed value, formula, and decision heuristic in the platform. Each entry cites its source file.

---

## 1. Engagement Score

Measures a creator's X engagement relative to their audience size.

```
avgEngagements = (totalLikes + totalRetweets) / tweetCount
engagementScore = min(100, round((avgEngagements / followerCount) * 10000))
```

- If `followerCount` is 0 → score is 0
- If `tweetCount` is 0 → denominator defaults to 1
- Score range: 0–100 (integer)
- Inputs: last 10 tweets fetched from X API v2

**Source:** `frontend/src/lib/x-import.ts` lines 241–255

---

## 2. Theme Extraction from Tweets

Identifies content themes from a creator's recent tweets using two signals:

### Hashtags
- Regex: `/#[\w]+/g`
- Lowercased, counted by frequency

### Significant Words
- Strip URLs: `https?:\/\/\S+` removed
- Strip non-alpha: `[^a-zA-Z\s]` removed
- Lowercase, split on whitespace
- Filter: words < 4 chars removed
- Filter: 93 stop words removed (the, a, is, are, have, has, do, to, of, in, for, on, with, at, by, from, ... rt, amp, https, http, co)

### Output
- Merge hashtag + word frequencies
- Sort descending by count
- Return top 5 (configurable via `limit` param)

**Source:** `frontend/src/lib/x-import.ts` lines 77–121 (`extractThemes`)

---

## 3. Posting Frequency Estimation

Classifies how often a creator posts based on tweet timestamps.

```
spanDays = (newest_tweet_date - oldest_tweet_date) / 86400000
tweetsPerDay = tweetCount / max(spanDays, 1)
```

| tweetsPerDay | Classification |
|---|---|
| ≥ 3.0 | "Several times a day" |
| ≥ 0.8 | "Daily" |
| ≥ 0.3 | "Several times a week" |
| ≥ 0.1 | "Weekly" |
| < 0.1 | "Occasionally" |
| < 2 tweets | "Unknown" |

Special case: if `spanDays === 0` (all tweets same day) → "Several times a day"

**Source:** `frontend/src/lib/x-import.ts` lines 126–145 (`estimatePostingFrequency`)

---

## 4. Slug Validation

Validates store subdomain slugs before creation.

```
Pattern: /^[a-z0-9-]{3,30}$/
```

**Rules:**
- Lowercase letters, numbers, hyphens only
- 3–30 characters
- Must NOT be a reserved slug

**14 Reserved Slugs:**
`console`, `admin`, `api`, `www`, `app`, `mail`, `support`, `help`, `blog`, `shop`, `store`, `login`, `signup`, `dashboard`

**Source:** `frontend/src/lib/slugs.ts` lines 1–30

---

## 5. Product Listing Fee Gate

Controls when creators pay for product listings.

```
FREE_LISTING_LIMIT = 50
LISTING_FEE_CENTS = 5  // $0.05

if (storeProductCount >= FREE_LISTING_LIMIT) {
  → Redirect to Stripe checkout ($0.05)
  → On payment: webhook calls createProductFromMetadata()
} else {
  → Create product directly (no fee)
}
```

**Count spans** these product types: `default`, `digital_download`, `crafts`

**Stripe metadata stored on checkout session:**
- `type: "product_listing"`, `title`, `description`, `price`, `store_id`, `product_type`, `image_url`, `subscriber_only`, `min_tier`

**Source:** `frontend/src/app/api/stores/products/route.ts` lines 132–248, `frontend/src/lib/payments.ts` line 9

---

## 6. Platform Processing Fee (Checkout)

Added to every product purchase as a visible line item.

```
feeCents = round(subtotalCents * 0.029) + 30
```

- 2.9% of order subtotal + $0.30 flat fee
- Displayed as "Platform processing fee" in Stripe checkout
- Applied in `StripeProvider.createCheckout()`

**Source:** `frontend/src/lib/payments.ts` lines 186–201

---

## 7. Store Setup Pricing

One-time + recurring fees for store creation.

```
Setup checkout = $11 total
  → $5 one-time setup fee
  → $6 first month
  → Then $6/month recurring (Stripe subscription auto-created in webhook)
```

**Subscription cancellation** → store status set to `"suspended"` via `disableStore()`

**Source:** `frontend/src/app/api/checkout/route.ts` lines 22–46, `frontend/src/app/api/webhooks/stripe/route.ts` lines 108–164

---

## 8. Follower Count Denormalization

Follow/unfollow operations update denormalized counts on stores to avoid expensive queries.

```
// On follow:
updateFollowerCount(targetStoreId, +1)
updateFollowingCount(followerStoreId, +1)

// On unfollow:
updateFollowerCount(targetStoreId, -1)
updateFollowingCount(followerStoreId, -1)

// Update logic:
newCount = Math.max(0, currentCount + delta)
```

- `Math.max(0, ...)` prevents negative counts from race conditions
- Reads current value, applies delta, PATCHes back
- Fields: `field_follower_count`, `field_following_count` on `commerce_store--online`
- Errors are caught and logged (non-blocking)

**Source:** `frontend/src/lib/social.ts` lines 203–280

---

## 9. Mutual Follow Detection

Checks if two stores follow each other (bidirectional relationship).

```
// For each follower of targetStore:
reverseQuery = flagging/follow_creator?filter[flagged_entity.id]={followerStoreId}&filter[field_follower_store_id]={targetStoreId}
isMutual = (reverseQuery.data.length > 0)
```

- Uses Drupal Flag module's `follow_creator` flagging entity
- Each flagging stores `field_follower_store_id` (who followed) and `flagged_entity` (who was followed)
- Reverse query checks if target also follows the follower

**Source:** `frontend/src/lib/social.ts` lines 322–331

---

## 10. Health Check Status Classification

Frontend agent categorizes system health into three states.

```
criticalCount = issues.filter(i => i.startsWith("CRITICAL")).length

if (criticalCount > 0) → "critical"
else if (issues.length > 0) → "degraded"
else → "healthy"
```

**Triggers for "critical":**
- Drupal API unreachable (10s timeout)
- Failed to fetch creator profiles

**Triggers for "degraded":**
- Any approved store page returning non-200
- Any critical API route returning 500+

**On critical:** sends admin email with full JSON health report (fire-and-forget)

**Source:** `frontend/src/lib/frontend-agent.ts` lines 257–289

---

## 11. Drupal Session Cache (Write Auth)

Cookie-based auth for Drupal write operations with in-memory caching.

```
TTL = 10 minutes (600,000 ms)

if (_sessionCache && _sessionCache.expiresAt > Date.now()) {
  → Return cached {cookie, csrfToken}
}

// Otherwise:
1. POST /user/login?_format=json {name, pass}
2. Extract SESS* cookie from Set-Cookie header
   → Primary: getSetCookie() → find cookie starting with "SESS" or "SSESS"
   → Fallback: regex /(S?SESS[^=]+=[^;]+)/ on raw header
3. GET /session/token with Cookie header → CSRF token
4. Cache with expiresAt = now + 10 min
```

**Used by:** `drupalWriteHeaders()` for all POST/PATCH/DELETE operations

**Source:** `frontend/src/lib/drupal.ts` lines 7–75

---

## 12. Admin Role Assignment

Determines user role from X username on login.

```
adminXUsernames = process.env.ADMIN_X_USERNAMES
  .toLowerCase()
  .split(",")
  .map(s => s.trim())
  .filter(Boolean)

xUser = token.xUsername.toLowerCase()
role = adminXUsernames.includes(xUser) ? "admin" : "creator"
```

**Credentials login roles:**
- Email matches `CONSOLE_ADMIN_EMAIL` → `"admin"`
- Drupal user lookup succeeds → `"store_owner"`

**Source:** `frontend/src/lib/auth.ts` lines 135–137

---

## 13. Product SKU Generation

Auto-generates SKUs for new products.

```
sku = `${storeId}-${Date.now()}`
```

- `storeId` = Drupal internal store ID (integer)
- `Date.now()` = Unix timestamp in milliseconds
- Ensures uniqueness per store

**Source:** `frontend/src/app/api/stores/products/route.ts` line 263

---

## 14. Slug Generation (Product URLs)

Converts product titles to URL-safe slugs.

```
slugify(title):
  1. .toLowerCase()
  2. .replace(/[^a-z0-9]+/g, "-")   // non-alphanumeric → hyphens
  3. .replace(/^-|-$/g, "")          // trim leading/trailing hyphens
```

**Source:** `frontend/src/lib/drupal.ts` lines 540–545

---

## 15. Profile Image URL Enhancement

X API returns `_normal` resolution images. Platform upgrades to 400x400.

```
profileImageUrl = user.profile_image_url.replace("_normal", "_400x400")
```

Applied to both creator profile pictures and follower profile pictures.

**Source:** `frontend/src/lib/x-import.ts` lines 173–175, 231–233

---

## 16. Top Followers Ranking

Selects the most notable followers from a creator's X audience.

```
1. Fetch 20 followers from X API v2
2. Map to {username, display_name, profile_image_url, follower_count, verified}
3. Sort by follower_count descending
4. Take top 8
```

**Source:** `frontend/src/lib/x-import.ts` lines 217–238

---

## 17. Payment Provider Resolution

Determines which payment provider to use at runtime.

```
if (XMONEY_API_KEY is set) → XMoneyProvider
else if (STRIPE_SECRET_KEY is set) → StripeProvider
else → throw Error("No payment provider configured")
```

- Providers are lazy singletons (`_xmoney`, `_stripe`)
- X Money is preferred when available (currently throws "not yet available")
- Both implement `PaymentProvider` interface

**Source:** `frontend/src/lib/payments.ts` lines 330–352

---

## 18. Grok Profile Enhancement

Uses xAI's Grok-3 to generate personalized store recommendations.

```
Input: creator's X username, bio, follower count, top 6 posts (text + metrics)
Model: grok-3
Temperature: 0.5
Response format: JSON object
Timeout: 20 seconds

Output:
- storeBio: polished 2-3 sentence store description
- suggestedProducts: 3-5 product ideas with name/description/category
- recommendedTheme: one of [default, minimal, neon, editorial, myspace]
- topThemes: top 5 content themes
- audienceSentiment: Very Positive | Positive | Neutral | Mixed
```

**Validation:** recommended theme must be in the allowed list, otherwise defaults to `"default"`

**Source:** `frontend/src/lib/grok.ts` lines 59–125

---

## 19. AI Page Builder Rate Limiting

Controls usage of the Grok-powered page builder.

```
RATE_LIMIT = 10 requests
RATE_WINDOW = 3,600,000 ms (1 hour)

Per-user tracking via in-memory Map keyed by user ID
Reset when window expires
```

**Model:** Grok (`grok-3-mini`)
**Streaming:** enabled (returns `ReadableStream`)
**Theme-specific system prompts** for each of the 6 themes

**Source:** `frontend/src/app/api/chat/route.ts` lines 7–9

---

## 20. Product Type Mapping

Maps UI product type names to Drupal Commerce bundle names.

```
TYPE_MAP = {
  default → "default"
  digital_download → "digital_download"
  physical_custom → "crafts"
}
```

**All queryable product types:** `default`, `clothing`, `digital_download`, `crafts`, `printful`

**Each type has specific JSON:API includes** (e.g., clothing includes `variations.field_variation_image`, `variations.field_color_swatch`)

**Source:** `frontend/src/app/api/stores/products/route.ts` lines 10–14, `frontend/src/lib/drupal.ts` lines 730–738

---

## 21. Data Revalidation Strategy

Controls how Next.js caches Drupal API responses.

| Context | Strategy | TTL |
|---|---|---|
| Creator profiles (read) | `{ next: { revalidate: 60 } }` | 60 seconds |
| Products (read) | `{ next: { revalidate: 60 } }` | 60 seconds |
| Stores (read) | `{ next: { revalidate: 60 } }` | 60 seconds |
| Product list (console) | `{ next: { revalidate: 0 } }` | No cache |
| Orders, shipments | `{ cache: "no-store" }` | No cache |
| Social (follows) | `{ cache: "no-store" }` | No cache |
| Vercel CDN | `s-maxage=60, stale-while-revalidate=300` | 60s fresh, 5m stale |

**Source:** `frontend/src/lib/drupal.ts` (various), `frontend/vercel.json`
