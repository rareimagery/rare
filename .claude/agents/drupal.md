# Drupal Backend Agent

You are the Drupal backend agent for RareImagery.net — managing the headless CMS and commerce platform.

## Scope
- Custom modules in `web/modules/custom/`
- Custom theme in `web/themes/custom/rareimagery/`
- Drupal config, Commerce setup, entity management
- Remote server at 72.62.80.155 (SSH: `ssh root@72.62.80.155`)
- Drush: `docker exec rare-drupal /opt/drupal/vendor/bin/drush`

## Stack
- Drupal 10.3, PHP 8.3, PostgreSQL 16
- Docker containers: `rare-drupal` (port 80), `rare-postgres` (port 5432)
- Commerce 3.x with Stripe, Printful integrations

## Custom Modules

### rareimagery_xstore (main platform module)
Location: `web/modules/custom/rareimagery_xstore/`
- **Controllers**: StorePageController, DashboardAppController, PrintfulWebhookController, StripeSubscriptionWebhookController
- **EventSubscribers**: PlatformFeeSubscriber ($1 physical/$0.05 digital), StripeConnectSubscriber, PrintfulOrderSubscriber
- **Services**: StoreManagerService, PrintfulSyncService, XProfileScraperService, CreatorThemeService, SubscriptionManagerService
- **Resolvers**: CreatorStoreResolver, StoreOrderTypeResolver
- **REST Resources**: 13 endpoints (store CRUD, checkout, Stripe Connect, Printful sync, subscriptions, analytics)
- **Config**: 220 YAML files in config/install/ (product types, order types, fields, taxonomies, views, facets, search indexes, payment gateways)

### rareimagery_ai (AI admin module)
Location: `web/modules/custom/rareimagery_ai/`
- Claude + Grok AI admin chat interface
- Services: AiAgent, ClaudeClient, XaiClient, ToolRegistry
- Admin route: `/admin/ai/chat`

### rareimagery_x_import (X profile import)
Location: `web/modules/custom/rareimagery_x_import/`
- One-click X profile import via Grok
- Forms: XImportSettingsForm, XProfileImportForm
- Services: GrokService, XApiService

## Entity Model
- `x_creator_store` (node) — public storefront, X profile data
- `creator_profile_theme` (node) — theme customization
- `commerce_store` type `creator` — linked 1:1 to store node
- Product types: physical_pod, physical_custom, digital_download
- Variation types: pod_variation, custom_variation, digital_variation
- Order types: pod_order, custom_order, digital_order
- Taxonomies: product_category, design_style, audience, animal_type, breed

## Content Type: creator_x_profile
Fields: field_x_username, field_store_theme, field_bio_description, field_follower_count, field_top_posts, field_top_followers, field_metrics, field_profile_picture, field_background_banner, field_linked_store

## SKU Convention
`[STORE]-[TYPE]-[PRODUCT]-[VARIANT]` e.g. `RAREIMAGERY-POD-001-SM-BLK`

## Payment Flow (Stripe Connect)
1. Customer pays full amount to platform Stripe account
2. `application_fee_amount` retains platform fee
3. `transfer_data.destination` routes remainder to creator's Connected Account

## Admin User
- Username: `rare` (uid 1, is_admin: true)
- JSON:API read_only disabled (`drush config:set jsonapi.settings read_only 0`)

## Scripts
Setup scripts in `scripts/`: setup_drupal_fields.php, setup_product_types.php, create_demo_profiles.php, create_demo_stores.php, create_demo_products.php, etc.

## Gotchas
- Local Docker DB has ZERO data — all real data on remote server
- `drush config:set ... false` doesn't work for booleans — use `0`
- Basic Auth requires custom `jsonapi_basic_auth` module
