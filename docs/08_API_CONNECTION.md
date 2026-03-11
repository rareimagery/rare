# Step 8: Drupal ↔ Next.js API Connection

**Agent:** Connection (`drupal-nextjs-connection.md`)

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

interface Product {
  id: string;
  title: string;
  price: string;
  image_url: string;
  product_type: string;
  // ... 40+ fields for full ProductDetail
}
```

## Next.js API Routes (17 endpoints)

### Store Management
| Route | Method | What It Does |
|-------|--------|-------------|
| `/api/stores/create` | POST | Creates commerce_store + creator_x_profile nodes in Drupal |
| `/api/stores/provision` | POST | Provisions subdomain, creates DNS record |
| `/api/stores/select-theme` | POST | PATCHes field_store_theme on profile node |
| `/api/stores/approve` | POST | Sets store status to approved, sends notification |
| `/api/stores/products` | GET | Lists products for a store |
| `/api/stores/theme` | GET/POST | Get or set theme configuration |

### X/AI Integration
| Route | Method | What It Does |
|-------|--------|-------------|
| `/api/stores/import-x-data` | POST | Fetches X profile via API v2, uploads images to Drupal |
| `/api/stores/enhance-profile` | POST | Runs X fetch → Grok AI enhancement |
| `/api/chat` | POST | Claude Haiku generates Tailwind components |
| `/api/builds` | GET/POST/DELETE | CRUD for saved page builder builds |

### Payments
| Route | Method | What It Does |
|-------|--------|-------------|
| `/api/checkout` | POST | Creates Stripe checkout session |
| `/api/webhooks/stripe` | POST | Handles payment confirmations, subscription events |

### Printful
| Route | Method | What It Does |
|-------|--------|-------------|
| `/api/printful/connect` | POST | OAuth flow for Printful |
| `/api/printful/products` | GET | Fetch Printful catalog |
| `/api/printful/sync` | POST | Sync Printful products to Drupal |

### Other
| Route | Method | What It Does |
|-------|--------|-------------|
| `/api/auth/[...nextauth]` | * | NextAuth session management |
| `/api/notifications/preferences` | POST | Save notification settings |

## Drupal REST Resources (Backend)

12 custom REST resources in `rareimagery_xstore`:

| Resource | Plugin ID | Purpose |
|----------|----------|---------|
| StoreCreateResource | `store_create` | POST store creation |
| StoreListResource | `store_list` | GET all stores |
| StoreProfileResource | `store_profile` | GET single store |
| CurrentUserStoreResource | `current_user_store` | GET logged-in user's store |
| CheckoutResource | `checkout` | POST order |
| ShippingRatesResource | `shipping_rates` | POST rate calculation |
| DashboardAnalyticsResource | `dashboard_analytics` | GET revenue/orders |
| StripeConnectOnboardingResource | `stripe_connect_onboarding` | POST seller setup |
| SubscriptionCheckoutResource | `subscription_checkout` | POST subscription |
| SubscriptionPortalResource | `subscription_portal` | POST billing portal |
| SubscriptionStatusResource | `subscription_status` | GET subscription |
| PrintfulSyncTriggerResource | `printful_sync_trigger` | POST sync |
| XProfilePreviewResource | `x_profile_preview` | GET preview |

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

## Next Step

→ [Step 9: xAI & Grok Import](09_XAI_IMPORT.md)
