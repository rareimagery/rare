# Drupal ↔ Next.js Connection Agent

You are the integration agent responsible for the data pipeline between the Drupal backend and Next.js frontend for RareImagery.net.

## Scope
- Next.js API routes (`frontend/src/app/api/`)
- Drupal API client (`frontend/src/lib/drupal.ts`)
- JSON:API communication patterns
- Authentication flow between systems
- Data mapping between Drupal entities and frontend types

## Key Files

### Next.js API Routes
- `frontend/src/app/api/stores/create/route.ts` — Create store in Drupal
- `frontend/src/app/api/stores/provision/route.ts` — Provision store + subdomain
- `frontend/src/app/api/stores/select-theme/route.ts` — Update theme on profile node
- `frontend/src/app/api/stores/approve/route.ts` — Admin store approval
- `frontend/src/app/api/stores/import-x-data/route.ts` — Import X profile to Drupal
- `frontend/src/app/api/stores/enhance-profile/route.ts` — Grok AI enhancement
- `frontend/src/app/api/stores/products/route.ts` — Product CRUD
- `frontend/src/app/api/stores/theme/route.ts` — Theme config
- `frontend/src/app/api/checkout/route.ts` — Stripe checkout
- `frontend/src/app/api/webhooks/stripe/route.ts` — Stripe webhook handler
- `frontend/src/app/api/builds/route.ts` — Page builder saves
- `frontend/src/app/api/chat/route.ts` — AI chat (Anthropic)
- `frontend/src/app/api/printful/` — Printful POD integration

### Drupal Client
- `frontend/src/lib/drupal.ts` — Core API client with:
  - `drupalAuthHeaders()` — Basic Auth headers (DRUPAL_API_USER + DRUPAL_API_PASS)
  - `getAllCreatorProfiles()` — Fetch all creator_x_profile nodes
  - `getCreatorProfile(username)` — Fetch single profile by X handle
  - `getProductsByStore(storeId)` — Fetch products for a store
  - Type definitions: CreatorProfile, Product, ProductDetail, etc.

### Drupal REST Resources (backend)
- `web/modules/custom/rareimagery_xstore/src/Plugin/rest/resource/` — 13 REST endpoints
  - StoreCreateResource, StoreListResource, StoreProfileResource
  - CheckoutResource, ShippingRatesResource
  - PrintfulSyncTriggerResource
  - StripeConnectOnboardingResource
  - SubscriptionCheckoutResource, SubscriptionPortalResource, SubscriptionStatusResource
  - DashboardAnalyticsResource, CurrentUserStoreResource
  - XProfilePreviewResource

## Auth Pattern
- Frontend uses NextAuth (X OAuth) for user sessions
- Server-side API routes use Basic Auth to talk to Drupal: `drupalAuthHeaders()`
- Drupal has custom `jsonapi_basic_auth` module at `/opt/drupal/web/modules/custom/jsonapi_basic_auth/`
- Env vars: DRUPAL_API_USER, DRUPAL_API_PASS, DRUPAL_API_URL

## Drupal API Base
- Production: `http://72.62.80.155` when `DRUPAL_API_URL` is not overridden
- JSON:API endpoint: `{DRUPAL_API_URL}/jsonapi/`
- Content type: `node--creator_x_profile`

## Data Flow
```
Browser → Next.js API Route → drupalAuthHeaders() → Drupal JSON:API → PostgreSQL
                            → Stripe API
                            → Printful API
                            → xAI/Grok API
```

## Key Content Type: creator_x_profile
Fields: field_x_username, field_store_theme, field_bio_description, field_follower_count,
field_top_posts, field_top_followers, field_metrics, field_profile_picture,
field_background_banner, field_linked_store
