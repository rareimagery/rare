# Xai1_theme — X Profile Page Template

## Overview

`Xai1_theme` is the default Next.js page template for store owner public profile pages on rareimagery.net. It mirrors the visual language of X (Twitter) — centered layout, dark mode by default — but replaces the **Communities** widget with the owner's **live store panel**, and injects their last **5 X posts** pulled via the X API. All profile and store data is fed from the Drupal backend via JSON:API.

**Design Direction:** Refined dark editorial — not a clone of X, but a spiritual sibling. Deep near-black backgrounds, cool graphite tones, precise typographic hierarchy. Thin luminous accent lines. Feels like a native part of the X ecosystem while being unmistakably the platform's own.

---

## Aesthetic & Design System

### Color Palette

```css
:root {
  /* Core dark palette */
  --bg-base:        #0a0a0b;   /* deepest background */
  --bg-surface:     #111214;   /* card / panel surfaces */
  --bg-elevated:    #1a1c1f;   /* hover states, borders */
  --bg-overlay:     #222428;   /* modals, tooltips */

  /* Text */
  --text-primary:   #e7e9ea;   /* primary content */
  --text-secondary: #71767b;   /* metadata, timestamps */
  --text-muted:     #3e4144;   /* dividers, placeholders */

  /* Accent */
  --accent-blue:    #1d9bf0;   /* X brand blue — links, CTAs */
  --accent-glow:    rgba(29, 155, 240, 0.12); /* subtle glow */
  --accent-store:   #f0a500;   /* amber — store panel accent */
  --accent-store-glow: rgba(240, 165, 0, 0.10);

  /* Borders */
  --border-subtle:  rgba(255, 255, 255, 0.06);
  --border-active:  rgba(255, 255, 255, 0.12);

  /* Fonts */
  --font-display:   'Sora', sans-serif;       /* headers, name */
  --font-body:      'DM Sans', sans-serif;    /* body text, posts */
  --font-mono:      'JetBrains Mono', monospace; /* stats, counts */
}
```

### Typography Scale

| Usage | Font | Weight | Size |
|---|---|---|---|
| Display name | Sora | 700 | 20px |
| Username | DM Sans | 400 | 15px |
| Bio | DM Sans | 400 | 15px |
| Post body | DM Sans | 400 | 15px |
| Metadata / time | DM Sans | 400 | 13px |
| Stats / counts | JetBrains Mono | 500 | 14px |
| Store name | Sora | 600 | 16px |
| CTA buttons | DM Sans | 600 | 14px |

### Layout Grid

The template uses X's proven three-column layout centered on a 1280px max-width:

```
[max-width: 1280px, centered, margin: 0 auto]

┌──────────┬──────────────────────┬───────────────┐
│  LEFT    │     CENTER FEED      │     RIGHT     │
│  NAV     │    (600px fixed)     │   WIDGETS     │
│  (240px) │                      │   (350px)     │
└──────────┴──────────────────────┴───────────────┘
```

On tablet (< 1024px): Left nav collapses to icon-only sidebar.
On mobile (< 768px): Single column, bottom tab navigation.

---

## Page Sections

### 1. Profile Header

Mirrors X's profile header exactly in structure, but uses the store owner's Drupal data:

```
┌──────────────────────────────────────────┐
│  [BANNER IMAGE — field_banner_image]     │
│                                          │
│  ┌──────┐                               │
│  │AVATAR│  [Display Name]  ✓ Verified   │
│  │      │  @x_username                  │
│  └──────┘                               │
│                                          │
│  [Bio — field_bio]                       │
│                                          │
│  📍 [Location]   🔗 rareimagery.net/    │
│                     stores/[slug]        │
│                                          │
│  Joined [date]                           │
│                                          │
│  [Following count]  [Followers count]   │
└──────────────────────────────────────────┘
```

**Data source:** Drupal `user` entity + `field_store` reference.

### 2. Navigation Tabs

Replaces X's standard tab set with store-contextual tabs:

```
[ Posts ] [ Replies ] [ Media ] [ Store ] [ Reviews ]
```

`Store` tab is new — links to the store panel section on the page.
`Reviews` tab — links to the product reviews section.
Active tab has the X-style blue underline indicator.

### 3. Center Feed — Last 5 X Posts

The main column displays the store owner's 5 most recent X posts, pulled live via the X API v2. Each post card matches X's visual style:

```
┌──────────────────────────────────────────────┐
│  [Avatar]  Display Name  @username  · 2h     │
│                                              │
│  Post text content here — full tweet body   │
│  rendered with links, hashtags highlighted  │
│                                              │
│  [Embedded image if present]                 │
│                                              │
│  ── ♻ 12  ♡ 48  👁 1.2K  ↗               │
└──────────────────────────────────────────────┘
```

Posts include: text, images/media, engagement stats (likes, retweets, views), timestamp, and a direct link to the post on X.

### 4. Right Column — Store Panel (Replaces Communities)

This is the key departure from X's native layout. Where X shows "Communities", `Xai1_theme` shows the store owner's **rareimagery.net store panel**.

```
┌───────────────────────────────────┐
│  ──────────────────────────────  │
│  🛍️  THEIR STORE                 │
│  ──────────────────────────────  │
│                                   │
│  [Store Logo / Banner Thumbnail]  │
│                                   │
│  [Store Name]                     │
│  [Short store description]        │
│                                   │
│  ★★★★☆  4.6  ·  48 reviews        │
│  📦  127 sales                    │
│                                   │
│  ─── Featured Products ───        │
│                                   │
│  [Img] Product 1  $24.00  →      │
│  [Img] Product 2  $38.00  →      │
│  [Img] Product 3  $12.00  →      │
│                                   │
│  [ Visit Store → ]               │
│                                   │
│  ──────────────────────────────  │
└───────────────────────────────────┘
```

The store panel is always visible on desktop, accessible via the Store tab on mobile.

### 5. Right Column — Also What's Happening (Below Store)

Below the store panel, a slim platform context widget:

```
┌───────────────────────────────────┐
│  More from rareimagery            │
│  ──────────────────────────────  │
│  [3 other store owner thumbnails] │
│  Explore the marketplace →        │
└───────────────────────────────────┘
```

---

## Component Architecture

```
app/
└── x-profile/
    └── [username]/
        └── page.tsx                  ← Server Component, data fetching

components/
└── xai1/
    ├── XProfileLayout.tsx            ← 3-column grid wrapper
    ├── XProfileHeader.tsx            ← Banner, avatar, bio, stats
    ├── XProfileTabs.tsx              ← Tab navigation bar
    ├── XPostFeed.tsx                 ← Last 5 posts container
    ├── XPostCard.tsx                 ← Individual post card
    ├── XStorePanel.tsx               ← Store widget (replaces Communities)
    ├── XFeaturedProductCard.tsx      ← Mini product card in store panel
    ├── XSuggestedStores.tsx          ← "More from rareimagery" widget
    └── XProfileSkeleton.tsx          ← Loading skeleton

lib/
├── drupal/
│   └── fetchXProfile.ts             ← Drupal JSON:API fetch
└── x-api/
    └── fetchUserPosts.ts            ← X API v2 fetch (last 5 posts)

types/
└── xai1.types.ts                    ← TypeScript interfaces
```

---

## TypeScript Interfaces

```typescript
// types/xai1.types.ts

export interface XProfileData {
  // From Drupal JSON:API
  drupal: {
    userId: string;
    displayName: string;
    username: string;           // Drupal username
    xUsername: string;          // @handle on X (field_x_username)
    xUserId: string;            // X numeric ID (field_x_user_id)
    bio: string;                // field_bio
    location?: string;          // field_location
    avatarUrl: string;          // user picture
    bannerUrl?: string;         // field_banner_image
    joinedDate: string;         // user created date
    isVerified: boolean;        // field_verified badge
    store: StoreData;
  };

  // From X API v2
  xPosts: XPost[];
}

export interface StoreData {
  id: string;
  name: string;
  slug: string;
  shortDescription: string;
  logoUrl: string;
  bannerUrl?: string;
  averageRating: number;
  reviewCount: number;
  totalSales: number;
  featuredProducts: FeaturedProduct[];
  storeUrl: string;             // /stores/[slug] on rareimagery.net
}

export interface FeaturedProduct {
  id: string;
  title: string;
  price: string;
  imageUrl: string;
  slug: string;
}

export interface XPost {
  id: string;
  text: string;
  createdAt: string;
  publicMetrics: {
    retweetCount: number;
    likeCount: number;
    replyCount: number;
    impressionCount: number;
  };
  attachments?: {
    mediaKeys?: string[];
    mediaUrls?: string[];
  };
  url: string;                  // direct link to post on x.com
}
```

---

## Data Fetching

### Drupal JSON:API — Profile + Store Data

```typescript
// lib/drupal/fetchXProfile.ts

export async function fetchXProfile(username: string): Promise<XProfileData['drupal']> {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/user/user` +
    `?filter[name]=${username}` +
    `&include=user_picture,field_store,field_store.field_images,` +
    `field_store.field_featured_products,field_store.field_featured_products.field_images` +
    `&fields[user--user]=name,field_x_username,field_x_user_id,field_bio,` +
    `field_banner_image,field_location,field_verified,created,user_picture` +
    `&fields[commerce_store--online]=title,field_short_description,field_images,` +
    `field_store_logo,field_featured_products`,
    {
      next: { revalidate: 300 },  // revalidate every 5 minutes
      headers: {
        'Accept': 'application/vnd.api+json',
        'Authorization': `Bearer ${process.env.DRUPAL_API_TOKEN}`,
      },
    }
  );

  if (!res.ok) throw new Error(`Drupal fetch failed: ${res.status}`);
  const json = await res.json();
  return normalizeDrupalProfile(json);
}
```

### X API v2 — Last 5 Posts

```typescript
// lib/x-api/fetchUserPosts.ts

export async function fetchUserPosts(xUserId: string): Promise<XPost[]> {
  const res = await fetch(
    `https://api.x.com/2/users/${xUserId}/tweets` +
    `?max_results=5` +
    `&tweet.fields=created_at,public_metrics,attachments` +
    `&expansions=attachments.media_keys` +
    `&media.fields=url,preview_image_url,type`,
    {
      next: { revalidate: 900 },  // revalidate every 15 minutes
      headers: {
        'Authorization': `Bearer ${process.env.X_BEARER_TOKEN}`,
      },
    }
  );

  if (!res.ok) {
    console.warn(`X API fetch failed for user ${xUserId}: ${res.status}`);
    return [];   // Fail gracefully — show profile without posts
  }

  const json = await res.json();
  return normalizeXPosts(json);
}
```

**Graceful degradation:** If the X API is unavailable or rate-limited, the feed section renders a fallback state — "View posts on X →" link — so the profile page never hard-fails.

### Page-Level Data Fetch

```typescript
// app/x-profile/[username]/page.tsx

import { fetchXProfile } from '@/lib/drupal/fetchXProfile';
import { fetchUserPosts } from '@/lib/x-api/fetchUserPosts';
import { XProfileLayout } from '@/components/xai1/XProfileLayout';

export default async function XProfilePage({
  params,
}: {
  params: { username: string };
}) {
  const drupalData = await fetchXProfile(params.username);
  const xPosts     = await fetchUserPosts(drupalData.xUserId);

  return (
    <XProfileLayout
      drupal={drupalData}
      xPosts={xPosts}
    />
  );
}

export async function generateStaticParams() {
  // Pre-render all active store owner profiles at build time
  const owners = await fetchAllStoreOwnerUsernames();
  return owners.map((u) => ({ username: u }));
}
```

---

## Component Code

### XProfileLayout.tsx

```tsx
// components/xai1/XProfileLayout.tsx
'use client';

import { XProfileData } from '@/types/xai1.types';
import { XProfileHeader } from './XProfileHeader';
import { XProfileTabs } from './XProfileTabs';
import { XPostFeed } from './XPostFeed';
import { XStorePanel } from './XStorePanel';
import { XSuggestedStores } from './XSuggestedStores';

export function XProfileLayout({ drupal, xPosts }: XProfileData) {
  return (
    <div className="xai1-root">
      {/* Left navigation — renders the platform nav, out of scope for this template */}
      <nav className="xai1-leftnav" aria-label="Platform navigation" />

      {/* Center column */}
      <main className="xai1-center">
        <XProfileHeader data={drupal} />
        <XProfileTabs />
        <XPostFeed posts={xPosts} xUsername={drupal.xUsername} />
      </main>

      {/* Right column */}
      <aside className="xai1-right">
        <XStorePanel store={drupal.store} />
        <XSuggestedStores currentStoreId={drupal.store.id} />
      </aside>
    </div>
  );
}
```

### XStorePanel.tsx

```tsx
// components/xai1/XStorePanel.tsx
import Image from 'next/image';
import Link from 'next/link';
import { StoreData } from '@/types/xai1.types';
import { StarRating } from '@/components/ui/StarRating';

export function XStorePanel({ store }: { store: StoreData }) {
  return (
    <div className="xai1-store-panel">
      <div className="xai1-store-panel__header">
        <span className="xai1-store-panel__label">Their Store</span>
      </div>

      {/* Store logo / thumbnail */}
      <div className="xai1-store-panel__hero">
        <Image
          src={store.logoUrl}
          alt={store.name}
          width={56}
          height={56}
          className="xai1-store-panel__logo"
        />
        <div>
          <h3 className="xai1-store-panel__name">{store.name}</h3>
          <p className="xai1-store-panel__desc">{store.shortDescription}</p>
        </div>
      </div>

      {/* Stats row */}
      <div className="xai1-store-panel__stats">
        <StarRating value={store.averageRating} />
        <span className="xai1-store-panel__stat-text">
          {store.averageRating.toFixed(1)} · {store.reviewCount} reviews
        </span>
        <span className="xai1-store-panel__sales">
          📦 {store.totalSales.toLocaleString()} sales
        </span>
      </div>

      {/* Featured products */}
      <div className="xai1-store-panel__products">
        <p className="xai1-store-panel__products-label">Featured Products</p>
        {store.featuredProducts.slice(0, 3).map((product) => (
          <Link
            key={product.id}
            href={`/products/${product.slug}`}
            className="xai1-store-panel__product-row"
          >
            <Image
              src={product.imageUrl}
              alt={product.title}
              width={40}
              height={40}
              className="xai1-store-panel__product-img"
            />
            <span className="xai1-store-panel__product-title">{product.title}</span>
            <span className="xai1-store-panel__product-price">{product.price}</span>
          </Link>
        ))}
      </div>

      {/* CTA */}
      <Link href={store.storeUrl} className="xai1-store-panel__cta">
        Visit Store →
      </Link>
    </div>
  );
}
```

### XPostCard.tsx

```tsx
// components/xai1/XPostCard.tsx
import Image from 'next/image';
import Link from 'next/link';
import { XPost } from '@/types/xai1.types';
import { formatRelativeTime } from '@/lib/utils/time';
import { formatCount } from '@/lib/utils/format';

export function XPostCard({
  post,
  authorAvatar,
  authorName,
  authorUsername,
}: {
  post: XPost;
  authorAvatar: string;
  authorName: string;
  authorUsername: string;
}) {
  return (
    <article className="xai1-post-card">
      <div className="xai1-post-card__header">
        <Image
          src={authorAvatar}
          alt={authorName}
          width={40}
          height={40}
          className="xai1-post-card__avatar"
        />
        <div className="xai1-post-card__meta">
          <span className="xai1-post-card__name">{authorName}</span>
          <span className="xai1-post-card__username">@{authorUsername}</span>
          <span className="xai1-post-card__dot">·</span>
          <span className="xai1-post-card__time">
            {formatRelativeTime(post.createdAt)}
          </span>
        </div>
      </div>

      <p className="xai1-post-card__body">{post.text}</p>

      {/* Media attachments */}
      {post.attachments?.mediaUrls?.length > 0 && (
        <div className="xai1-post-card__media">
          {post.attachments.mediaUrls.map((url, i) => (
            <Image
              key={i}
              src={url}
              alt="Post media"
              width={560}
              height={315}
              className="xai1-post-card__media-img"
            />
          ))}
        </div>
      )}

      {/* Engagement bar */}
      <div className="xai1-post-card__engagement">
        <span className="xai1-post-card__stat">
          <ReplyIcon /> {formatCount(post.publicMetrics.replyCount)}
        </span>
        <span className="xai1-post-card__stat">
          <RetweetIcon /> {formatCount(post.publicMetrics.retweetCount)}
        </span>
        <span className="xai1-post-card__stat xai1-post-card__stat--likes">
          <HeartIcon /> {formatCount(post.publicMetrics.likeCount)}
        </span>
        <span className="xai1-post-card__stat">
          <ViewIcon /> {formatCount(post.publicMetrics.impressionCount)}
        </span>
        <Link
          href={post.url}
          target="_blank"
          rel="noopener noreferrer"
          className="xai1-post-card__external"
          aria-label="View on X"
        >
          <ShareIcon />
        </Link>
      </div>
    </article>
  );
}
```

---

## CSS — Full Dark Theme Styles

```css
/* styles/xai1.css */

/* ── Root & Layout ───────────────────────────── */

.xai1-root {
  display: grid;
  grid-template-columns: 240px 600px 1fr;
  gap: 0;
  max-width: 1280px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--bg-base);
  color: var(--text-primary);
  font-family: var(--font-body);
}

/* ── Center Column ───────────────────────────── */

.xai1-center {
  border-left: 1px solid var(--border-subtle);
  border-right: 1px solid var(--border-subtle);
  min-height: 100vh;
}

/* ── Profile Header ──────────────────────────── */

.xai1-profile-banner {
  width: 100%;
  height: 200px;
  object-fit: cover;
  background: var(--bg-elevated);
}

.xai1-profile-banner--placeholder {
  height: 200px;
  background: linear-gradient(
    135deg,
    var(--bg-elevated) 0%,
    var(--bg-overlay) 100%
  );
}

.xai1-profile-info {
  padding: 0 16px 16px;
  position: relative;
}

.xai1-profile-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 4px solid var(--bg-base);
  position: absolute;
  top: -40px;
  border-radius: 50%;
  overflow: hidden;
}

.xai1-profile-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 12px;
  margin-bottom: 52px; /* space for avatar */
}

.xai1-profile-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 20px;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 4px;
  letter-spacing: -0.01em;
}

.xai1-profile-username {
  font-size: 15px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.xai1-profile-bio {
  font-size: 15px;
  line-height: 1.5;
  color: var(--text-primary);
  margin: 12px 0;
}

.xai1-profile-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 12px;
}

.xai1-profile-meta a {
  color: var(--accent-blue);
  text-decoration: none;
}

.xai1-profile-meta a:hover {
  text-decoration: underline;
}

.xai1-profile-follow-stats {
  display: flex;
  gap: 20px;
  font-size: 14px;
}

.xai1-profile-follow-stats strong {
  font-family: var(--font-mono);
  font-weight: 500;
  color: var(--text-primary);
}

.xai1-profile-follow-stats span {
  color: var(--text-secondary);
}

/* ── Profile Tabs ────────────────────────────── */

.xai1-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-subtle);
  position: sticky;
  top: 0;
  background: rgba(10, 10, 11, 0.85);
  backdrop-filter: blur(12px);
  z-index: 10;
}

.xai1-tab {
  flex: 1;
  padding: 16px 8px;
  text-align: center;
  font-size: 15px;
  font-weight: 500;
  color: var(--text-secondary);
  cursor: pointer;
  position: relative;
  transition: color 0.15s ease, background 0.15s ease;
  text-decoration: none;
  background: transparent;
  border: none;
}

.xai1-tab:hover {
  color: var(--text-primary);
  background: var(--accent-glow);
}

.xai1-tab--active {
  color: var(--text-primary);
  font-weight: 700;
}

.xai1-tab--active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 3px;
  background: var(--accent-blue);
  border-radius: 2px 2px 0 0;
}

/* ── Post Cards ──────────────────────────────── */

.xai1-post-feed {
  padding: 0;
}

.xai1-post-card {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-subtle);
  transition: background 0.12s ease;
  cursor: pointer;
}

.xai1-post-card:hover {
  background: rgba(255, 255, 255, 0.02);
}

.xai1-post-card__header {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 4px;
}

.xai1-post-card__avatar {
  border-radius: 50%;
  flex-shrink: 0;
}

.xai1-post-card__meta {
  display: flex;
  gap: 4px;
  align-items: baseline;
  flex-wrap: wrap;
  font-size: 15px;
}

.xai1-post-card__name {
  font-family: var(--font-display);
  font-weight: 700;
  color: var(--text-primary);
}

.xai1-post-card__username,
.xai1-post-card__dot,
.xai1-post-card__time {
  color: var(--text-secondary);
  font-size: 14px;
}

.xai1-post-card__body {
  font-size: 15px;
  line-height: 1.55;
  color: var(--text-primary);
  margin: 8px 0 8px 52px; /* align with avatar */
  white-space: pre-wrap;
}

.xai1-post-card__media {
  margin: 8px 0 8px 52px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border-subtle);
}

.xai1-post-card__media-img {
  width: 100%;
  height: auto;
  display: block;
}

.xai1-post-card__engagement {
  display: flex;
  gap: 28px;
  margin-left: 52px;
  margin-top: 10px;
}

.xai1-post-card__stat {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-secondary);
  transition: color 0.12s ease;
}

.xai1-post-card__stat:hover {
  color: var(--accent-blue);
}

.xai1-post-card__stat--likes:hover {
  color: #f91880;
}

.xai1-post-card__external {
  margin-left: auto;
  color: var(--text-secondary);
  transition: color 0.12s ease;
}

.xai1-post-card__external:hover {
  color: var(--accent-blue);
}

/* Post feed empty state */
.xai1-post-feed__empty {
  padding: 40px 16px;
  text-align: center;
  color: var(--text-secondary);
  font-size: 15px;
}

.xai1-post-feed__empty a {
  color: var(--accent-blue);
  text-decoration: none;
}

/* ── Store Panel (Right Column) ──────────────── */

.xai1-store-panel {
  margin: 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
}

.xai1-store-panel::before {
  /* Amber accent line at top */
  content: '';
  display: block;
  height: 3px;
  background: linear-gradient(
    90deg,
    transparent,
    var(--accent-store) 30%,
    var(--accent-store) 70%,
    transparent
  );
}

.xai1-store-panel__header {
  padding: 16px 16px 0;
}

.xai1-store-panel__label {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.02em;
}

.xai1-store-panel__hero {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 16px;
}

.xai1-store-panel__logo {
  border-radius: 10px;
  flex-shrink: 0;
  border: 1px solid var(--border-subtle);
}

.xai1-store-panel__name {
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 4px;
}

.xai1-store-panel__desc {
  font-size: 13px;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.4;
}

.xai1-store-panel__stats {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 16px 12px;
  font-size: 13px;
  color: var(--text-secondary);
  flex-wrap: wrap;
}

.xai1-store-panel__sales {
  color: var(--accent-store);
  font-family: var(--font-mono);
  font-size: 12px;
}

.xai1-store-panel__products {
  border-top: 1px solid var(--border-subtle);
  padding: 12px 0;
}

.xai1-store-panel__products-label {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  padding: 0 16px 8px;
  margin: 0;
}

.xai1-store-panel__product-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  text-decoration: none;
  transition: background 0.12s ease;
}

.xai1-store-panel__product-row:hover {
  background: var(--accent-store-glow);
}

.xai1-store-panel__product-img {
  border-radius: 6px;
  object-fit: cover;
  flex-shrink: 0;
  border: 1px solid var(--border-subtle);
}

.xai1-store-panel__product-title {
  flex: 1;
  font-size: 14px;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.xai1-store-panel__product-price {
  font-family: var(--font-mono);
  font-size: 13px;
  color: var(--accent-store);
  font-weight: 500;
}

.xai1-store-panel__cta {
  display: block;
  margin: 12px 16px 16px;
  padding: 10px 16px;
  background: var(--accent-store);
  color: #0a0a0b;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 14px;
  text-align: center;
  border-radius: 9999px;
  text-decoration: none;
  transition: opacity 0.15s ease, transform 0.1s ease;
}

.xai1-store-panel__cta:hover {
  opacity: 0.92;
  transform: translateY(-1px);
}

/* ── Suggested Stores Widget ─────────────────── */

.xai1-suggested {
  margin: 0 12px 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  padding: 16px;
}

.xai1-suggested__title {
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 12px;
  letter-spacing: -0.02em;
}

/* ── Responsive: Tablet ──────────────────────── */

@media (max-width: 1024px) {
  .xai1-root {
    grid-template-columns: 68px 1fr 350px;
  }
}

@media (max-width: 768px) {
  .xai1-root {
    grid-template-columns: 1fr;
    grid-template-rows: auto 1fr auto;
  }

  .xai1-leftnav {
    display: none;
  }

  .xai1-right {
    display: none; /* store panel accessible via Store tab on mobile */
  }

  .xai1-center {
    border: none;
  }

  .xai1-post-card__body,
  .xai1-post-card__media,
  .xai1-post-card__engagement {
    margin-left: 0;
  }

  .xai1-profile-banner {
    height: 130px;
  }
}

/* ── Loading Skeleton ────────────────────────── */

@keyframes xai1-shimmer {
  0%   { background-position: -600px 0; }
  100% { background-position: 600px 0; }
}

.xai1-skeleton {
  background: linear-gradient(
    90deg,
    var(--bg-elevated) 0px,
    var(--bg-overlay) 200px,
    var(--bg-elevated) 400px
  );
  background-size: 600px 100%;
  animation: xai1-shimmer 1.4s infinite linear;
  border-radius: 4px;
}

.xai1-skeleton--avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
}

.xai1-skeleton--line-lg  { height: 20px; width: 180px; margin-bottom: 8px; }
.xai1-skeleton--line-md  { height: 16px; width: 120px; margin-bottom: 6px; }
.xai1-skeleton--line-sm  { height: 14px; width: 80px; }
.xai1-skeleton--post     { height: 80px; width: 100%; margin-bottom: 1px; }
```

---

## Global Dark Mode Setup

In `app/layout.tsx`, set dark mode as the default — no class toggling required:

```tsx
// app/layout.tsx
export default function RootLayout({ children }) {
  return (
    <html lang="en" data-theme="dark">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
          href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap"
          rel="stylesheet"
        />
      </head>
      <body className="xai1-dark-body">
        {children}
      </body>
    </html>
  );
}
```

```css
/* globals.css */
html[data-theme="dark"],
.xai1-dark-body {
  background: var(--bg-base);
  color: var(--text-primary);
  color-scheme: dark;
}

* {
  box-sizing: border-box;
  -webkit-font-smoothing: antialiased;
}
```

---

## Environment Variables

```env
# .env.local

# Drupal
DRUPAL_API_URL=https://cms.rareimagery.net
DRUPAL_API_TOKEN=your_drupal_jwt_or_consumer_token

# X API v2
X_BEARER_TOKEN=your_x_api_bearer_token

# Optional: X API user context (if sending DMs from same app)
X_API_KEY=
X_API_SECRET=
X_ACCESS_TOKEN=
X_ACCESS_TOKEN_SECRET=
```

---

## Drupal-Side Requirements

For this template to receive the correct data, the following must be configured in Drupal:

| Requirement | Details |
|---|---|
| `field_x_username` on user | Store owner's X handle |
| `field_x_user_id` on user | Numeric X ID — for API calls |
| `field_bio` on user | Short bio displayed on profile |
| `field_banner_image` on user | Profile banner image |
| `field_verified` on user | Boolean — renders verified badge |
| `field_store` on user | Entity reference to Commerce Store |
| Store: `field_store_logo` | Store logo image |
| Store: `field_short_description` | 1-2 sentence store blurb |
| Store: `field_featured_products` | Up to 3 products shown in panel |
| JSON:API enabled | `jsonapi` module installed and enabled |
| CORS configured | rareimagery.net Next.js origin allowed |
| Decoupled Router | `decoupled_router` module for clean path aliases |

---

## Accessibility

| Requirement | Implementation |
|---|---|
| Landmark regions | `<main>`, `<nav>`, `<aside>` on layout columns |
| Post list semantics | `<article>` per post, `<ul>` wrapper |
| Image alt text | Pulled from Drupal image alt fields |
| Focus indicators | Visible rings on all interactive elements |
| Reduced motion | `@media (prefers-reduced-motion)` disables shimmer/transitions |
| Screen reader text | `aria-label` on icon-only buttons (share, external link) |
| Colour contrast | All text meets WCAG AA (4.5:1 minimum) on dark backgrounds |

---

## File Naming Summary

| File | Role |
|---|---|
| `app/x-profile/[username]/page.tsx` | Route entry, server component, data fetch |
| `components/xai1/XProfileLayout.tsx` | Three-column grid shell |
| `components/xai1/XProfileHeader.tsx` | Banner, avatar, bio, follow stats |
| `components/xai1/XProfileTabs.tsx` | Tab bar (Posts, Replies, Media, Store, Reviews) |
| `components/xai1/XPostFeed.tsx` | Feed container, empty/error states |
| `components/xai1/XPostCard.tsx` | Individual post — text, media, engagement |
| `components/xai1/XStorePanel.tsx` | Store widget (Communities replacement) |
| `components/xai1/XFeaturedProductCard.tsx` | Mini product row in store panel |
| `components/xai1/XSuggestedStores.tsx` | "More from rareimagery" widget |
| `components/xai1/XProfileSkeleton.tsx` | Loading skeleton matching layout |
| `lib/drupal/fetchXProfile.ts` | Drupal JSON:API fetch + normalize |
| `lib/x-api/fetchUserPosts.ts` | X API v2 user tweets fetch + normalize |
| `types/xai1.types.ts` | All TypeScript interfaces |
| `styles/xai1.css` | Full dark theme CSS |
