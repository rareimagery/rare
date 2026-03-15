# RareImagery Dual-AI Architecture
## X Import → Grok + Claude Haiku → Drupal → Next.js Pipeline

---

## 1. System Overview

Two AIs split the workload by what they're best at:

| AI | Role | Why This One | Cost |
|---|---|---|---|
| **Grok (xAI)** | X profile ingestion, bio rewriting, theme suggestion, social graph seeding | Native X integration, understands X culture/creator context | ~$0.002/signup |
| **Claude Haiku 4.5** | Next.js component generation, layout assembly, style generation | Best code generation per dollar, 4–5x faster than Sonnet, 90% of Sonnet quality | $1/M input, $5/M output |

**Cost comparison for site generation (~2K input + ~4K output tokens per site):**
- Haiku 4.5: ~$0.022/site
- Sonnet 4.6: ~$0.066/site (3x more)
- Opus 4.6: ~$0.110/site (5x more)

At 1,000 site generations/month, Haiku costs ~$22. That's negligible.

---

## 2. End-to-End Flow

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Creator on  │────▶│  Next.js App │────▶│  Drupal API  │────▶│  Next.js     │
│  X (Twitter) │     │  (Signup)    │     │  (Backend)   │     │  (Storefront)│
└─────────────┘     └──────┬───────┘     └──────┬───────┘     └──────────────┘
                           │                     │
                    ┌──────▼───────┐      ┌──────▼───────┐
                    │  X OAuth 2.0 │      │  Grok API    │
                    │  + PKCE      │      │  (xAI)       │
                    └──────────────┘      └──────┬───────┘
                                                 │
                                          ┌──────▼───────┐
                                          │ Claude Haiku │
                                          │ 4.5 API      │
                                          └──────────────┘
```

### Step-by-step:

1. **Creator clicks "Sign Up with X"** → Next.js initiates OAuth 2.0 + PKCE
2. **X returns auth code** → Next.js exchanges for access token + refresh token
3. **Next.js pulls X profile data** via X API v2 (`/2/users/me`)
4. **Profile data sent to Grok** → bio rewrite, theme suggestions, category detection
5. **Grok output + X data sent to Claude Haiku** → generates Next.js page components
6. **Drupal creates `creator_site` entity** → stores all config, theme JSON, saved builds
7. **Storefront goes live** at `{username}.rareimagery.net` → no Vercel config needed

---

## 3. X OAuth 2.0 Integration

### 3.1 NextAuth Configuration

```typescript
// app/api/auth/[...nextauth]/route.ts
import NextAuth from "next-auth";
import TwitterProvider from "next-auth/providers/twitter";

const handler = NextAuth({
  providers: [
    TwitterProvider({
      clientId: process.env.X_CLIENT_ID!,
      clientSecret: process.env.X_CLIENT_SECRET!,
      version: "2.0",
      authorization: {
        params: {
          scope: "users.read tweet.read follows.read offline.access",
        },
      },
    }),
  ],
  callbacks: {
    async jwt({ token, account, profile }) {
      if (account) {
        token.accessToken = account.access_token;
        token.refreshToken = account.refresh_token;
        token.xId = account.providerAccountId;
      }
      return token;
    },
    async session({ session, token }) {
      session.accessToken = token.accessToken as string;
      session.xId = token.xId as string;
      return session;
    },
  },
});

export { handler as GET, handler as POST };
```

### 3.2 X Profile Data Pull

```typescript
// lib/x-api.ts
interface XProfileData {
  id: string;
  name: string;
  username: string;
  description: string;
  profile_image_url: string;
  profile_banner_url?: string;
  public_metrics: {
    followers_count: number;
    following_count: number;
    tweet_count: number;
  };
  location?: string;
  url?: string;
  pinned_tweet_id?: string;
}

export async function fetchXProfile(accessToken: string): Promise<XProfileData> {
  const response = await fetch(
    "https://api.x.com/2/users/me?" +
    new URLSearchParams({
      "user.fields": [
        "created_at",
        "description",
        "entities",
        "location",
        "name",
        "pinned_tweet_id",
        "profile_image_url",
        "profile_banner_url",
        "public_metrics",
        "url",
        "username",
      ].join(","),
    }),
    {
      headers: { Authorization: `Bearer ${accessToken}` },
    }
  );

  if (!response.ok) {
    throw new Error(`X API error: ${response.status}`);
  }

  const { data } = await response.json();
  return data;
}

export async function fetchXFollowing(
  accessToken: string,
  userId: string,
  maxResults = 100
): Promise<string[]> {
  const response = await fetch(
    `https://api.x.com/2/users/${userId}/following?max_results=${maxResults}&user.fields=username`,
    {
      headers: { Authorization: `Bearer ${accessToken}` },
    }
  );

  if (!response.ok) {
    throw new Error(`X API error: ${response.status}`);
  }

  const { data } = await response.json();
  return data?.map((u: any) => u.username) ?? [];
}
```

### 3.3 Required X API Scopes

| Scope | Purpose |
|---|---|
| `users.read` | Profile data, avatar, banner, bio, metrics |
| `tweet.read` | Pinned tweet for storefront hero content |
| `follows.read` | Seed the social graph (mutual follows → Friends) |
| `offline.access` | Refresh token for background syncs |

**X API Tier needed:** Basic ($200/month) gives 10K reads/month — enough for ~10K signups/month before needing Pro.

---

## 4. AI Pipeline: Grok → Haiku Handoff

### 4.1 Grok's Job (Profile Intelligence)

```typescript
// lib/ai/grok-profile.ts
import { XProfileData } from "@/lib/x-api";

interface GrokProfileOutput {
  rewrittenBio: string;          // Polished storefront bio
  suggestedCategory: string;     // "artist" | "musician" | "designer" | "writer" | etc.
  suggestedThemePreset: string;  // "y2k_pink" | "dark_emo" | "neon_cyber" | "scene_gold"
  brandKeywords: string[];       // Extracted from bio + pinned tweet
  colorMood: string;             // "warm" | "cool" | "dark" | "vibrant"
  audienceType: string;          // "creative" | "tech" | "lifestyle" | "music"
}

export async function analyzeXProfile(
  profile: XProfileData,
  pinnedTweetText?: string
): Promise<GrokProfileOutput> {
  const response = await fetch("https://api.x.ai/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${process.env.XAI_API_KEY}`,
    },
    body: JSON.stringify({
      model: "grok-3-mini",  // cheapest, sufficient for profile analysis
      messages: [
        {
          role: "system",
          content: `You are a creative director for a Y2K/MySpace-era creator platform.
Analyze this X profile and return JSON only (no markdown, no preamble):
{
  "rewrittenBio": "A punchy storefront bio (max 160 chars)",
  "suggestedCategory": "one of: artist, musician, designer, writer, photographer, maker, creator",
  "suggestedThemePreset": "one of: y2k_pink, dark_emo, neon_cyber, scene_gold",
  "brandKeywords": ["keyword1", "keyword2", "keyword3"],
  "colorMood": "one of: warm, cool, dark, vibrant",
  "audienceType": "one of: creative, tech, lifestyle, music"
}`,
        },
        {
          role: "user",
          content: `Username: @${profile.username}
Bio: ${profile.description}
Followers: ${profile.public_metrics.followers_count}
Location: ${profile.location || "N/A"}
Pinned tweet: ${pinnedTweetText || "N/A"}`,
        },
      ],
    }),
  });

  const data = await response.json();
  return JSON.parse(data.choices[0].message.content);
}
```

### 4.2 Claude Haiku's Job (Site Generation)

```typescript
// lib/ai/haiku-site-gen.ts
import Anthropic from "@anthropic-ai/sdk";
import { GrokProfileOutput } from "./grok-profile";
import { XProfileData } from "@/lib/x-api";

const anthropic = new Anthropic({
  apiKey: process.env.ANTHROPIC_API_KEY,
});

interface GeneratedSiteComponents {
  heroSection: string;      // JSX string
  aboutSection: string;     // JSX string
  layoutConfig: object;     // grid/flex configuration
  customCSS: string;        // Tailwind + custom properties
  themeOverrides: object;   // Color/font overrides on top of preset
}

export async function generateSiteComponents(
  profile: XProfileData,
  grokAnalysis: GrokProfileOutput
): Promise<GeneratedSiteComponents> {
  const message = await anthropic.messages.create({
    model: "claude-haiku-4-5-20251001",
    max_tokens: 4096,
    messages: [
      {
        role: "user",
        content: `Generate Next.js/Tailwind storefront components for a creator.

CREATOR DATA:
- Name: ${profile.name}
- Username: @${profile.username}
- Bio: ${grokAnalysis.rewrittenBio}
- Category: ${grokAnalysis.suggestedCategory}
- Theme preset: ${grokAnalysis.suggestedThemePreset}
- Color mood: ${grokAnalysis.colorMood}
- Brand keywords: ${grokAnalysis.brandKeywords.join(", ")}
- Avatar URL: ${profile.profile_image_url}
- Banner URL: ${profile.profile_banner_url || "none"}
- Follower count: ${profile.public_metrics.followers_count}

REQUIREMENTS:
- Y2K/MySpace aesthetic matching the ${grokAnalysis.suggestedThemePreset} preset
- Responsive (mobile-first)
- Use Tailwind utility classes only
- Components must be self-contained JSX strings
- Include hover effects and micro-animations via Tailwind

Return JSON only (no markdown fences):
{
  "heroSection": "<JSX string for hero with avatar, name, bio>",
  "aboutSection": "<JSX string for about/links section>",
  "layoutConfig": { "type": "grid|flex", "columns": 1-3, "gap": "..." },
  "customCSS": "additional Tailwind @apply rules or CSS custom properties",
  "themeOverrides": { "primaryColor": "...", "accentColor": "...", "fontFamily": "..." }
}`,
      },
    ],
  });

  const textContent = message.content.find((block) => block.type === "text");
  if (!textContent || textContent.type !== "text") {
    throw new Error("No text response from Haiku");
  }

  return JSON.parse(textContent.text);
}
```

### 4.3 The Orchestrator Route

```typescript
// app/api/site/generate/route.ts
import { NextRequest, NextResponse } from "next/server";
import { getServerSession } from "next-auth";
import { fetchXProfile, fetchXFollowing } from "@/lib/x-api";
import { analyzeXProfile } from "@/lib/ai/grok-profile";
import { generateSiteComponents } from "@/lib/ai/haiku-site-gen";
import { createCreatorSite, seedSocialGraph } from "@/lib/drupal";

export async function POST(req: NextRequest) {
  // 1. Auth check
  const session = await getServerSession();
  if (!session?.accessToken) {
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  }

  try {
    // 2. Pull X profile data
    const xProfile = await fetchXProfile(session.accessToken);

    // 3. Pull following list for social graph seeding
    const following = await fetchXFollowing(session.accessToken, xProfile.id);

    // 4. Grok analyzes the profile (AI #1)
    const grokAnalysis = await analyzeXProfile(xProfile);

    // 5. Claude Haiku generates site components (AI #2)
    const siteComponents = await generateSiteComponents(xProfile, grokAnalysis);

    // 6. Create creator_site entity in Drupal
    const creatorSite = await createCreatorSite({
      x_username: xProfile.username,
      x_id: xProfile.id,
      display_name: xProfile.name,
      bio: grokAnalysis.rewrittenBio,
      avatar_url: xProfile.profile_image_url,
      banner_url: xProfile.profile_banner_url,
      category: grokAnalysis.suggestedCategory,
      theme_preset: grokAnalysis.suggestedThemePreset,
      // Saved build — stored as JSON, eliminates future API costs
      page_builds: JSON.stringify({
        version: 1,
        generated_at: new Date().toISOString(),
        components: siteComponents,
        grok_analysis: grokAnalysis,
      }),
      follower_count: xProfile.public_metrics.followers_count,
    });

    // 7. Seed social graph (background, non-blocking)
    seedSocialGraph(xProfile.id, following).catch(console.error);

    return NextResponse.json({
      success: true,
      subdomain: `${xProfile.username}.rareimagery.net`,
      site_id: creatorSite.id,
    });
  } catch (error) {
    console.error("Site generation failed:", error);
    return NextResponse.json(
      { error: "Site generation failed" },
      { status: 500 }
    );
  }
}
```

---

## 5. Drupal `creator_site` Entity — Field Management

### 5.1 Entity Fields (All Creator-Editable)

```
creator_site
├── field_x_username          (string, readonly — from X import)
├── field_x_id                (string, readonly — X user ID)
├── field_display_name        (string, editable)
├── field_bio                 (text_long, editable)
├── field_avatar_url          (string, editable — can upload custom)
├── field_banner_url          (string, editable — can upload custom)
├── field_category            (list_string, editable)
├── field_theme_preset        (list_string, editable)
├── field_theme_overrides     (text_long/JSON, editable — full theme customization)
├── field_page_builds         (text_long/JSON, editable — saved generated components)
├── field_custom_links        (link, multi-value, editable)
├── field_social_links        (link, multi-value, editable)
├── field_seo_title           (string, editable)
├── field_seo_description     (string, editable)
├── field_custom_css          (text_long, editable — power users)
├── field_follower_count      (integer, system-updated)
├── field_site_status         (list_string: active/paused/suspended)
├── field_commerce_enabled    (boolean — activates commerce_store child)
├── field_notification_prefs  (text_long/JSON — email/SMS settings)
└── field_created             (datetime, readonly)
```

### 5.2 Drupal REST API — PATCH for Field Updates

```typescript
// lib/drupal.ts
const DRUPAL_BASE = process.env.DRUPAL_API_URL; // e.g., https://api.rareimagery.net

export async function updateCreatorSite(
  siteId: string,
  fields: Record<string, any>,
  authToken: string
) {
  const response = await fetch(`${DRUPAL_BASE}/jsonapi/node/creator_site/${siteId}`, {
    method: "PATCH",
    headers: {
      "Content-Type": "application/vnd.api+json",
      Authorization: `Bearer ${authToken}`,
    },
    body: JSON.stringify({
      data: {
        type: "node--creator_site",
        id: siteId,
        attributes: fields,
      },
    }),
  });

  if (!response.ok) {
    throw new Error(`Drupal PATCH failed: ${response.status}`);
  }

  return response.json();
}

export async function createCreatorSite(data: Record<string, any>) {
  const response = await fetch(`${DRUPAL_BASE}/jsonapi/node/creator_site`, {
    method: "POST",
    headers: {
      "Content-Type": "application/vnd.api+json",
      Authorization: `Bearer ${process.env.DRUPAL_SERVICE_TOKEN}`,
    },
    body: JSON.stringify({
      data: {
        type: "node--creator_site",
        attributes: {
          title: data.x_username,
          field_x_username: data.x_username,
          field_x_id: data.x_id,
          field_display_name: data.display_name,
          field_bio: { value: data.bio, format: "plain_text" },
          field_avatar_url: data.avatar_url,
          field_banner_url: data.banner_url,
          field_category: data.category,
          field_theme_preset: data.theme_preset,
          field_page_builds: { value: data.page_builds, format: "plain_text" },
          field_follower_count: data.follower_count,
          field_site_status: "active",
          field_commerce_enabled: false,
        },
      },
    }),
  });

  return response.json();
}

export async function seedSocialGraph(userId: string, followingUsernames: string[]) {
  // Batch-check which usernames already exist as creator_sites
  // Create follow relationships for matches (mutual follows = Friends)
  const response = await fetch(`${DRUPAL_BASE}/api/social/seed-graph`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${process.env.DRUPAL_SERVICE_TOKEN}`,
    },
    body: JSON.stringify({
      user_x_id: userId,
      following_usernames: followingUsernames,
    }),
  });

  return response.json();
}
```

---

## 6. Creator Site Management — FloatingBuilder Integration

### 6.1 Field Management Panel

The FloatingBuilder already exists. Here's how all fields map to editable UI:

```typescript
// components/FloatingBuilder/SiteFieldsPanel.tsx
"use client";

import { useState, useEffect } from "react";
import { useSession } from "next-auth/react";

interface CreatorSiteFields {
  display_name: string;
  bio: string;
  avatar_url: string;
  banner_url: string;
  category: string;
  theme_preset: string;
  theme_overrides: Record<string, string>;
  custom_links: Array<{ title: string; url: string }>;
  social_links: Array<{ platform: string; url: string }>;
  seo_title: string;
  seo_description: string;
  custom_css: string;
  commerce_enabled: boolean;
  notification_prefs: { email: boolean; sms: boolean };
}

// Category options match Drupal list_string allowed values
const CATEGORIES = [
  "artist", "musician", "designer", "writer",
  "photographer", "maker", "creator",
] as const;

const THEME_PRESETS = [
  "y2k_pink", "dark_emo", "neon_cyber", "scene_gold",
] as const;

export function SiteFieldsPanel({ siteId }: { siteId: string }) {
  const { data: session } = useSession();
  const [fields, setFields] = useState<CreatorSiteFields | null>(null);
  const [saving, setSaving] = useState(false);
  const [dirty, setDirty] = useState<Set<string>>(new Set());

  useEffect(() => {
    // Fetch current field values from Drupal
    fetchSiteFields(siteId).then(setFields);
  }, [siteId]);

  const updateField = (key: keyof CreatorSiteFields, value: any) => {
    setFields((prev) => prev ? { ...prev, [key]: value } : null);
    setDirty((prev) => new Set(prev).add(key));
  };

  const saveChanges = async () => {
    if (!fields || dirty.size === 0) return;
    setSaving(true);

    // Only PATCH dirty fields — minimizes payload
    const patch: Record<string, any> = {};
    for (const key of dirty) {
      const fieldKey = `field_${key}`;
      const value = fields[key as keyof CreatorSiteFields];

      if (key === "bio") {
        patch[fieldKey] = { value, format: "plain_text" };
      } else if (typeof value === "object") {
        patch[fieldKey] = { value: JSON.stringify(value), format: "plain_text" };
      } else {
        patch[fieldKey] = value;
      }
    }

    await updateCreatorSite(siteId, patch, session!.accessToken);
    setDirty(new Set());
    setSaving(false);
  };

  // All fields exposed to creator — nothing hidden
  // UI renders form controls per field type
  // ...
}
```

### 6.2 What Creators Can Manage

| Field | Control Type | Notes |
|---|---|---|
| Display Name | Text input | Defaults to X name |
| Bio | Textarea (160 char) | Grok-rewritten default, fully editable |
| Avatar | Image upload + URL | Falls back to X avatar |
| Banner | Image upload + URL | Falls back to X banner |
| Category | Dropdown | Affects Discover page placement |
| Theme Preset | Visual selector | 4 Y2K presets with live preview |
| Theme Overrides | Color pickers + font selector | primaryColor, accentColor, fontFamily |
| Custom Links | Repeatable link fields | "My links" section on storefront |
| Social Links | Platform picker + URL | Auto-icons for X, IG, TikTok, etc. |
| SEO Title | Text input | `<title>` tag override |
| SEO Description | Textarea | `<meta description>` override |
| Custom CSS | Code editor (power users) | Scoped to their storefront only |
| Commerce Toggle | Switch | Activates `commerce_store` child entity |
| Notification Prefs | Toggles | Email (Brevo) / SMS (Telnyx) |

### 6.3 Regenerate vs Edit

Creators have two paths at all times:
1. **AI Regenerate** — hits Grok → Haiku pipeline again, overwrites `field_page_builds`
2. **Manual Edit** — directly edits any field via FloatingBuilder, PATCH to Drupal
3. **Saved Builds** — can switch between previously generated builds (stored as JSON array)

---

## 7. Auth & Login Flow

### 7.1 NextAuth Session → Drupal Token Bridge

```typescript
// lib/auth/drupal-bridge.ts

/**
 * After NextAuth authenticates via X OAuth 2.0,
 * we need a corresponding Drupal session/JWT for API writes.
 *
 * Strategy: Drupal Simple OAuth module issues JWT tokens.
 * On first X login, auto-provision Drupal user.
 */
export async function ensureDrupalUser(xProfile: {
  id: string;
  username: string;
  name: string;
  email?: string;
}): Promise<{ drupalUid: string; drupalToken: string }> {
  // Check if Drupal user exists for this X ID
  const existing = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/user/user?filter[field_x_id]=${xProfile.id}`,
    {
      headers: {
        Authorization: `Bearer ${process.env.DRUPAL_SERVICE_TOKEN}`,
      },
    }
  );

  const { data } = await existing.json();

  if (data.length > 0) {
    // Existing user — issue fresh token
    const token = await issueDrupalToken(data[0].id);
    return { drupalUid: data[0].id, drupalToken: token };
  }

  // New user — create Drupal account
  const newUser = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/user/user`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/vnd.api+json",
        Authorization: `Bearer ${process.env.DRUPAL_SERVICE_TOKEN}`,
      },
      body: JSON.stringify({
        data: {
          type: "user--user",
          attributes: {
            name: xProfile.username,
            mail: xProfile.email || `${xProfile.username}@rareimagery.placeholder`,
            field_x_id: xProfile.id,
            field_x_username: xProfile.username,
            field_display_name: xProfile.name,
            status: true,
          },
        },
      }),
    }
  );

  const created = await newUser.json();
  const token = await issueDrupalToken(created.data.id);
  return { drupalUid: created.data.id, drupalToken: token };
}

async function issueDrupalToken(drupalUid: string): Promise<string> {
  // Uses Drupal Simple OAuth to issue a scoped JWT
  // Scoped to: edit own creator_site, manage own commerce_store
  const response = await fetch(`${process.env.DRUPAL_API_URL}/oauth/token`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "client_credentials",
      client_id: process.env.DRUPAL_OAUTH_CLIENT_ID!,
      client_secret: process.env.DRUPAL_OAUTH_CLIENT_SECRET!,
      scope: "creator",
      // Custom: associate token with specific Drupal user
      user_id: drupalUid,
    }),
  });

  const { access_token } = await response.json();
  return access_token;
}
```

### 7.2 Session Architecture

```
Browser (Creator)
  └── NextAuth session (X OAuth 2.0 token + Drupal JWT)
        ├── X API calls → uses X access_token (read profile, follows)
        └── Drupal API calls → uses Drupal JWT (CRUD on own creator_site)
```

**Token storage:** NextAuth encrypted cookie. Drupal JWT stored in the NextAuth `jwt` callback alongside the X tokens. Both refresh independently.

---

## 8. Environment Variables

```bash
# X (Twitter) OAuth 2.0
X_CLIENT_ID=
X_CLIENT_SECRET=

# xAI / Grok
XAI_API_KEY=

# Anthropic / Claude Haiku
ANTHROPIC_API_KEY=

# Drupal Backend
DRUPAL_API_URL=https://api.rareimagery.net
DRUPAL_SERVICE_TOKEN=           # Server-to-server (user provisioning)
DRUPAL_OAUTH_CLIENT_ID=         # Simple OAuth client
DRUPAL_OAUTH_CLIENT_SECRET=

# Stripe
STRIPE_SECRET_KEY=
STRIPE_SETUP_PRICE_ID=          # $5 one-time
STRIPE_MONTHLY_PRICE_ID=        # $2/month

# NextAuth
NEXTAUTH_SECRET=
NEXTAUTH_URL=https://rareimagery.net

# Notifications
BREVO_API_KEY=
TELNYX_API_KEY=
```

---

## 9. Cost Per Signup (Total AI Spend)

| Step | API | Est. Cost |
|---|---|---|
| X profile pull | X API v2 (Basic tier) | Included in $200/mo flat |
| X following pull | X API v2 (Basic tier) | Included above |
| Grok profile analysis | grok-3-mini | ~$0.002 |
| Haiku site generation | claude-haiku-4-5 | ~$0.022 |
| **Total per signup** | | **~$0.024** |

At 500 signups/month: **~$12 in AI costs** + $200 X API = $212 total API spend.

---

## 10. Validation Checklist

- [ ] X OAuth 2.0 + PKCE flow returns access_token + refresh_token
- [ ] X API v2 `/2/users/me` returns all user.fields needed
- [ ] Grok API returns valid JSON (grok-3-mini, not a reasoning model — no temperature issues)
- [ ] Claude Haiku 4.5 returns valid JSON component definitions
- [ ] Drupal creates `creator_site` entity with all fields populated
- [ ] Wildcard subdomain resolves to correct creator's storefront
- [ ] FloatingBuilder can PATCH every editable field
- [ ] Saved builds persist and are switchable
- [ ] Drupal user auto-provisioned on first X login
- [ ] Dual-token session (X + Drupal JWT) works in NextAuth
- [ ] Commerce toggle creates child `commerce_store` on demand
- [ ] Social graph seeding runs non-blocking after signup
