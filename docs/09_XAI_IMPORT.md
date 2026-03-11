# Step 9: xAI & Grok Import

**Agent:** xAI Import (`xai-import.md`)

## What It Does

When a creator signs in with X, the system:
1. Fetches their X profile (avatar, banner, bio, followers, posts)
2. Sends that data to Grok AI for enhancement
3. Auto-fills the store creation wizard with AI-generated content

## Data Flow

```
X OAuth Login → NextAuth session (xAccessToken, xId)
       ↓
/api/stores/enhance-profile (POST)
       ↓
fetchXData(xAccessToken, xId)  →  X API v2
       ↓
enhanceCreatorProfile(xData)   →  Grok API (grok-3)
       ↓
Returns: { xData, grokEnhancements }
       ↓
StoreBuilderWizard auto-fills all fields
       ↓
/api/stores/create → Drupal (saves creator_x_profile node)
```

## X Data Fetch (`frontend/src/lib/x-import.ts`, 445 LOC)

### `fetchXData(accessToken, userId)`

Calls X API v2 to get:
```typescript
interface XImportData {
  username: string;
  displayName: string;
  bio: string;
  followerCount: number;
  followingCount: number;
  profileImageUrl: string;    // Full-res (remove _normal suffix)
  bannerUrl: string;
  topPosts: XPost[];          // Recent posts sorted by engagement
  topFollowers: XFollower[];  // Notable followers
  metrics: {
    followers: number;
    following: number;
    totalPosts: number;
    avgLikes: number;
    postingFrequency: string;
  };
}
```

### `syncXDataToDrupal(xData, drupalProfileId)`

Writes X data to Drupal JSON:API:
- Uploads profile picture → `field_profile_picture`
- Uploads banner → `field_background_banner`
- PATCHes all text fields (bio, followers, posts, metrics)

### `uploadImageToDrupal(imageUrl)`

Downloads image from X CDN → uploads as Drupal file entity → returns file UUID.

## Grok AI Enhancement (`frontend/src/lib/grok.ts`, 125 LOC)

### `enhanceCreatorProfile(xData)`

Sends X data to Grok with a structured prompt. Returns:

```typescript
interface GrokEnhancements {
  storeBio: string;             // AI-written store description
  suggestedProducts: Array<{    // 3-5 product ideas
    name: string;
    description: string;
    category: string;
  }>;
  recommendedTheme: string;     // Best theme for this creator
  topThemes: string[];          // Content themes from posts
  audienceSentiment: string;    // Audience analysis
}
```

### API Config
```
Endpoint: https://api.x.ai/v1/chat/completions
Model: grok-3
Temperature: 0.7
JSON mode: enabled
```

## API Routes

### `/api/stores/enhance-profile` (POST)
File: `frontend/src/app/api/stores/enhance-profile/route.ts`
1. Validates NextAuth session (must be `creator` role)
2. Gets `xAccessToken` + `xId` from session
3. Calls `fetchXData()` → X API v2
4. Calls `enhanceCreatorProfile()` → Grok API (non-blocking — returns null on failure)
5. Returns `{ xData, grokEnhancements }`

### `/api/stores/import-x-data` (POST)
File: `frontend/src/app/api/stores/import-x-data/route.ts`
- Raw X import without Grok enhancement
- Uploads images directly to Drupal

## Drupal Fields Updated

| Field | Source | Data |
|-------|--------|------|
| field_x_username | X API | Handle |
| field_bio_description | Grok or X API | AI bio or raw bio |
| field_follower_count | X API | Integer |
| field_profile_picture | X API → Drupal file | Uploaded image |
| field_background_banner | X API → Drupal file | Uploaded image |
| field_top_posts | X API | JSON array |
| field_top_followers | X API | JSON array |
| field_metrics | X API + Grok | JSON (includes AI analysis) |

## In the Wizard

The `StoreBuilderWizard` component auto-fills from this data:
- Bio field shows "AI suggested" badge when Grok-generated
- Profile picture and banner preview from X
- Product suggestions shown in a grid with category badges
- Recommended theme pre-selected in ThemeSelector

## Future Upgrade

The blueprint includes a newer approach:
- Switch from `/v1/chat/completions` to `/v1/responses` endpoint
- Use Grok's built-in `x_search` tool (no X API OAuth needed)
- Upgrade model from `grok-3` to `grok-4-1-fast-reasoning`
- This would eliminate the X API v2 dependency entirely

## Next Step

→ [Step 10: Store Creation Flow](10_STORE_CREATION.md)
