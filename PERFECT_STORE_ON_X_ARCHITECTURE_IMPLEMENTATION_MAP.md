# PERFECT_STORE_ON_X_ARCHITECTURE_IMPLEMENTATION_MAP

As-built architecture map for the current codebase in frontend. This document ties the target Store on X architecture to the real handlers, adapters, and runtime behavior already implemented.

## 1) Runtime Topology (As Built)

Request path:
1. Browser request lands on Vercel.
2. Host-based routing rewrite occurs in [frontend/src/proxy.ts](frontend/src/proxy.ts).
3. Creator subdomain requests are rewritten to /stores/{creator} and rendered by [frontend/src/app/stores/[creator]/page.tsx](frontend/src/app/stores/[creator]/page.tsx).
4. Data is fetched from Drupal JSON:API via [frontend/src/lib/drupal.ts](frontend/src/lib/drupal.ts).
5. External side systems are invoked as needed:
- X API via x-api client/import stack
- Grok via [frontend/src/lib/grok.ts](frontend/src/lib/grok.ts), [frontend/src/app/api/chat/route.ts](frontend/src/app/api/chat/route.ts), and [frontend/src/lib/ai/generate-site.ts](frontend/src/lib/ai/generate-site.ts)
- Stripe via checkout/webhook routes
- Printful via product/order/webhook routes

Important implementation note:
- This project uses [frontend/src/proxy.ts](frontend/src/proxy.ts) for host rewrite logic. There is no middleware.ts in the workspace.

## 2) Auth and Identity Plane

Primary auth entrypoint:
- [frontend/src/app/api/auth/[...nextauth]/route.ts](frontend/src/app/api/auth/[...nextauth]/route.ts)

Session and claims logic:
- [frontend/src/lib/auth.ts](frontend/src/lib/auth.ts)

Implemented modes:
1. X OAuth 2.0 login (TwitterProvider version 2.0) with scopes tweet.read users.read follows.read offline.access.
2. Credentials login path for admin/store-owner fallback.
3. JWT session strategy with role claims and X identity fields.
4. Auto-provision + async X sync on login:
- profile existence check
- creator profile create fallback
- syncXDataToDrupal execution

Drupal auth split:
1. Read operations: Basic/Bearer via drupalAuthHeaders.
2. Write operations: cookie + CSRF via drupalWriteHeaders.

Source:
- [frontend/src/lib/drupal.ts](frontend/src/lib/drupal.ts)

## 3) Storefront Routing and Rendering

Routing rewrite:
- [frontend/src/proxy.ts](frontend/src/proxy.ts)
- Reserved subdomains are excluded from rewrite.
- Non-reserved subdomain rewrites to /stores/{subdomain}.

Creator storefront renderer:
- [frontend/src/app/stores/[creator]/page.tsx](frontend/src/app/stores/[creator]/page.tsx)

Rendering behavior:
1. Loads profile + products + published builds in parallel.
2. Blocks non-approved stores with holding page.
3. Chooses theme component by profile.store_theme.
4. Falls back to Xai3 theme when unknown.

## 4) Core Business Flows Mapped to Real Handlers

### A. Profile Provisioning and AI Bootstrap
- [frontend/src/app/api/stores/provision/route.ts](frontend/src/app/api/stores/provision/route.ts)

Flow:
1. Requires X-authenticated token and agreedToTerms.
2. Enforces per-user rate limit.
3. Requires X follow/subscription check through x-subscription utility.
4. Creates creator profile in Drupal when absent.
5. Triggers async X data sync.
6. Triggers async dual-AI site generation and patches resulting theme/bio/metrics.

### B. Full Store Creation
- [frontend/src/app/api/stores/create/route.ts](frontend/src/app/api/stores/create/route.ts)

Flow:
1. Requires server session auth.
2. Validates slug and uniqueness.
3. Creates commerce store in Drupal with pending status.
4. Creates/links creator profile.
5. Includes permission-aware partial-success fallbacks.
6. Sends admin notification asynchronously.

### C. Store Setup Checkout
- [frontend/src/app/api/checkout/route.ts](frontend/src/app/api/checkout/route.ts)
- [frontend/src/app/api/webhooks/stripe/route.ts](frontend/src/app/api/webhooks/stripe/route.ts)

Flow:
1. Checkout session metadata marks type store_setup.
2. Stripe webhook verifies signature.
3. Webhook creates Drupal store and links creator profile.
4. Webhook starts recurring monthly Stripe subscription.
5. Subscription deletion path suspends store in Drupal.

### D. Product Commerce Checkout
- [frontend/src/app/api/checkout/product/route.ts](frontend/src/app/api/checkout/product/route.ts)
- [frontend/src/lib/payments.ts](frontend/src/lib/payments.ts)

Flow:
1. Chooses provider through abstraction.
2. Uses Stripe provider when available.
3. Adds platform processing fee line item.
4. Returns checkout URL and provider metadata.

### E. Site Generation Endpoint
- [frontend/src/app/api/site/generate/route.ts](frontend/src/app/api/site/generate/route.ts)
- [frontend/src/lib/ai/generate-site.ts](frontend/src/lib/ai/generate-site.ts)

Flow:
1. Requires signed-in creator/admin token.
2. Enforces generation rate limit.
3. Pulls X data.
4. Runs Grok AI for site analysis and component generation.
5. Saves generated data into Drupal profile metrics and published builds.
6. Applies recommended theme and rewritten bio where possible.

## 5) AI Architecture (Implemented)

Chat builder stream:
- [frontend/src/app/api/chat/route.ts](frontend/src/app/api/chat/route.ts)
- Grok streaming output with per-user in-memory rate limit.

Dual-AI site generation:
- [frontend/src/lib/ai/generate-site.ts](frontend/src/lib/ai/generate-site.ts)

Stages:
1. Grok profile analysis returns rewritten bio/category/theme hints.
2. Theme resolution maps to supported storefront themes.
3. Grok generates hero/about components, layout config, CSS, and theme overrides.
4. Calling routes persist output to Drupal.

## 6) X Integration Surface (Implemented)

Inbound X webhooks:
- [frontend/src/app/api/webhooks/x/route.ts](frontend/src/app/api/webhooks/x/route.ts)
- CRC validation on GET
- Signature verification on POST
- Async event routing

Event processor:
- [frontend/src/lib/webhooks/process-event.ts](frontend/src/lib/webhooks/process-event.ts)

Proxy feed endpoint:
- [frontend/src/app/api/proxy/x-feed/[userId]/route.ts](frontend/src/app/api/proxy/x-feed/[userId]/route.ts)

Behavior:
1. Rate-limited endpoint.
2. Attempts X API v2 first (app bearer token).
3. Falls back to Grok feed extraction if X API unavailable.
4. Returns empty feed payload as final fallback.
5. In-memory caching for 5 minutes.

## 7) Fulfillment and Webhooks

Printful webhook:
- [frontend/src/app/api/printful/webhook/route.ts](frontend/src/app/api/printful/webhook/route.ts)

Handled events include:
- package_shipped
- order_failed
- order_canceled
- hold/unhold states
- order_updated
- stock_updated
- package_returned

Order update behavior:
- Resolves Drupal order by internal order id
- Patches state and printful fields through JSON:API
- Returns 200 on processing errors to avoid unbounded retries

Stripe webhook:
- [frontend/src/app/api/webhooks/stripe/route.ts](frontend/src/app/api/webhooks/stripe/route.ts)

Handled events include:
- checkout.session.completed
- customer.subscription.deleted
- invoice.payment_failed

## 8) Scheduled Operations and Health

Cron endpoints:
- [frontend/src/app/api/cron/frontend-agent/route.ts](frontend/src/app/api/cron/frontend-agent/route.ts)
- [frontend/src/app/api/cron/api-agent/route.ts](frontend/src/app/api/cron/api-agent/route.ts)

Security:
- Both require Authorization Bearer CRON_SECRET.

Vercel schedule config:
- [frontend/vercel.json](frontend/vercel.json)

## 9) Config, Security Headers, and Edge Behavior

Next runtime headers and image allowlist:
- [frontend/next.config.ts](frontend/next.config.ts)

Platform-level cache header defaults and cron scheduling:
- [frontend/vercel.json](frontend/vercel.json)

Key notes:
1. Security headers are globally configured.
2. Image remotePatterns explicitly allow Drupal host, rareimagery subdomains, and pbs.twimg.com.
3. Vercel default response cache headers are defined globally.

## 10) Complete API Route Inventory (Current)

Auth:
- [frontend/src/app/api/auth/[...nextauth]/route.ts](frontend/src/app/api/auth/[...nextauth]/route.ts)
- [frontend/src/app/api/auth/register/route.ts](frontend/src/app/api/auth/register/route.ts)

Store lifecycle and theming:
- [frontend/src/app/api/stores/create/route.ts](frontend/src/app/api/stores/create/route.ts)
- [frontend/src/app/api/stores/provision/route.ts](frontend/src/app/api/stores/provision/route.ts)
- [frontend/src/app/api/stores/approve/route.ts](frontend/src/app/api/stores/approve/route.ts)
- [frontend/src/app/api/stores/select-theme/route.ts](frontend/src/app/api/stores/select-theme/route.ts)
- [frontend/src/app/api/stores/theme/route.ts](frontend/src/app/api/stores/theme/route.ts)
- [frontend/src/app/api/stores/generate-theme/route.ts](frontend/src/app/api/stores/generate-theme/route.ts)
- [frontend/src/app/api/stores/theme-chat/route.ts](frontend/src/app/api/stores/theme-chat/route.ts)
- [frontend/src/app/api/stores/import-x-data/route.ts](frontend/src/app/api/stores/import-x-data/route.ts)
- [frontend/src/app/api/stores/enhance-profile/route.ts](frontend/src/app/api/stores/enhance-profile/route.ts)
- [frontend/src/app/api/stores/products/route.ts](frontend/src/app/api/stores/products/route.ts)

Commerce, orders, shipping, accounting:
- [frontend/src/app/api/checkout/route.ts](frontend/src/app/api/checkout/route.ts)
- [frontend/src/app/api/checkout/product/route.ts](frontend/src/app/api/checkout/product/route.ts)
- [frontend/src/app/api/orders/route.ts](frontend/src/app/api/orders/route.ts)
- [frontend/src/app/api/orders/[id]/route.ts](frontend/src/app/api/orders/[id]/route.ts)
- [frontend/src/app/api/shipping/route.ts](frontend/src/app/api/shipping/route.ts)
- [frontend/src/app/api/accounting/route.ts](frontend/src/app/api/accounting/route.ts)

Subscriptions:
- [frontend/src/app/api/subscriptions/checkout/route.ts](frontend/src/app/api/subscriptions/checkout/route.ts)
- [frontend/src/app/api/subscriptions/status/route.ts](frontend/src/app/api/subscriptions/status/route.ts)
- [frontend/src/app/api/subscriptions/tiers/route.ts](frontend/src/app/api/subscriptions/tiers/route.ts)
- [frontend/src/app/api/x-subscription/route.ts](frontend/src/app/api/x-subscription/route.ts)

Social:
- [frontend/src/app/api/social/follow/route.ts](frontend/src/app/api/social/follow/route.ts)
- [frontend/src/app/api/social/followers/route.ts](frontend/src/app/api/social/followers/route.ts)
- [frontend/src/app/api/social/picks/route.ts](frontend/src/app/api/social/picks/route.ts)
- [frontend/src/app/api/social/shoutouts/route.ts](frontend/src/app/api/social/shoutouts/route.ts)
- [frontend/src/app/api/social/seed-from-x/route.ts](frontend/src/app/api/social/seed-from-x/route.ts)
- [frontend/src/app/api/social/conversations/route.ts](frontend/src/app/api/social/conversations/route.ts)
- [frontend/src/app/api/social/conversations/[username]/route.ts](frontend/src/app/api/social/conversations/[username]/route.ts)

AI, builds, config, notifications:
- [frontend/src/app/api/chat/route.ts](frontend/src/app/api/chat/route.ts)
- [frontend/src/app/api/site/generate/route.ts](frontend/src/app/api/site/generate/route.ts)
- [frontend/src/app/api/builds/route.ts](frontend/src/app/api/builds/route.ts)
- [frontend/src/app/api/app-config/[slug]/route.ts](frontend/src/app/api/app-config/[slug]/route.ts)
- [frontend/src/app/api/notifications/preferences/route.ts](frontend/src/app/api/notifications/preferences/route.ts)
- [frontend/src/app/api/console/insights/route.ts](frontend/src/app/api/console/insights/route.ts)

Printful:
- [frontend/src/app/api/printful/connect/route.ts](frontend/src/app/api/printful/connect/route.ts)
- [frontend/src/app/api/printful/status/route.ts](frontend/src/app/api/printful/status/route.ts)
- [frontend/src/app/api/printful/sync/route.ts](frontend/src/app/api/printful/sync/route.ts)
- [frontend/src/app/api/printful/catalog/route.ts](frontend/src/app/api/printful/catalog/route.ts)
- [frontend/src/app/api/printful/catalog/[productId]/route.ts](frontend/src/app/api/printful/catalog/[productId]/route.ts)
- [frontend/src/app/api/printful/products/route.ts](frontend/src/app/api/printful/products/route.ts)
- [frontend/src/app/api/printful/orders/route.ts](frontend/src/app/api/printful/orders/route.ts)
- [frontend/src/app/api/printful/orders/estimate/route.ts](frontend/src/app/api/printful/orders/estimate/route.ts)
- [frontend/src/app/api/printful/shipping-rates/route.ts](frontend/src/app/api/printful/shipping-rates/route.ts)
- [frontend/src/app/api/printful/tax/route.ts](frontend/src/app/api/printful/tax/route.ts)
- [frontend/src/app/api/printful/mockups/route.ts](frontend/src/app/api/printful/mockups/route.ts)
- [frontend/src/app/api/printful/mockups/[taskKey]/route.ts](frontend/src/app/api/printful/mockups/[taskKey]/route.ts)
- [frontend/src/app/api/printful/webhook/route.ts](frontend/src/app/api/printful/webhook/route.ts)
- [frontend/src/app/api/printful/webhook/setup/route.ts](frontend/src/app/api/printful/webhook/setup/route.ts)

Webhooks and platform ops:
- [frontend/src/app/api/webhooks/stripe/route.ts](frontend/src/app/api/webhooks/stripe/route.ts)
- [frontend/src/app/api/webhooks/x/route.ts](frontend/src/app/api/webhooks/x/route.ts)
- [frontend/src/app/api/proxy/x-feed/[userId]/route.ts](frontend/src/app/api/proxy/x-feed/[userId]/route.ts)
- [frontend/src/app/api/cron/frontend-agent/route.ts](frontend/src/app/api/cron/frontend-agent/route.ts)
- [frontend/src/app/api/cron/api-agent/route.ts](frontend/src/app/api/cron/api-agent/route.ts)
- [frontend/src/app/api/invite/verify/route.ts](frontend/src/app/api/invite/verify/route.ts)

## 11) Gaps and Hardening Priorities (Based on Current Implementation)

1. Webhook idempotency is partially implicit and should be explicit.
- Add persisted dedup keys for Stripe and Printful events.

2. In-memory rate limits and caches are not durable across serverless isolates.
- Move to shared backing store for consistent limits under scale.

3. Some routes return success after internal failure to suppress retries.
- Add dead-letter and reconciliation jobs for guaranteed eventual consistency.

4. Auth and authorization patterns vary by route.
- Standardize route guards and ownership checks into shared utilities.

5. Route catalog drift exists between docs and actual route count.
- Regenerate API catalog automatically from filesystem and method exports.

## 12) Definition of Done for Implementation-Mapped Architecture

This architecture is considered implementation-mapped when:
1. Every production flow references concrete route handlers and libs.
2. Every external integration has at least one verified ingress and egress path in code.
3. Every critical mutation path has a known retry, dedup, and observability strategy.
4. The route inventory in docs matches the filesystem route inventory.
