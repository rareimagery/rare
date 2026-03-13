# RareImagery — Creator Social Space

## Vision

RareImagery isn't just a store host — it's where X creator culture gets a permanent home.
The social layer turns isolated storefronts into a living network: creators follow each other,
shout each other out, drop collabs, and build audience overlap into actual community.

The X import gives us a massive head start — we already know who follows who.
The social layer makes that graph visible and actionable on RareImagery itself.

---

## The Social Graph

### Core Entities

```
Creator  ──follows──▶  Creator
Creator  ──members──▶  Circle
Creator  ──curates──▶  My Picks (list of creators)
Creator  ──shouted──▶  Creator (Shoutout Wall)
Creator  ──collab──▶   Drop (collaborative product release)
```

### Drupal Data Model

#### 1. Creator Follow Relationship

```bash
# New entity: creator_follow (lightweight relationship table)
drush generate entity:content  # then configure as:
```

```php
// Entity fields:
// - follower_uid (user reference)  — who is following
// - following_store_id (store reference) — store being followed
// - created (timestamp)
// - source: 'rareimagery' | 'x_import'  (did they follow here or was it seeded from X?)
```

Or use Drupal's **Flag** module for a simpler follow relationship:

```bash
composer require drupal/flag
drush en flag -y
```

Create a Flag: `follow_creator` — flaggable entity: `commerce_store`
This gives you: follow/unfollow actions, follow counts, and Views integration out of the box.

#### 2. New fields on `commerce_store`

```
field_follower_count       integer   — denormalized count, updated on flag events
field_following_count      integer   — stores this creator follows
field_x_seed_imported      boolean   — whether X follower graph has been seeded
field_social_bio           text_long — short "about me" for the social profile card
field_my_picks             entity_reference_revisions (paragraphs) — curated creator list
field_shoutout_enabled     boolean   — allow others to post shoutouts (default TRUE)
field_collab_open          boolean   — open to collaboration requests (default FALSE)
```

---

## Feature Set

### 1. Follow System (Core)

Every creator gets a **Follow** button on their storefront. Followers see the creator's activity
in their feed. Following is the atomic unit of the entire social layer.

**Seeding from X:**
On first console login, after Grok imports the X profile, auto-check which of the creator's
X followers already have RareImagery stores. Pre-populate their follow list with a confirmation
step: "These 14 creators you follow on X are already on RareImagery — follow them here too?"

This creates instant network density even for new creators.

```js
// /app/api/social/seed-from-x/route.js
// 1. Fetch creator's X following list (already in Drupal creator_x_profile)
// 2. Cross-reference with existing commerce_store field_x_handle values
// 3. Return matched stores → prompt creator to batch-follow
```

---

### 2. Activity Feed (`/feed` in console + store homepage widget)

A reverse-chronological feed of activity from creators you follow:

| Event Type | Feed Entry Example |
|---|---|
| New product drop | `@hypebeast_kai just dropped "Chrome Tee Vol. 3"` |
| Store theme update | `@solstice.art redesigned their store` |
| Collab drop | `@kai × @solstice just dropped a collab pack` |
| New follower milestone | `@nova_creates hit 500 followers on RareImagery` |
| My Picks update | `@jadepunk added 2 new creators to their picks` |

Feed is scoped to: **people you follow** only. No algorithmic amplification.
Optional: **Trending** tab — most-followed and most-active stores this week.

**Drupal: Activity Log**

```bash
composer require drupal/activity_creator
drush en activity_creator -y
```

Or custom: create a `store_activity` entity with fields: `actor_store_id`, `verb`, `target_id`, `created`.
Expose via JSON:API. Next.js polls or uses long-poll/SSE.

---

### 3. Creator Profile Card

Every creator has a profile card used everywhere — ads, feed, search, My Picks.
It's the consistent social identity unit across the platform.

```
┌──────────────────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░░░  [banner crop]  ░░░░░░░░░░░░ │
│                                                  │
│  [Avatar]   @handle          [ Follow / Friends ]│
│             "Dripping in chrome and attitude"    │
│                                                  │
│  847 followers  ·  23 picks  ·  12 products      │
│                                                  │
│  Friends with: @nova @jadepunk @sol [+9 more]    │
└──────────────────────────────────────────────────┘
```

"Friends with" = mutual follows. This is the friendship layer built on top of follows.

---

### 4. Mutual Follows → "Friends"

When two creators follow each other, they become **Friends**.
Friends get elevated treatment across the platform:

- Appear first in each other's feed
- Can unlock a **Friends Only** product tier (future commerce feature)
- Shown in the "Friends with" section of each other's profile card
- Can cross-post shoutouts to each other's Shoutout Wall

This mirrors the X mutual-follow dynamic but makes it a first-class feature.

---

### 5. Shoutout Wall

Each storefront has a **Shoutout Wall** — a small section near the footer where
other creators can post a short text shoutout (max 120 chars, with their avatar).

```
── SHOUTOUTS ────────────────────────────────────────────────────
  [Avatar] @jadepunk: "kai's drops are the real deal 🔥"
  [Avatar] @nova_art: "my fav store on the platform rn"
  [ + Leave a Shoutout ]  (only followers can post)
─────────────────────────────────────────────────────────────────
```

Rules:
- Only creators with a RareImagery store can leave shoutouts
- Store owner can delete shoutouts on their own wall
- Max 10 visible shoutouts (most recent)
- Shoutout creates a feed activity event for both parties

**Drupal:** New content type `shoutout` with fields:
`field_from_store`, `field_to_store`, `field_text` (max 120 chars), `field_status` (published/deleted)

---

### 6. My Picks (Curated Creator Lists)

Every creator can curate up to 10 "Picks" — creators they personally endorse.
Visible as a section on their storefront between products and the footer.

```
── @hypekai's PICKS ─────────────────────────────────────────────
  [Card] @jadepunk      [Card] @nova_art     [Card] @solstice
  [Card] @chromeghost   [Card] @lxst_era
─────────────────────────────────────────────────────────────────
```

This is the evolved form of the ad model — fully personal, fully creator-driven.
My Picks are the strongest possible social signal: "I vouch for this creator."

My Picks also feed the ad recommendation engine — if two creators share Picks overlap,
they have deep audience compatibility.

---

### 7. Creator Circles

Circles are small private groups (3–12 creators) that collaborate or support each other.
Think: a collective, a crew, a clique.

```
Circle: "Chrome Collective"
Members: @hypekai @jadepunk @nova_art @solstice
Visibility: Public (anyone can see the circle exists and its members)
Badge: Members display a circle badge on their storefront
```

**Use cases:**
- A group of photographers who all shoot in the same aesthetic
- A streetwear collective doing simultaneous drops
- A regional creator community (NYC creators, LA creators)

Circles display a shared badge on each member's storefront. Clicking the badge
shows the full circle — a mini-hub page at `rareimagery.net/circles/chrome-collective`.

**Drupal:** Group module or a simple `creator_circle` custom entity with member references.

---

### 8. Collab Drops

Two or more creators can co-release a product. Each creator's storefront shows the drop,
both creator cards appear on the product page, and the activity feeds of both audiences get notified.

```
Product page header:
  [Avatar @hypekai] × [Avatar @jadepunk]
  "CHROME PACK Vol. 1 — A Collaboration"
```

Revenue split is stored on the Drupal Commerce product:
`field_collab_split` — JSON: `{ "store_a": 0.6, "store_b": 0.4 }` (future billing logic)

This is a later-phase feature but worth modeling in the data layer now.

---

### 9. Discover Page (`rareimagery.net/discover`)

A public-facing discovery page — not behind a login — that showcases the creator network:

| Section | Content |
|---|---|
| **Trending** | Most-followed stores this week |
| **New Creators** | Stores created in the last 7 days |
| **Circles** | Featured creator collectives |
| **Rising** | Stores with fastest follower growth |
| **Picks of the Week** | Editorially selected by RareImagery (manual curation by you) |

This is the SEO and growth engine. Every creator featured here gets organic traffic.
It also gives potential creators a reason to sign up — they can see the community before joining.

---

### 10. Social Proof on Storefronts

Subtle but high-impact social signals woven into each storefront:

```
"12 people you follow have visited this store"
"@jadepunk and @nova_art both follow @hypekai"
"3 of your friends have bought from this store"
```

These render only when the visitor is logged in and following relevant creators.
Pull from the follow graph + session identity in Next.js middleware.

---

## Architecture Summary

```
X Import (Grok)
  └── Seeds follow graph on signup
        └── Creator confirms → Flag module creates follow relationships

Creator actions on RareImagery
  └── Follow / Unfollow → Flag entity
  └── Shoutout → shoutout content entity
  └── My Picks → field_my_picks on commerce_store
  └── Circle membership → creator_circle entity

Activity log
  └── store_activity entity → JSON:API → Next.js feed

Next.js surfaces:
  └── /feed (console) — activity from followed creators
  └── [slug].rareimagery.net — social proof widgets, shoutout wall, my picks
  └── rareimagery.net/discover — public discovery page
  └── rareimagery.net/circles/[slug] — circle hub pages
```

---

## Phased Rollout

| Phase | Features | Effort |
|---|---|---|
| **1 — Foundation** | Follow/unfollow, follower count, X seed import, profile card | Medium |
| **2 — Voice** | Shoutout wall, My Picks section on storefronts | Low |
| **3 — Feed** | Activity feed in console, social proof on storefronts | Medium |
| **4 — Circles** | Circle creation, circle badge, circle hub page | Medium |
| **5 — Discover** | Public discovery page, trending, editorial picks | Low |
| **6 — Collabs** | Collab drops, revenue split modeling | High |

Start with Phase 1 + 2. They deliver the most visible social texture immediately
with the least backend complexity.
