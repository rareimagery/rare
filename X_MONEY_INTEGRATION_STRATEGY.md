# X Money Integration Strategy — RareImagery

**Date:** March 17, 2026  
**Status:** Strategic Architecture — Pre-API  
**Replaces:** Stripe-only payment model (Stripe becomes fallback provider)

---

## Executive Summary

X Money enters public access in April 2026. There is no third-party merchant/developer API yet, but the trajectory is clear: P2P payments, creator tips, in-app wallet, and Visa Direct withdrawals are live in beta. Merchant APIs and creator commerce tooling are the acknowledged next layer.

RareImagery's position is ideal. We're building a platform explicitly *on top of* X for X creators. When X Money opens merchant/developer APIs, RareImagery should be among the first platforms to integrate — making payments feel native to the X ecosystem rather than routing creators through a disconnected Stripe checkout.

**The play:** Build a payment abstraction layer now. Stripe remains the concrete provider today. X Money slots in as the primary provider the moment their API surfaces. No architectural rework required.

---

## What X Money Is (March 2026)

### Confirmed Live (Beta)

| Feature | Detail |
|---|---|
| P2P Transfers | Instant via Visa Direct, inside posts/replies/DMs |
| Digital Wallet | In-app balance, real-time tracking, instant notifications |
| Visa Debit Card | Personalized metal card with X handle, 3% cashback |
| Deposits | FDIC-insured via Cross River Bank, up to $250K |
| APY | 6% on wallet balances |
| Licensing | 41 state money transmitter licenses + DC (missing NY notably) |

### Confirmed Roadmap (Not Yet Live)

| Feature | Status |
|---|---|
| Merchant APIs | Acknowledged as next layer; not available |
| Creator monetization tools | Expected but no public spec |
| Stock/crypto trading (Smart Cashtags) | Announced Feb 2026 by Nikita Bier; not in beta screenshots |
| Full 50-state coverage | NY still unlicensed |

### What We're Watching For

- **Merchant Payment API** — Accept X Money as a payment method on external sites/apps
- **Creator Payout API** — Programmatic payouts to creator X wallets
- **Webhook/Event System** — Payment confirmation, refund, dispute callbacks
- **OAuth Scope Expansion** — Financial permissions in X OAuth flow
- **In-App Purchase SDK** — Native X checkout without leaving the platform

---

## Architectural Approach: Payment Provider Abstraction

### Design Principle

Every payment operation in RareImagery goes through a provider-agnostic interface. The concrete implementation behind it can be swapped, stacked, or A/B tested without touching business logic.

### Interface Definition

```
PaymentProvider
├── createCustomer(creatorId, metadata)
├── createSubscription(creatorId, planId)
├── cancelSubscription(subscriptionId)
├── createOneTimeCharge(creatorId, amount, description)
├── processStorefrontPurchase(orderId, cart, buyerPaymentMethod)
├── issueCreatorPayout(creatorId, amount)
├── handleWebhook(rawPayload, signature) → PaymentEvent
├── getPaymentMethods(customerId)
└── refund(transactionId, amount?)
```

Each method returns a normalized `PaymentResult` object regardless of which provider executed it.

### Provider Implementations

```
providers/
├── stripe.ts          ← Live now. Full implementation.
├── x-money.ts         ← Stub today. Populated when API drops.
├── types.ts           ← Shared interfaces and PaymentResult type
└── index.ts           ← Provider factory + routing logic
```

### Provider Router

The router decides which provider handles each operation. Initially all traffic goes to Stripe. As X Money capabilities come online, the router shifts traffic per-capability:

```
Route Decision Matrix (configurable, not hardcoded):

Operation                  | Phase 1 (Now)  | Phase 2 (API Beta) | Phase 3 (Full)
---------------------------|----------------|--------------------|-----------------
Platform subscription      | Stripe         | Stripe             | X Money
One-time setup fee         | Stripe         | Stripe             | X Money
Storefront purchase        | Stripe         | X Money            | X Money
Creator payout             | Stripe Connect | X Money            | X Money
P2P tip (buyer → creator)  | N/A            | X Money            | X Money
Refund                     | Match original | Match original     | Match original
```

The routing config lives in environment variables, same pattern as our existing price storage:

```env
PAYMENT_PROVIDER_SUBSCRIPTION=stripe
PAYMENT_PROVIDER_STOREFRONT=stripe
PAYMENT_PROVIDER_PAYOUT=stripe
PAYMENT_PROVIDER_TIP=disabled
```

Flip a value, redeploy, done. No code changes.

---

## What Changes in the RareImagery Model

### Current Model (Stripe-Only)

| Flow | Mechanism |
|---|---|
| $5 setup + $2/mo platform fee | Stripe `add_invoice_items` + subscription |
| Storefront purchases | Stripe Checkout / Payment Intents |
| Creator payouts | Manual or Stripe Connect |
| X Subscriptions ($2/mo) | Runs entirely through X — no RI involvement |

### Target Model (X Money Primary)

| Flow | Mechanism |
|---|---|
| $5 setup + $2/mo platform fee | X Money subscription (or Stripe fallback) |
| Storefront purchases | X Money in-app payment → direct to creator wallet |
| Creator payouts | Instant to X wallet via X Money API |
| Tips (new feature) | Native X Money P2P — RI facilitates, doesn't process |
| X Subscriptions | Still runs through X natively — unchanged |

### New Capabilities X Money Unlocks

**1. Zero-Friction Checkout for X Users**

Every RareImagery buyer is, by definition, an X user. If they have X Money, checkout is a single tap — no card entry, no Stripe form, no redirect. This is the killer advantage. Conversion rate impact could be massive.

**2. Instant Creator Payouts**

Stripe Connect payouts take 2-7 days. X Money via Visa Direct is instant. Creators see money in their X wallet the moment a sale clears. That's a tangible selling point for onboarding.

**3. Native Tipping**

X Money P2P enables tip-on-storefront as a first-class feature. A visitor can tip a creator directly from their RareImagery page. We facilitate the UI, X Money moves the money. RareImagery takes zero cut (or a tiny facilitation fee — TBD).

**4. Unified Creator Economy Identity**

Creator's X handle = their RareImagery subdomain = their X Money wallet. One identity across social, storefront, and payments. That's the "everything app" vision, and RareImagery rides it.

**5. Reduced Platform Costs**

Stripe charges 2.9% + $0.30 per transaction. If X Money undercuts this (which is the explicit goal — they've called out the $148.5B merchant fee market), RareImagery's margin improves on every transaction, and we can pass savings to creators.

---

## Implementation Phases

### Phase 1: Abstraction Layer (Now — Before X Money API)

**Goal:** Decouple all payment logic from Stripe-specific code.

Tasks:
- Define `PaymentProvider` interface in TypeScript
- Refactor existing Stripe calls to implement the interface
- Implement provider router with env-var-based routing
- Add `x-money.ts` stub that throws `ProviderNotAvailable` for all methods
- Store provider identifier on every transaction record in Drupal (`field_payment_provider: stripe | x-money`)
- Ensure webhook handler is provider-aware (different endpoints, different signature validation)

Drupal schema addition on `commerce_transaction` (or equivalent):
```
field_payment_provider    | string | 'stripe' | 'x-money'
field_provider_tx_id      | string | provider's native transaction ID
field_payment_status      | string | normalized status enum
```

**No user-facing changes.** Everything still runs through Stripe. But the plumbing is provider-agnostic.

### Phase 2: X Money Monitoring & Early Integration

**Goal:** Be ready to integrate within days of API announcement.

Tasks:
- Monitor X developer channels (`@XDevelopers`, `devcommunity.x.com`) for payment API announcements
- When API docs drop: implement `x-money.ts` against real endpoints
- Extend X OAuth scopes to include financial permissions (when available)
- Build X Money payment method selector in storefront checkout UI
- Test in sandbox/beta environment
- Creator console: add X Money wallet connection flow

Decision point: Does X Money support recurring billing natively, or do we need to manage subscription state ourselves and trigger individual charges?

### Phase 3: Gradual Rollout

**Goal:** Shift storefront purchases to X Money first, then subscriptions.

Rollout sequence (each step gated on stability):
1. Enable X Money as **optional** payment method at storefront checkout (buyer chooses)
2. Monitor conversion rates: X Money vs Stripe checkout
3. If X Money conversion > Stripe, make it the **default** with Stripe as fallback
4. Enable creator payouts via X Money (instant to wallet)
5. Enable tipping on storefronts via X Money P2P
6. Migrate platform subscriptions to X Money (last — highest risk)

Stripe is never fully removed. It remains:
- Fallback for buyers without X Money
- Fallback for states where X Money isn't licensed (NY currently)
- Fallback if X Money API has downtime

### Phase 4: X-Money-Native Features

**Goal:** Build features that are only possible with X Money.

- **Tip Jar on every storefront** — P2P from visitor's X wallet to creator's X wallet
- **Split payments for Collab Drops** — X Money handles multi-party payout natively (if API supports)
- **Shoutout-for-payment** — Pay to place a Shoutout on a creator's wall (microtransaction, <$5)
- **Creator Circle treasury** — Shared X Money wallet for collective merchandise drops
- **Social proof payment feed** — "X just bought Y" powered by real X Money events (opt-in, privacy-respecting)

---

## Drupal Backend Adjustments

### New Fields

On `creator_site` entity:
```
field_x_money_wallet_id     | string | null  | Creator's X Money wallet identifier
field_x_money_connected     | boolean        | Whether creator has linked X Money
field_preferred_payout       | string         | 'x-money' | 'stripe' | 'manual'
```

On all transaction entities:
```
field_payment_provider       | string         | 'stripe' | 'x-money'
field_provider_tx_id         | string         | Native transaction ID from provider
```

### Webhook Endpoints

```
/api/webhooks/stripe        ← Existing Stripe webhook handler
/api/webhooks/x-money       ← New endpoint, routed to same normalized event processor
```

Both endpoints validate provider-specific signatures, then emit a normalized `PaymentEvent` that the rest of the system consumes identically.

---

## Next.js Frontend Adjustments

### Checkout Flow

```
Current:
  [Buy] → Stripe Checkout Session → Stripe hosted page → redirect back

Target (X Money):
  [Buy] → X Money in-app payment (if buyer has X Money) → instant confirmation
  [Buy] → Stripe fallback (if no X Money) → existing flow
```

The checkout component detects whether the buyer has X Money available (via OAuth scope or client-side SDK check) and renders the appropriate payment UI.

### Creator Console

New section: **Payments & Payouts**
- Connect X Money wallet (OAuth flow with financial scope)
- View payout history (unified across providers)
- Set preferred payout method
- View tip history

---

## Risk Assessment

| Risk | Severity | Mitigation |
|---|---|---|
| X Money API never materializes or is delayed years | Medium | Stripe works fine. Abstraction layer has zero overhead. We lose nothing. |
| X Money API is too limited (no subscriptions, no webhooks) | Medium | Use X Money for what it supports, Stripe for the rest. Router handles this per-operation. |
| X Money licensing gaps (NY, other states) | Low | Stripe fallback for unlicensed states. Geo-detection at checkout. |
| X Money fee structure is worse than Stripe | Low | Router defaults to cheapest provider per-operation. Easy to flip. |
| X changes terms / restricts third-party commerce | High | Core RareImagery principle: complement X, never depend on it for survival. Stripe is always the floor. |
| Creator trust in X Money is low | Medium | Offer both. Let creators choose their payout method. Don't force it. |

---

## Cost Impact Modeling

### Current (Stripe Only)

```
Per storefront purchase ($25 avg):
  Stripe fee: $25 × 2.9% + $0.30 = $1.03

Per platform subscription ($2/mo):
  Stripe fee: $2 × 2.9% + $0.30 = $0.36 (18% of revenue — painful)
```

### Projected (X Money Primary)

X Money's fee structure isn't public yet. But their stated goal is undercutting traditional payment processors. Conservative modeling:

```
Scenario A: X Money at 1.5% flat (no per-tx fee)
  Per $25 purchase: $0.38 (vs $1.03 Stripe) → 63% fee reduction
  Per $2 subscription: $0.03 (vs $0.36 Stripe) → 92% fee reduction

Scenario B: X Money at 2.0% + $0.10
  Per $25 purchase: $0.60 (vs $1.03 Stripe) → 42% fee reduction
  Per $2 subscription: $0.14 (vs $0.36 Stripe) → 61% fee reduction

Scenario C: X Money at 0% for P2P (tips only)
  Tips become zero-cost to facilitate. Pure upside.
```

The $2/mo subscription fee is where the impact is most dramatic. Stripe's $0.30 flat fee eats 18% of a $2 charge. Any reduction there goes straight to margin.

---

## Alignment with RareImagery Principles

| Principle | How X Money Aligns |
|---|---|
| Complement X, never compete | X Money IS X infrastructure. Using it deepens the complement. |
| Site-first, commerce optional | Unchanged. Commerce is still opt-in. Payment provider is an implementation detail. |
| Creator identity = X identity | X Money wallet tied to X handle reinforces unified identity. |
| No involvement in X Subscriptions | Unchanged. X Subscriptions remain 100% X-native. X Money is for RareImagery's own commerce. |

---

## Action Items

**Immediate (This Sprint)**
- [ ] Define `PaymentProvider` TypeScript interface
- [ ] Refactor Stripe integration behind the interface
- [ ] Add `field_payment_provider` to Drupal transaction schema
- [ ] Set up env-var routing config
- [ ] Create `x-money.ts` stub

**Monitoring (Ongoing)**
- [ ] Watch `@XDevelopers` and `devcommunity.x.com` for merchant API announcements
- [ ] Track X Money state licensing progress (especially NY)
- [ ] Monitor X Money fee structure announcements

**When API Drops**
- [ ] Implement `x-money.ts` against real API
- [ ] Build X Money OAuth financial scope flow
- [ ] Build dual-payment checkout UI
- [ ] Beta test with 5-10 creators before general rollout
