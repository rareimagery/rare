# Creator Mobile App — RareImagery.net

White-label iOS and Android apps for store owners. Each creator gets a branded app that surfaces their X posts and their RareImagery product catalog. The app config is a JSON file served from your Drupal backend — one file drives both platforms.

---

## Architecture Overview

```
creator.rareimagery.net (storefront)
        ↓
api.rareimagery.net/jsonapi/commerce_store/online/{storeId}/app-config
        ↓
app-config.json  ←  single source of truth for both platforms
        ↓
iOS App (Swift/SwiftUI)          Android App (Kotlin/Jetpack Compose)
  - reads app-config.json          - reads app-config.json
  - fetches X posts via xAI        - fetches X posts via xAI
  - fetches products via JSON:API  - fetches products via JSON:API
```

**Key principle:** One template app codebase per platform, parameterized entirely by `app-config.json`. You ship one iOS app and one Android app to the stores under RareImagery's developer accounts. Each creator gets their own branded build generated from their config. No per-creator app store account needed until they want full white-labeling.

---

## Two Tiers to Sell

### Tier 1 — Hosted App (~$X/month subscription)
- App lives in RareImagery's App Store / Play Store accounts
- Creator's name, avatar, colors, and content — but "RareImagery" appears in store listing
- Fastest to ship, lowest cost to creator
- Config-driven — no custom build per creator

### Tier 2 — White-Label App (one-time fee + annual)
- App published under creator's own Apple/Google developer accounts
- Custom bundle ID: `com.creatorhandle.app`
- Custom app store listing, screenshots, description
- Full brand ownership
- Requires a custom build per creator + their developer account credentials

---

## Data Sources the App Consumes

| Data | Source | Auth |
|------|--------|------|
| Creator profile & theme | `app-config.json` (Drupal) | Public (cached) |
| X posts / media | xAI Grok API → proxied via Next.js | Server-side API key |
| Products catalog | `api.rareimagery.net/jsonapi/commerce_product` | Public JSON:API |
| Product images | Drupal managed files CDN | Public |
| Cart / checkout | Creator's storefront URL (webview) | Session |

Checkout stays in a webview pointed at `creator.rareimagery.net/cart` — no need to rebuild a native checkout flow. This keeps PCI scope out of the app entirely.

---

## File: `app-config.json`

Served at:
```
GET api.rareimagery.net/app-config/{creatorSlug}
```

This endpoint reads the `field_app_config` JSON field from the creator's Commerce Store entity and returns it. Public, cacheable, CDN-friendly.

```json
{
  "schema_version": "1.0",
  "generated_at": "2025-01-15T10:30:00Z",

  "creator": {
    "slug": "marcellaink",
    "display_name": "Marcella Ink",
    "x_handle": "@marcellaink",
    "x_user_id": "1234567890",
    "bio": "Artist. Sticker maker. Chaotic good.",
    "avatar_url": "https://api.rareimagery.net/files/avatars/marcellaink.jpg",
    "banner_url": "https://api.rareimagery.net/files/banners/marcellaink.jpg",
    "storefront_url": "https://marcellaink.rareimagery.net",
    "store_id": "uuid-of-drupal-store-entity"
  },

  "app": {
    "bundle_id_ios": "net.rareimagery.marcellaink",
    "bundle_id_android": "net.rareimagery.marcellaink",
    "app_name": "Marcella Ink",
    "app_subtitle": "Art & Merch",
    "version": "1.0.0",
    "tier": "hosted"
  },

  "theme": {
    "preset": "y2k_pink",
    "colors": {
      "primary": "#FF1493",
      "secondary": "#9400D3",
      "accent": "#00FFFF",
      "background": "#0a0a0a",
      "surface": "#1a1a2e",
      "text_primary": "#FFFFFF",
      "text_secondary": "#AAAAAA",
      "tab_bar_background": "#0a0a0a",
      "tab_bar_active": "#FF1493",
      "tab_bar_inactive": "#555555"
    },
    "fonts": {
      "display": "system",
      "body": "system"
    },
    "dark_mode_only": true
  },

  "tabs": [
    {
      "id": "feed",
      "label": "Posts",
      "icon": "sparkles",
      "enabled": true,
      "order": 1
    },
    {
      "id": "shop",
      "label": "Shop",
      "icon": "bag",
      "enabled": true,
      "order": 2
    },
    {
      "id": "media",
      "label": "Media",
      "icon": "photo",
      "enabled": true,
      "order": 3
    },
    {
      "id": "profile",
      "label": "Profile",
      "icon": "person",
      "enabled": true,
      "order": 4
    }
  ],

  "feed": {
    "source": "x_posts",
    "x_user_id": "1234567890",
    "content_types": ["tweet", "reply", "retweet", "media"],
    "exclude_replies": false,
    "max_items": 50,
    "refresh_interval_seconds": 300,
    "proxy_endpoint": "https://api.rareimagery.net/proxy/x-feed/{x_user_id}"
  },

  "shop": {
    "products_endpoint": "https://api.rareimagery.net/jsonapi/commerce_product/default",
    "filter_by_store_id": "uuid-of-drupal-store-entity",
    "layout": "grid",
    "columns": 2,
    "show_price": true,
    "show_sold_out": true,
    "checkout_mode": "webview",
    "checkout_url": "https://marcellaink.rareimagery.net/cart"
  },

  "media": {
    "source": "x_media",
    "content_types": ["photo", "video"],
    "layout": "masonry"
  },

  "profile": {
    "show_x_stats": true,
    "show_product_count": true,
    "show_storefront_link": true,
    "links": [
      {
        "label": "Full Storefront",
        "url": "https://marcellaink.rareimagery.net",
        "icon": "globe"
      }
    ]
  },

  "notifications": {
    "enabled": false,
    "apns_topic": null,
    "fcm_project_id": null
  },

  "analytics": {
    "enabled": false,
    "provider": null
  },

  "meta": {
    "app_store_id": null,
    "play_store_id": null,
    "app_store_url": null,
    "play_store_url": null,
    "support_email": "support@rareimagery.net",
    "privacy_url": "https://rareimagery.net/privacy",
    "terms_url": "https://rareimagery.net/terms"
  }
}
```

---

## iOS Platform File — `apple-app-site-association`

Served at:
```
GET marcellaink.rareimagery.net/.well-known/apple-app-site-association
```

Enables Universal Links — tapping a storefront URL on iOS opens the native app instead of Safari. Add this dynamically based on the creator's bundle ID.

```json
{
  "applinks": {
    "details": [
      {
        "appIDs": ["TEAMID.net.rareimagery.marcellaink"],
        "components": [
          {
            "/": "/products/*",
            "comment": "Opens product detail in app"
          },
          {
            "/": "/collections/*",
            "comment": "Opens collection in app"
          },
          {
            "/": "/*",
            "comment": "All other paths open in app"
          }
        ]
      }
    ]
  },
  "webcredentials": {
    "apps": ["TEAMID.net.rareimagery.marcellaink"]
  }
}
```

> Replace `TEAMID` with your Apple Developer Team ID. For Tier 1 hosted apps, this is your team ID. For Tier 2 white-label, it's the creator's team ID.

---

## Android Platform File — `assetlinks.json`

Served at:
```
GET marcellaink.rareimagery.net/.well-known/assetlinks.json
```

Enables Android App Links — same purpose as Universal Links on iOS.

```json
[
  {
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
      "namespace": "android_app",
      "package_name": "net.rareimagery.marcellaink",
      "sha256_cert_fingerprints": [
        "AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99"
      ]
    }
  }
]
```

> The SHA-256 fingerprint comes from the app's signing keystore. For Tier 1, this is RareImagery's keystore. For Tier 2 white-label, the creator signs with their own keystore and provides the fingerprint.

---

## Drupal Integration

### New field on Commerce Store entity
Add `field_app_config` (JSON) to the `commerce_store` entity. Populated when a store owner purchases the app tier.

### New JSON:API endpoint
Expose via a custom route (not raw JSON:API, since this needs light transformation):

```
GET /app-config/{creatorSlug}
```

Returns the `field_app_config` value with `generated_at` timestamp injected. Cache with a 5-minute TTL — Cloudflare handles the CDN layer.

### Next.js — serve `.well-known` files dynamically
Both `apple-app-site-association` and `assetlinks.json` need to live at the creator's subdomain. Handle in Next.js via a catch-all route:

```ts
// app/[store]/.well-known/[file]/route.ts
export async function GET(req, { params }) {
  const { store, file } = params

  if (file === 'apple-app-site-association') {
    const config = await fetchAppConfig(store)
    return NextResponse.json(buildAAsa(config))
  }

  if (file === 'assetlinks.json') {
    const config = await fetchAppConfig(store)
    return NextResponse.json(buildAssetLinks(config))
  }

  return new Response(null, { status: 404 })
}
```

---

## X Posts Proxy

The app fetches posts via a server-side proxy at `api.rareimagery.net/proxy/x-feed/{x_user_id}`. This keeps the xAI/X API key server-side and lets you cache responses per creator.

```
GET /proxy/x-feed/{x_user_id}?max_results=50&exclude_replies=false
```

Response shape the app expects:

```json
{
  "posts": [
    {
      "id": "tweet_id",
      "text": "Post content here",
      "created_at": "2025-01-15T09:00:00Z",
      "media": [
        {
          "type": "photo",
          "url": "https://pbs.twimg.com/media/...",
          "width": 1200,
          "height": 900
        }
      ],
      "metrics": {
        "like_count": 142,
        "retweet_count": 18,
        "reply_count": 7
      },
      "url": "https://x.com/marcellaink/status/tweet_id"
    }
  ],
  "next_token": "cursor_for_pagination"
}
```

---

## Selling the App Tiers

| Feature | Tier 1 Hosted | Tier 2 White-Label |
|---------|--------------|-------------------|
| Branded name & colors | ✓ | ✓ |
| X post feed | ✓ | ✓ |
| Product catalog | ✓ | ✓ |
| Published under RareImagery account | ✓ | — |
| Published under creator's account | — | ✓ |
| Custom bundle ID | — | ✓ |
| Push notifications | Add-on | Add-on |
| App Store optimization | — | Add-on |
| Suggested price | $X/mo | $X one-time + $X/yr |

---

## Build Pipeline (when ready)

Both platforms use a single template codebase. When a creator purchases an app:

1. Their `app-config.json` is finalized and saved to Drupal
2. A CI job (GitHub Actions) triggers a new build:
   - Injects `APP_CONFIG_URL` env var pointing to their config endpoint
   - Builds the iOS `.ipa` or Android `.aab`
   - Submits to App Store Connect / Play Console via Fastlane
3. App goes live within Apple/Google review time (~1–3 days)

For Tier 1 (hosted), all builds go into one App Store slot with per-creator deep links routing to the right config. For Tier 2, each creator gets their own App Store listing.

---

## What to Build Next

1. **`field_app_config` on Commerce Store** — Drupal field + JSON:API exposure
2. **`/app-config/{slug}` route** — Next.js API route serving the config with caching
3. **`/.well-known/` routes** — dynamic AASA + assetlinks per subdomain
4. **X posts proxy** — `/proxy/x-feed/{x_user_id}` on the Next.js API layer
5. **iOS template app** — SwiftUI, config-driven, tab bar from JSON
6. **Android template app** — Jetpack Compose, config-driven, bottom nav from JSON
