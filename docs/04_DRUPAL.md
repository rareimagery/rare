# Step 4: Drupal Backend

**Agent:** Drupal (`drupal.md`)

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

### Controllers (4)
| Controller | Route | Purpose |
|-----------|-------|---------|
| StorePageController | `/store/[handle]` | Public storefront |
| DashboardAppController | `/dashboard` | Vite SPA mount |
| PrintfulWebhookController | `/api/printful/webhook` | Printful order events |
| StripeSubscriptionWebhookController | `/api/stripe/webhook` | Subscription events |

### Event Subscribers (3)
| Subscriber | Event | Purpose |
|-----------|-------|---------|
| PlatformFeeSubscriber | ORDER_PRE_SAVE | Add $1.00/$0.05 fees |
| StripeConnectSubscriber | Payment events | Route payouts to creators |
| PrintfulOrderSubscriber | Order events | Trigger POD fulfillment |

### REST Resources (12)
See [Step 8: API Connection](08_API_CONNECTION.md) for the full list.

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
# Key scripts
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

## Next Step

→ [Step 5: Commerce & Payments](05_COMMERCE.md)
