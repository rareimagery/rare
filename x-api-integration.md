# X API v2 Integration — RareImagery.net
**Source of truth:** [X API Postman Collection](https://www.postman.com/xapidevelopers/x-api-public-workspace/collection/34902927-2efc5689-99c6-4ab6-8091-996f35c2fd80) · [Official Docs](https://docs.x.com/x-api/introduction)
**Last synced:** March 2026 · All v1.1 references removed · Pay-per-usage model

---

## 1. API Status & Critical Changes

| Change | Detail |
|---|---|
| **Base URL** | `https://api.x.com/2/` (NOT api.twitter.com) |
| **v1.1 status** | Fully deprecated. Media upload endpoints killed March 31 2025 |
| **Pricing model** | Pay-per-usage credits — no subscriptions. Monthly cap: 2M post reads |
| **Free tier** | Write-only, ~500 posts/month. Like & Follow endpoints removed Aug 2025 |
| **Auth standard** | OAuth 2.0 App-Only (Bearer Token) for reads; OAuth 2.0 PKCE for user-context writes |
| **xAI credit bonus** | Purchasing X API credits returns 10–20% back as free xAI API credits (link accounts in console.x.com) |

---

## 2. Authentication

### 2.1 App-Only (Bearer Token) — Storefront Reads
Used for all public data reads: profiles, timelines, post display. No user login required.

```typescript
// lib/x-api/client.ts
const BEARER_TOKEN = process.env.X_API_BEARER_TOKEN!;

export const xApiHeaders = {
  Authorization: `Bearer ${BEARER_TOKEN}`,
  'Content-Type': 'application/json',
};
```

### 2.2 OAuth 2.0 PKCE — User-Context Actions
Required for anything acting on behalf of a store owner (e.g. future post scheduling).
Flow: `https://x.com/i/oauth2/authorize` → code exchange → `https://api.x.com/2/oauth2/token`

Scopes needed for RareImagery:
- `tweet.read` — display creator posts on storefront
- `users.read` — fetch creator profile for storefront auto-population
- `offline.access` — refresh tokens without re-auth

### 2.3 Legacy Auth Note
Legacy user-context auth exists for compatibility, but **do not use it for new code**. All new RareImagery integrations use OAuth 2.0.

---

## 3. Endpoints Used by RareImagery

### 3.1 User Profile — Storefront Auto-Population (Grok Agent)

**GET** `https://api.x.com/2/users/by/username/:username`

Returns only `id`, `name`, `username` by default. Request all storefront-relevant fields explicitly.

```typescript
// lib/x-api/user.ts
export async function fetchXProfile(username: string) {
  const params = new URLSearchParams({
    'user.fields': [
      'id',
      'name',
      'username',
      'description',
      'profile_image_url',
      'public_metrics',       // followers_count, following_count, tweet_count
      'verified_type',        // blue, business, government, none
      'url',
      'entities',             // expanded URL in bio
      'location',
      'created_at',
      'pinned_tweet_id',
    ].join(','),
    expansions: 'pinned_tweet_id',
    'tweet.fields': 'text,created_at,public_metrics',
  });

  const res = await fetch(
    `https://api.x.com/2/users/by/username/${username}?${params}`,
    { headers: xApiHeaders, next: { revalidate: 3600 } } // Next.js ISR cache 1hr
  );

  if (!res.ok) throw new XApiError(res.status, await res.json());
  return (await res.json()) as XUserResponse;
}
```

**Response shape:**
```json
{
  "data": {
    "id": "12345",
    "name": "Creator Name",
    "username": "creatorhandle",
    "description": "Bio text with expanded URLs in entities",
    "profile_image_url": "https://pbs.twimg.com/profile_images/...",
    "public_metrics": {
      "followers_count": 4200,
      "following_count": 310,
      "tweet_count": 1840,
      "listed_count": 12,
      "like_count": 8900
    },
    "verified_type": "blue",
    "location": "Los Angeles, CA",
    "created_at": "2019-03-14T00:00:00.000Z"
  },
  "includes": {
    "tweets": [ /* pinned tweet if requested */ ]
  }
}
```

---

### 3.2 User Post Timeline — Storefront Feed

**GET** `https://api.x.com/2/users/:id/tweets`

Displays the creator's recent posts on their RareImagery storefront. Access up to 3,200 most recent posts.

```typescript
// lib/x-api/timeline.ts
export async function fetchUserTimeline(
  userId: string,
  options: { maxResults?: number; paginationToken?: string } = {}
) {
  const params = new URLSearchParams({
    max_results: String(options.maxResults ?? 10),
    'tweet.fields': [
      'id',
      'text',
      'created_at',
      'public_metrics',      // likes, retweets, replies, impressions
      'attachments',
      'entities',            // URLs, mentions, hashtags
      'referenced_tweets',
    ].join(','),
    expansions: [
      'attachments.media_keys',
      'referenced_tweets.id',
    ].join(','),
    'media.fields': [
      'media_key',
      'type',
      'url',
      'preview_image_url',
      'width',
      'height',
      'alt_text',
    ].join(','),
    // Exclude replies & retweets for cleaner storefront display
    exclude: 'replies,retweets',
  });

  if (options.paginationToken) {
    params.set('pagination_token', options.paginationToken);
  }

  const res = await fetch(
    `https://api.x.com/2/users/${userId}/tweets?${params}`,
    { headers: xApiHeaders, next: { revalidate: 900 } } // 15min cache
  );

  if (!res.ok) throw new XApiError(res.status, await res.json());
  return (await res.json()) as XTimelineResponse;
}
```

**Pagination:** Response includes `meta.next_token`. Pass as `pagination_token` for the next page.

---

### 3.3 Single Post Lookup — Embed / Share

**GET** `https://api.x.com/2/tweets/:id` or `https://api.x.com/2/tweets?ids=id1,id2`

Used when a creator pins a specific post to their storefront.

```typescript
export async function fetchPost(tweetId: string) {
  const params = new URLSearchParams({
    'tweet.fields': 'id,text,created_at,public_metrics,entities,attachments',
    'user.fields': 'id,name,username,profile_image_url,verified_type',
    expansions: 'author_id,attachments.media_keys',
    'media.fields': 'type,url,preview_image_url,width,height',
  });

  const res = await fetch(
    `https://api.x.com/2/tweets/${tweetId}?${params}`,
    { headers: xApiHeaders, next: { revalidate: 3600 } }
  );

  if (!res.ok) throw new XApiError(res.status, await res.json());
  return (await res.json()) as XPostResponse;
}
```

---

### 3.4 Batch User Lookup — Admin Console

**GET** `https://api.x.com/2/users/by?usernames=user1,user2,...`

Used in the RareImagery console when importing multiple store owners by handle. Up to 100 usernames per request.

```typescript
export async function fetchUsersBatch(usernames: string[]) {
  // Max 100 per request
  const chunks = chunkArray(usernames, 100);
  const results = await Promise.all(
    chunks.map(async (chunk) => {
      const params = new URLSearchParams({
        usernames: chunk.join(','),
        'user.fields': 'id,name,username,description,profile_image_url,public_metrics,verified_type',
      });
      const res = await fetch(
        `https://api.x.com/2/users/by?${params}`,
        { headers: xApiHeaders }
      );
      if (!res.ok) throw new XApiError(res.status, await res.json());
      return (await res.json() as XUsersResponse).data;
    })
  );
  return results.flat();
}
```

---

### 3.5 Usage Monitoring

**GET** `https://api.x.com/2/usage/tweets`

Track credit consumption programmatically. Wire this to the RareImagery admin dashboard.

```typescript
export async function fetchApiUsage() {
  const res = await fetch('https://api.x.com/2/usage/tweets', {
    headers: xApiHeaders,
  });
  return res.json(); // daily post consumption counts
}
```

---

## 4. TypeScript Types

```typescript
// lib/x-api/types.ts

export interface XUser {
  id: string;
  name: string;
  username: string;
  description?: string;
  profile_image_url?: string;
  verified_type?: 'blue' | 'business' | 'government' | 'none';
  location?: string;
  created_at?: string;
  url?: string;
  pinned_tweet_id?: string;
  public_metrics?: {
    followers_count: number;
    following_count: number;
    tweet_count: number;
    listed_count: number;
    like_count: number;
  };
  entities?: {
    url?: { urls: Array<{ url: string; expanded_url: string; display_url: string }> };
    description?: { urls?: Array<{ url: string; expanded_url: string }> };
  };
}

export interface XPost {
  id: string;
  text: string;
  created_at?: string;
  public_metrics?: {
    like_count: number;
    retweet_count: number;
    reply_count: number;
    quote_count: number;
    impression_count: number;
    bookmark_count: number;
  };
  attachments?: { media_keys?: string[] };
  referenced_tweets?: Array<{ type: 'replied_to' | 'retweeted' | 'quoted'; id: string }>;
  entities?: {
    urls?: Array<{ url: string; expanded_url: string; display_url: string; images?: Array<{ url: string; width: number; height: number }> }>;
    hashtags?: Array<{ start: number; end: number; tag: string }>;
    mentions?: Array<{ start: number; end: number; username: string; id: string }>;
  };
}

export interface XMedia {
  media_key: string;
  type: 'photo' | 'video' | 'animated_gif';
  url?: string;                  // present for photos
  preview_image_url?: string;    // present for video/gif
  width?: number;
  height?: number;
  alt_text?: string;
}

export interface XUserResponse {
  data: XUser;
  includes?: { tweets?: XPost[] };
  errors?: XApiErrorObject[];
}

export interface XUsersResponse {
  data: XUser[];
  errors?: XApiErrorObject[];
}

export interface XTimelineResponse {
  data: XPost[];
  includes?: { media?: XMedia[]; tweets?: XPost[] };
  meta: {
    newest_id: string;
    oldest_id: string;
    result_count: number;
    next_token?: string;
  };
  errors?: XApiErrorObject[];
}

export interface XPostResponse {
  data: XPost;
  includes?: { users?: XUser[]; media?: XMedia[] };
  errors?: XApiErrorObject[];
}

export interface XApiErrorObject {
  title: string;
  detail: string;
  type: string;
  parameter?: string;
  value?: string;
}
```

---

## 5. Error Handling & Rate Limits

### 5.1 XApiError class

```typescript
// lib/x-api/errors.ts
export class XApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: unknown
  ) {
    super(`X API error ${status}`);
    this.name = 'XApiError';
  }

  get isRateLimit() { return this.status === 429; }
  get isNotFound()  { return this.status === 404; }
  get isUnauth()    { return this.status === 401 || this.status === 403; }
}
```

### 5.2 Exponential Backoff

Rate limit headers returned with every response:
- `x-rate-limit-limit` — requests allowed in window
- `x-rate-limit-remaining` — requests left
- `x-rate-limit-reset` — Unix timestamp when window resets

```typescript
// lib/x-api/fetch-with-retry.ts
export async function fetchWithRetry(
  url: string,
  options: RequestInit,
  maxRetries = 3
): Promise<Response> {
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    const res = await fetch(url, options);

    if (res.status !== 429) return res;

    if (attempt === maxRetries) throw new XApiError(429, { detail: 'Rate limit exceeded after retries' });

    // Read reset time from header, fall back to exponential backoff
    const resetAt = res.headers.get('x-rate-limit-reset');
    const waitMs = resetAt
      ? Math.max(0, Number(resetAt) * 1000 - Date.now())
      : 1000 * Math.pow(2, attempt);

    console.warn(`[X API] Rate limited. Waiting ${waitMs}ms before retry ${attempt + 1}`);
    await new Promise((r) => setTimeout(r, waitMs));
  }
  throw new XApiError(429, {});
}
```

### 5.3 Response Error Handling Pattern

```typescript
// Always check for partial errors in the errors array
// X API v2 can return HTTP 200 with an errors array for missing/protected accounts
function extractValidUsers(response: XUsersResponse): XUser[] {
  if (response.errors?.length) {
    console.warn('[X API] Partial errors:', response.errors);
  }
  return response.data ?? [];
}
```

---

## 6. Drupal Integration — Storefront Sync

The Grok agent calls the X API on storefront creation, then writes the result to Drupal via JSON:API. Subsequent reads come from Drupal (not X API directly), keeping costs minimal.

### 6.1 Data written to Drupal on store creation

| Drupal field | X API source |
|---|---|
| `field_x_user_id` | `data.id` |
| `field_x_username` | `data.username` |
| `field_x_display_name` | `data.name` |
| `field_x_bio` | `data.description` |
| `field_x_avatar_url` | `data.profile_image_url` |
| `field_x_followers` | `data.public_metrics.followers_count` |
| `field_x_verified_type` | `data.verified_type` |
| `field_x_profile_synced_at` | `Date.now()` (ISO string) |

### 6.2 Cache-first timeline strategy

```typescript
// Next.js API route: /api/storefront/[slug]/feed
// 1. Read cached posts from Drupal field_cached_posts (JSON)
// 2. If older than 15 min, re-fetch from X API and update Drupal
// 3. Return cached data immediately, update in background (stale-while-revalidate)

export async function getStorefrontFeed(userId: string, storeNodeId: string) {
  const cached = await drupalFetch(`/jsonapi/node/store/${storeNodeId}?fields[node--store]=field_cached_posts,field_posts_cached_at`);
  const cachedAt = new Date(cached.data.attributes.field_posts_cached_at ?? 0);
  const stale = Date.now() - cachedAt.getTime() > 15 * 60 * 1000;

  if (!stale && cached.data.attributes.field_cached_posts) {
    return JSON.parse(cached.data.attributes.field_cached_posts) as XPost[];
  }

  // Background refresh
  const fresh = await fetchUserTimeline(userId);
  await drupalPatch(`/jsonapi/node/store/${storeNodeId}`, {
    data: {
      type: 'node--store',
      id: storeNodeId,
      attributes: {
        field_cached_posts: JSON.stringify(fresh.data),
        field_posts_cached_at: new Date().toISOString(),
      },
    },
  });

  return fresh.data;
}
```

---

## 7. Next.js Route Handlers

### 7.1 Storefront profile endpoint

```typescript
// app/api/x/profile/[username]/route.ts
import { fetchXProfile } from '@/lib/x-api/user';
import { XApiError } from '@/lib/x-api/errors';
import { NextRequest, NextResponse } from 'next/server';

export async function GET(
  _req: NextRequest,
  { params }: { params: { username: string } }
) {
  try {
    const profile = await fetchXProfile(params.username);
    return NextResponse.json(profile, {
      headers: { 'Cache-Control': 's-maxage=3600, stale-while-revalidate=86400' },
    });
  } catch (err) {
    if (err instanceof XApiError) {
      if (err.isNotFound) return NextResponse.json({ error: 'User not found' }, { status: 404 });
      if (err.isRateLimit) return NextResponse.json({ error: 'Rate limited' }, { status: 429 });
    }
    return NextResponse.json({ error: 'Internal error' }, { status: 500 });
  }
}
```

### 7.2 Storefront feed endpoint

```typescript
// app/api/x/timeline/[userId]/route.ts
import { fetchUserTimeline } from '@/lib/x-api/timeline';
import { XApiError } from '@/lib/x-api/errors';
import { NextRequest, NextResponse } from 'next/server';

export async function GET(
  req: NextRequest,
  { params }: { params: { userId: string } }
) {
  const { searchParams } = new URL(req.url);
  const maxResults = Math.min(Number(searchParams.get('limit') ?? 10), 20);
  const paginationToken = searchParams.get('next') ?? undefined;

  try {
    const timeline = await fetchUserTimeline(params.userId, { maxResults, paginationToken });
    return NextResponse.json(timeline, {
      headers: { 'Cache-Control': 's-maxage=900, stale-while-revalidate=3600' },
    });
  } catch (err) {
    if (err instanceof XApiError) {
      return NextResponse.json({ error: err.message }, { status: err.status });
    }
    return NextResponse.json({ error: 'Internal error' }, { status: 500 });
  }
}
```

---

## 8. Grok Storefront Auto-Population Flow

The Grok agent (xAI API) uses X API data as context to generate the storefront. Here is the exact data pipeline:

```
Console form (X username input)
  → POST /api/console/stores
    → fetchXProfile(username)          // X API — user lookup
    → fetchUserTimeline(userId, {maxResults: 5})  // X API — recent posts
    → buildGrokPrompt(profile, posts)  // construct xAI prompt
    → xAI Grok API → storefront config JSON
    → PATCH Drupal node (store fields + field_page_builds)
    → Subdomain goes live at {slug}.rareimagery.net
```

```typescript
// lib/grok/build-prompt.ts
export function buildStorefrontPrompt(profile: XUser, posts: XPost[]): string {
  return `
You are generating a RareImagery storefront config for a creator.

Creator Profile:
- Name: ${profile.name}
- Handle: @${profile.username}
- Bio: ${profile.description ?? 'No bio'}
- Followers: ${profile.public_metrics?.followers_count.toLocaleString() ?? 'Unknown'}
- Verified: ${profile.verified_type !== 'none' ? profile.verified_type : 'No'}
- Location: ${profile.location ?? 'Not specified'}

Recent Posts (tone/content reference):
${posts.slice(0, 5).map((p, i) => `${i + 1}. "${p.text}"`).join('\n')}

Generate a JSON storefront config with:
- theme (one of: y2k_pink, dark_emo, neon_cyber, scene_gold)
- tagline (short punchy line based on their brand)
- about_text (expanded bio, 2-3 sentences)
- store_categories (array, infer from posts/bio: clothing, digital, crafts)
- accent_color (hex, consistent with their X aesthetic)
- music_vibe (descriptor for background music style)

Respond ONLY with valid JSON. No markdown.
`.trim();
}
```

---

## 9. Environment Variables

```bash
# .env.local (Next.js / Vercel)

# X API — Required
X_API_BEARER_TOKEN=         # App-only bearer token from console.x.com
X_API_CLIENT_ID=            # OAuth 2.0 client ID (for future user-context flows)
X_API_CLIENT_SECRET=        # OAuth 2.0 client secret

# X API — Optional monitoring
X_API_SPENDING_LIMIT_USD=50 # Soft cap — alert if exceeded
```

**Important:** Bearer tokens for App-Only access do not expire. Rotate them in console.x.com if compromised. Store in Vercel Environment Variables (not committed to repo).

---

## 10. Cost Management

| Operation | Frequency | Credits est. |
|---|---|---|
| User profile lookup (store creation) | Once per store | Minimal (1 user) |
| Timeline fetch (storefront visit) | Cached 15 min | 10 posts/visit, deduplicated within 24h |
| Batch user import (console) | Occasional | 1 request per 100 users |
| Usage monitoring | Daily cron | Free endpoint |

**Cost controls:**
- Set a spending limit in [console.x.com](https://console.x.com)
- Enable auto-recharge threshold at $5 with $25 top-ups
- Use Drupal field caching (section 6.2) to prevent redundant API calls
- Timeline deduplication: X charges only once per post within a 24-hour UTC window
- Link xAI account in console.x.com to earn 10–20% back as Grok API credits based on spend level

---

## 11. Key Gotchas & Notes

- **api.x.com not api.twitter.com** — The hostname changed. Old URLs still redirect but use the canonical form in code.
- **`verified` field removed** — The old boolean `verified` field is gone. Use `verified_type` which returns `blue`, `business`, `government`, or `none`.
- **`like_count` not `favourites_count`** — v2 renamed this field. The old name doesn't exist in v2.
- **Default fields are minimal** — v2 returns only `id`, `name`, `username` for users and `id`, `text` for posts unless you explicitly request more via `user.fields` and `tweet.fields`.
- **`data` array not `statuses` array** — v2 response root is `data`, not `statuses` (v1.1 style).
- **Media URLs require expansion** — Media does not appear in the post object by default. You must request `expansions=attachments.media_keys` and `media.fields=url,...`.
- **Temperature on xAI reasoning models** — Do NOT pass `temperature` to Grok reasoning model calls. Already flagged in project — applies here too if Grok is used for storefront generation.
- **Free tier missing Like/Follow endpoints** — `POST /2/users/:id/likes`, `POST /2/users/:id/following` etc. removed from free tier August 2025. Any social engagement features require a paid plan.
- **Monthly 2M post read cap** — Pay-per-use plans are hard-capped at 2M post reads/month. Enterprise required above that.
- **OAuth 2.0 scopes are additive** — Request only what you need at auth time. The `tweet.read users.read offline.access` set covers all current RareImagery storefront use cases.
