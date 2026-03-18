# PERFECT_STORE_ON_X_ARCHITECTURE

## Goal
Build the highest-converting, lowest-friction creator store system for X creators, where each creator gets a branded subdomain, fast storefront, easy management console, and reliable fulfillment and payments.

Core outcome:
- Creator goes from X handle to live store in less than 10 minutes
- Store pages load fast globally
- Platform remains compliant with X policies and resilient under API limits

## North Star Principles
1. X-first identity
- The creator X profile is the root identity for onboarding and storefront personalization.
- RareImagery complements X, not competes with it.

2. Site-first monetization
- Creator can launch profile + content presence first.
- Commerce features unlock progressively (products, subscriptions, upsells).

3. Single source of truth
- Drupal is the canonical system for stores, profiles, products, orders, and relationships.
- Next.js is the experience layer optimized for speed and conversion.

4. AI as force multiplier
- Grok enriches creator profile, theme suggestions, and product ideas.
- Claude powers builder and generation workflows.

5. Operational simplicity
- Clear event-driven boundaries between onboarding, payments, product sync, and fulfillment.
- Idempotent webhook handling and observable failure paths.

## Reference Stack
- Backend CMS: Drupal 10 + PostgreSQL
- Frontend: Next.js App Router + TypeScript + Tailwind
- Edge hosting: Vercel + wildcard subdomains
- DNS/CDN: Cloudflare
- X integration: X API v2 + OAuth 2.0
- AI: xAI Grok + Anthropic Claude
- Payments: Stripe now, X Money adapter-ready
- POD/Fulfillment: Printful
- Notifications: Brevo (email) + Telnyx (sms)

## High-Level Architecture

```text
[Creator/Buyer Browser]
        |
        v
[Cloudflare DNS/CDN + Wildcard *.rareimagery.net]
        |
        v
[Vercel Edge + Next.js Frontend]
  |         |          |            |
  |         |          |            +--> [xAI Grok API]
  |         |          +---------------> [xAI Grok API]
  |         +--------------------------> [X API v2]
  +------------------------------------> [Drupal JSON:API]
                                            |
                                            v
                                      [PostgreSQL]

Webhook/Event Inputs:
[Stripe] ------> [Next.js Webhook Handlers] ------> [Drupal state updates]
[Printful] -----> [Next.js Webhook Handlers] ------> [Order/fulfillment updates]
[X Events*] ----> [Optional webhook processors]
```

## Domain Model (Canonical)
1. Creator X Profile (`creator_x_profile`)
- Key fields: x username, bio, follower metrics, theme, linked store, top posts, profile media.

2. Store (`commerce_store`)
- Key fields: slug, status (`pending|approved|suspended`), contact data, payout/payment settings, printful keys.

3. Product (`commerce_product` + variations)
- Key fields: type, SKU, price, stock, media, subscriber-only gate, visibility.

4. Order (`commerce_order`)
- Key fields: line items, state, fulfillment status, payment references.

5. Social Graph (`flagging/follow_creator`)
- Key fields: follower store id, target store, source context.

## Request and Event Flows

### A) Creator Onboarding Flow
1. Creator logs in with X OAuth 2.0.
2. Next.js session established (role + identity claims).
3. Drupal profile lookup by x username.
4. If missing: create `creator_x_profile`.
5. Pull X profile/timeline/follower data.
6. Enrich with Grok (bio polish, theme and product suggestions).
7. Persist normalized profile and metrics to Drupal.
8. Route creator to setup wizard and prefilled launch state.

### B) Store Creation Flow
1. Creator submits store wizard (name, slug, profile preferences).
2. Validate slug, reserved words, ownership, terms acceptance.
3. Create `commerce_store` in pending status.
4. Link creator profile to store.
5. Trigger admin notification.
6. Return store dashboard URL and readiness checklist.

### C) Launch + Billing Flow
1. Creator starts checkout for setup + first month.
2. Stripe Checkout session created with metadata.
3. Webhook `checkout.session.completed` confirms payment.
4. Idempotent handler finalizes activation and subscription state.
5. Store status set to approved/active when policy checks pass.

### D) Product Lifecycle Flow
1. Creator creates product in console.
2. If free limit exceeded, route through listing fee checkout.
3. Persist variation first, then product entity and store relation.
4. Attach image/media and search indexing.
5. Buyer checkout triggers order creation and notifications.
6. Fulfillment updates via Printful webhook update order status.

### E) Storefront Read Flow
1. Buyer hits `creatorname.rareimagery.net`.
2. Next middleware resolves subdomain -> store slug.
3. Fetch public store/profile/product snapshot from Drupal.
4. Cache at edge (ISR + revalidation) for speed.
5. Render themed storefront with optional X feed panels.

## Subdomain and Routing Strategy
- Domain pattern: `https://{creatorSlug}.rareimagery.net`
- Wildcard DNS points to Vercel.
- Middleware resolves host header into creator context.
- Route groups separate public storefront and private console.
- Fallback behavior:
  - Unknown slug -> branded discovery page
  - Suspended store -> policy-safe status page

## API Boundary Contracts

### Frontend to Drupal
- Read-heavy endpoints use short ISR windows.
- Write operations require session+csrf flow.
- JSON:API schema adherence is enforced by typed client wrappers.

### Frontend to X API
- App-only token for public reads where possible.
- OAuth user-context token for account-bound actions.
- Rate-limit aware fetch wrapper with retry and backoff.

### Frontend to AI Providers
- Grok calls run in enhancement pipelines with safe timeout and null fallback.
- Claude calls run in builder context with strict prompt constraints and per-user limits.

### Webhooks
- Stripe and Printful handlers must be idempotent.
- Signature verification required before processing.
- Event logs include request id, entity id, and terminal state.

## Performance and Caching
1. Caching layers
- Edge CDN cache at Cloudflare/Vercel for storefront routes.
- Next.js ISR for profile/store/product snapshots.
- `no-store` for sensitive console and checkout paths.

2. Revalidation
- On-demand revalidation triggered by store/product/profile updates.
- Webhook-triggered cache invalidation for payment and fulfillment state.

3. Query optimization
- Use sparse fields and includes for JSON:API.
- Batch profile/user lookups where possible.

4. SLO targets
- P95 storefront response: < 350ms cached, < 1200ms uncached
- Checkout API success rate: > 99.9%
- Webhook processing: < 5s end-to-end for standard events

## Security and Compliance
1. AuthN/AuthZ
- NextAuth session hardening and role claims.
- Ownership checks on all store-write routes.

2. Secrets management
- All provider keys in environment variables only.
- No secrets in client bundles.

3. Webhook integrity
- Verify Stripe and Printful signatures.
- Replay-safe idempotency keys and dedup table.

4. Data protection
- Minimize stored PII.
- Structured audit logs for admin and billing actions.

5. X compliance
- Only use supported v2 endpoints.
- Respect rate limits and policy boundaries.
- Separate X-native subscription concerns from RareImagery billing.

## Observability and Operations
1. Structured logging
- Correlation id across request -> webhook -> entity mutation chain.

2. Metrics dashboards
- API error rate by provider (Drupal, X, Grok, Claude, Stripe, Printful).
- Store conversion funnel: login -> setup -> launch -> first sale.
- Job queue and webhook lag.

3. Alerting
- Critical alerts for webhook failures, payment failures, and elevated 429/5xx from X.

4. Recovery
- Retry queues for transient provider failures.
- Dead-letter queue for poison events.

## Data Consistency Rules
1. Drupal is canonical for platform state.
2. External systems are sources for sync/enrichment, not ownership of core store state.
3. Every async mutation is idempotent and replay-safe.
4. Derived analytics can lag; transactional states cannot.

## Rollout Plan

### Phase 1: Foundation
- Finalize domain model and typed API clients.
- Harden auth, ownership checks, webhook verification.
- Validate wildcard routing and subdomain middleware.

### Phase 2: Conversion Core
- Optimize onboarding wizard and auto-prefill from X.
- Add one-click theme recommendation from Grok.
- Implement launch readiness checklist and blockers.

### Phase 3: Revenue Engine
- Listing fee gate and dynamic checkout metadata.
- Order notifications and fulfillment timelines.
- Upsell modules (subscriber-only products, bundles).

### Phase 4: Scale and Reliability
- Add retry queues, dead-letter handling, and runbooks.
- Introduce cost controls for X/AI API usage.
- Expand observability to creator-level health score.

## Definition of Done (Perfect Store on X)
A store is considered perfect when all are true:
1. Creator launches from X login to live subdomain in under 10 minutes.
2. Storefront theme matches creator identity and is mobile-optimized.
3. Product purchase path is frictionless and monitored.
4. Fulfillment and payment events reconcile automatically.
5. Console provides clear actions for growth, not only management.
6. Platform remains policy-safe and resilient under external API constraints.

## Immediate Build Checklist
- Confirm middleware host-to-store resolution in production.
- Ensure idempotency keys in all webhook handlers.
- Add on-demand revalidation hooks for store and product mutations.
- Add typed wrappers for all X API calls currently in use.
- Add conversion dashboard for onboarding and launch funnel.
- Add incident runbook for X API 429 spikes and provider outages.
