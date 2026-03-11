# Agent State & Handoff Board

> Shared communication file for all agents. Read before starting work. Update when done.

---

## Active Project

**Project:** Verify & Fix All Next.js ↔ Drupal Integration Paths
**Status:** nearly complete
**Started:** 2026-03-10
**Owner:** data-integration (lead), all agents involved

---

## Agent Status

| Agent | Status | Last Action | Blocked By |
|-------|--------|-------------|------------|
| nextjs-developer | idle | Fixed `PRODUCT_INCLUDES` for default type | — |
| drupal-api | idle | Fixed store permissions, CORS, back-refs, user fields, store status | — |
| data-integration | idle | Audited all 27 endpoints, coordinated fixes | — |
| brand-designer | idle | — | not needed for this project |

---

## Handoff Queue

| From | To | Task | Status |
|------|----|------|--------|
| data-integration | drupal-api | Enable CORS for `*.rareimagery.net` + Vercel preview URLs | done |
| data-integration | drupal-api | Confirm `jsonapi_basic_auth` module is installed on remote | done — confirmed installed & enabled |
| data-integration | nextjs-developer | Report which endpoints fail so routes can add error handling | done — only `default` product includes were broken, now fixed |

---

## Current Sprint Tasks

| # | Task | Agent | Status | Notes |
|---|------|-------|--------|-------|
| 1 | Verify CORS config on Drupal | drupal-api | done | Was already enabled in `services.yml`, added `supportsCredentials: true` |
| 2 | Test public reads: profiles, stores, products | data-integration | done | Profiles + products work public. Stores were blocked — fixed with permission grant |
| 3 | Test Basic Auth writes: create/update profiles, stores | data-integration | done | POST, PATCH, DELETE all work with Basic Auth |
| 4 | Verify all 5 product types have correct include fields | data-integration | done | `default` type was 400ing on `field_categories,field_tags` — removed from `PRODUCT_INCLUDES` |
| 5 | Test file uploads (profile pic + banner via octet-stream) | data-integration | not tested | Needs manual test with actual image binary |
| 6 | Verify `field_linked_store` ↔ `field_linked_x_profile` refs | drupal-api | done | Back-refs were null — populated for all 5 creator stores |
| 7 | Test Printful sync path (variation → product creation) | data-integration | verified | Printful product/variation types exist, 0 products yet (no Printful API key connected) |
| 8 | Test store approval + notification flow | nextjs-developer | partially done | `field_store_status` was missing — created field, set all to "approved" |
| 9 | Test NextAuth credentials login against Drupal user API | data-integration | done | REST login works (200), user lookup by email works, `include=field_store` now resolves |
| 10 | Test Stripe webhook store creation (no session) | nextjs-developer | not tested | Requires Stripe test webhook — architecture verified |
| 11 | Test notification preferences GET/PATCH on user entity | data-integration | done | Fields didn't exist — created `field_phone_number`, `field_notification_channel`, `field_sms_alert_level` |
| 12 | Verify `getAllProductSlugs()` iterates all 5 product types | data-integration | done | All 5 types return 200 with Basic Auth |
| 13 | Test store theme update (both profile + store theme paths) | nextjs-developer | verified | Profile theme field works (all set to xai3). Store theme field is `string_long` for JSON |
| 14 | Verify middleware subdomain rewrite works with Drupal data | nextjs-developer | verified | Middleware rewrites correctly, `getCreatorProfile()` resolves from Drupal |

---

## Decisions Log

| Date | Decision | Reason | Affects |
|------|----------|--------|---------|
| 2026-03-10 | Test remote Drupal (72.62.80.155), not local Docker | Local DB is empty, all real data on remote | all agents |
| 2026-03-10 | CORS was already enabled on remote (not disabled as local config suggested) | `services.yml` on remote differs from local `default.services.yml` | all agents |
| 2026-03-10 | Auth priority: Basic Auth via `drupalAuthHeaders()` | Bearer token is fallback only | data-integration |
| 2026-03-10 | `field_store_status` created as string field (not list_string) | Needed for store approval workflow, was entirely missing | drupal-api |
| 2026-03-10 | Removed `field_categories,field_tags` from default product includes | These fields don't exist on `commerce_product/default`, only on clothing/crafts/digital_download | nextjs-developer |

---

## Blockers & Issues

| Issue | Raised By | Waiting On | Resolved? |
|-------|-----------|------------|-----------|
| CORS disabled on Drupal | data-integration | drupal-api | YES — was already enabled, added `supportsCredentials: true` |
| `jsonapi_basic_auth` module not in filesystem | data-integration | drupal-api | YES — confirmed installed on remote |
| Public reads inconsistent auth | data-integration | nextjs-developer | YES — stores now publicly viewable after permission grant |
| `field_linked_x_profile` null on stores | data-integration | drupal-api | YES — populated for all 5 stores |
| Notification fields missing on user entity | data-integration | drupal-api | YES — created all 3 fields |
| `field_store_status` missing on stores | data-integration | drupal-api | YES — created field, set all to "approved" |
| `field_store` null on `rare` user | data-integration | drupal-api | YES — linked to Demo Store 1 |
| `default` product includes cause 400 | data-integration | nextjs-developer | YES — removed `field_categories,field_tags` from includes |
| File uploads not tested | data-integration | — | NO — needs manual test with image binary |
| Stripe webhook not tested | nextjs-developer | — | NO — needs Stripe test event |

---

## Completed Projects

(none yet — this project nearly complete, 2 items remain untested)

---

## How To Use This File

### Starting a new project
1. Fill in **Active Project** with name, status, owner
2. Break work into tasks under **Current Sprint Tasks**, assign agents
3. Set agent statuses to `queued` or `in-progress`

### During work
- Update your agent's **Status** row when you start/finish
- Add **Handoff Queue** entries when your work creates a dependency for another agent
- Log any **Decisions** that affect other agents
- Flag **Blockers** immediately

### Finishing work
- Mark your tasks `done` in the sprint table
- Set your agent status back to `idle`
- Clear completed handoffs
- When all tasks are done, move project to **Completed Projects**

### Integration order (default)
```
data-integration → drupal-api → nextjs-developer → brand-designer
```
Route data/API changes through `data-integration` first. Visual-only changes can go straight to `brand-designer`.
