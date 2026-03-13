# RareImagery.net — X Subscriptions Support Tier

> **Status:** MVP ready to ship in < 4 hours (deep-link + console claim flow). Full auto-DM inbox is Phase 2.

---

## Table of Contents

1. [Overview](#1-overview)
2. [X Subscriptions Economics (2026)](#2-x-subscriptions-economics-2026)
3. [Recommended Tiers & Pricing](#3-recommended-tiers--pricing)
4. [Platform Perks Integration](#4-platform-perks-integration)
5. [Technical Implementation](#5-technical-implementation)
6. [User Flows & Console Experience](#6-user-flows--console-experience)
7. [Revenue Projections (100k Followers)](#7-revenue-projections-100k-followers)
8. [Synergy with Live X Spaces & Bug-Fix Demos](#8-synergy-with-live-x-spaces--bug-fix-demos)
9. [Requirements & One-Time Setup](#9-requirements--one-time-setup)
10. [Deployment & Quick Start](#10-deployment--quick-start)
11. [Action Items & Roadmap](#11-action-items--roadmap)

---

## 1. Overview

X Subscriptions on `@RareImagery` becomes the VIP entrance to the platform.

Fans pay directly on X, unlock exclusive perks inside every creator's RareImagery store, and it creates a powerful flywheel:

> **More supporters → more invite codes → faster path to 1,000+ X accounts launching stores.**

**Core idea:** Your personal X account runs a support tier that unlocks premium features on RareImagery.net. Creators who subscribe get priority Grok enhancements, waived fees, special badges, early theme access, and private Spaces — all while the platform continues collecting its `$5 setup + $2/mo + $1 POD` fees.

This turns your 100k followers into a recurring revenue engine while accelerating platform growth.

---

## 2. X Subscriptions Economics (2026)

| Metric | Value | Notes |
|---|---|---|
| Revenue share | ~97% to you | X takes 0% platform fee |
| Processing fee | ~2.9% + $0.30 | Standard Stripe, same account as platform |
| Payout threshold | $50 | Auto to your bank |
| New 2026 features | Auto-post exclusive threads, subscriber-only feed cards, shareable links | Use these in marketing |

**Net per subscriber (after fees):**

- `$5 tier` → ~$4.85/mo
- `$10 tier` → ~$9.70/mo

---

## 3. Recommended Tiers & Pricing

| Tier | Name | Price | Target |
|---|---|---|---|
| Tier 1 | Rare Supporter | $5/mo | Entry-level, broad audience |
| Tier 2 | Inner Circle Builder | $10/mo | Recommended upsell |

**Why these prices?** Matches the platform's `$5 setup fee` psychology and keeps it accessible for X creators while delivering high perceived value.

---

## 4. Platform Perks Integration

All perks are stored on the `creator_x_profile` Drupal node and surfaced in the Next.js console and store pages.

| Perk | $5 Tier | $10 Tier | Platform Implementation |
|---|:---:|:---:|---|
| Early access to new themes (MySpace v2, Neon Cyber, Grok-generated custom) | ✓ | ✓ | Auto-unlocked in `/console/theme` |
| Private monthly Spaces + live bug-fix demos | ✓ | ✓ | Invite link auto-sent via Brevo |
| Special invite codes (gift to friends) | 3/mo | 10/mo | New `/api/invite/generate-premium` route |
| Store launch fee discount | $50 (was $100) | $0 (free) | `SubscriptionManagerService` override |
| Exclusive "Rare Supporter" badge | ✓ | ✓ | Shows on public store page + console header |
| Priority Grok profile enhancements + floating builder credits | ✓ | 2× credits | `GrokService` priority queue |
| Dedicated support channel + first dibs on new features | — | ✓ | `/console/support` tab |
| Custom theme color/glitter presets | — | ✓ | MySpace editor unlock |

---

## 5. Technical Implementation

> We already have ~85% of the pieces in place (X OAuth, session, Drupal profile, console context).

### Option 1 — MVP (ship today)

- Deep-link buttons everywhere pointing to `https://x.com/RareImagery/creator-subscriptions/subscribe`
- One-click **"I'm subscribed"** claim button in console
- Admin approval in `/console/admin` (single click)

### Option 2 — Phase 2 (full DM inbox)

- Add `dm.read` + `dm.write` OAuth scopes
- New Next.js route: `/api/notifications/x-dm`
- New Drupal subscriber: `XSubscriptionDMSubscriber`

### Files to Create / Modify

| File | Status |
|---|---|
| `frontend/src/components/SubscribeOnXButton.tsx` | New |
| `frontend/src/app/console/support/page.tsx` | New |
| `frontend/src/lib/x-subscription.ts` | Extend existing |
| `web/modules/custom/rareimagery_xstore/src/Service/XSubscriptionService.php` | New |
| `frontend/src/context/ConsoleContext.tsx` | Small patch (badge logic) |

### New Drupal Fields on `creator_x_profile`

| Field | Type | Values |
|---|---|---|
| `field_x_subscription_tier` | List (string) | `none`, `rare_supporter`, `inner_circle` |
| `field_x_subscriber_since` | Date | ISO 8601 |

---

## 6. User Flows & Console Experience

### Subscriber Journey

```
Store page
  └── Click "Become a Supporter on X"
        └── Pay on X
              └── Return to RareImagery → /console/support
                    └── Click "Claim my perks"
                          └── X ID matched → perks unlock instantly
```

### Creator Admin View (`/console/admin`)

- New **X Subscribers** table
- One-click approve / revoke per subscriber
- Auto email + SMS notification on new subscriber join

### Public Badge

- Small gold **"Rare Supporter"** badge displayed on store nav and product pages

---

## 7. Revenue Projections (100k Followers)

| Scenario | Subscribers | Avg Tier | Monthly Gross | Net to You | Platform Upside (stores) | Total Monthly |
|---|---|---|---|---|---|---|
| Conservative (Month 1) | 200 | $6.50 | $1,300 | $1,260 | +$400–$800 | **$1,660–$2,060** |
| Realistic | 500 | $7.50 | $3,750 | $3,640 | +$1,000–$2,000 | **$4,640–$5,640** |
| Aggressive (1% goal) | 1,000 | $8.00 | $8,000 | $7,760 | +$2,000–$4,000 | **$9,760–$11,760** |

- **Break-even:** 6 subscribers covers all fixed platform costs
- **At 500 subscribers:** ~$5,000/mo on top of everything else

---

## 8. Synergy with Live X Spaces & Bug-Fix Demos

This feature was built for the Spaces growth plan:

- End every Space with: *"First 50 who subscribe right now get $0 store launch + 5 invite codes"*
- Live demo: trigger a test sale → show supporter badge appear in real time
- Fix a bug on-stream → announce *"Only Inner Circle Builders get priority hotfixes"*
- Post-Space automation: auto-DM new subscribers their claim link

> **One Space = 50–150 new supporters** (seen it happen).

---

## 9. Requirements & One-Time Setup

- [ ] Turn on Subscriptions in X Creator Studio (you qualify)
- [ ] No new API keys needed for MVP
- [ ] Existing Stripe account already connected
- [ ] Update X app OAuth permissions only if proceeding to Phase 2 (DM inbox)

---

## 10. Deployment & Quick Start

```bash
# 1. Pull latest
git pull

# 2. Add new component + service files (see Section 5)

# 3. Frontend
cd frontend && npm run dev

# 4. Backend — run one-time field creation
make cr
php scripts/setup_x_subscription_fields.php

# 5. Smoke test
# Login as test user → /console/support → claim perks → confirm badge appears
```

---

## 11. Action Items & Roadmap

### Today (2 hours)
- [ ] Ship deep-link buttons + console claim page
- [ ] Write announcement thread for X account

### This Week
- [ ] Run first Space with *"Subscribe to get free store launch"* offer
- [ ] Launch $5 / $10 tiers live

### Phase 2 (Next 2 Weeks)
- [ ] Full X DM inbox in console
- [ ] Auto-notifications to supporters on new sales

### Phase 3
- [ ] Subscriber-only Grok agent
- [ ] Custom theme generator for Inner Circle subscribers

---

*Docs path: `docs/X_SUBSCRIPTIONS_SUPPORT_TIER.md`*
