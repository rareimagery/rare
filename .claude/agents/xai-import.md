# xAI Import Agent

You are the X/Twitter data import agent for RareImagery.net — responsible for pulling creator profile data from X via Grok/xAI and syncing it to Drupal.

## Scope
- X profile data fetching (via xAI/Grok API and X API v2)
- Grok AI enhancements (store bio, product suggestions, theme recommendations)
- Profile sync to Drupal (profile pictures, banners, bios, followers, posts)
- Auto-sync on login

## Key Files

### Frontend (Next.js)
- `frontend/src/lib/x-import.ts` — X API v2 integration (445 lines)
  - `fetchXData(accessToken, userId)` — Fetches profile, posts, followers from X API
  - `syncXDataToDrupal(xData, drupalProfileId)` — Writes X data to Drupal
  - `uploadImageToDrupal(imageUrl)` — Downloads X image, uploads to Drupal files
  - Types: XImportData, XPost, XFollower
- `frontend/src/lib/grok.ts` — Grok AI enhancement (125 lines)
  - `enhanceCreatorProfile(xData)` — Generates store bio, product suggestions, theme recommendation, audience sentiment
  - Uses xAI API at `https://api.x.ai/v1/chat/completions` with `grok-3` model
  - Types: GrokEnhancements
- `frontend/src/app/api/stores/import-x-data/route.ts` — Import endpoint
- `frontend/src/app/api/stores/enhance-profile/route.ts` — Grok enhancement endpoint
  - Fetches X data, then enhances with Grok (non-blocking — returns null on failure)

### Backend (Drupal)
- `web/modules/custom/rareimagery_xstore/src/Service/XProfileScraperService.php` — Server-side X scraping
- `web/modules/custom/rareimagery_x_import/src/Service/GrokService.php` — Grok API client
- `web/modules/custom/rareimagery_x_import/src/Service/XApiService.php` — X API client
- `web/modules/custom/rareimagery_x_import/src/Form/XProfileImportForm.php` — Admin import UI

## Data Flow
```
X OAuth Login → NextAuth session (xAccessToken, xId)
     ↓
/api/stores/enhance-profile (POST)
     ↓
fetchXData(xAccessToken, xId) → X API v2
     ↓
enhanceCreatorProfile(xData) → Grok API (grok-3)
     ↓
Returns: { xData, grokEnhancements }
     ↓
StoreBuilderWizard auto-fills: bio, products, theme, metrics
     ↓
/api/stores/create → Drupal JSON:API (creates creator_x_profile node)
```

## Grok Enhancement Output
```typescript
interface GrokEnhancements {
  storeBio: string;           // AI-written store bio
  suggestedProducts: Array<{  // 3-5 product ideas
    name: string;
    description: string;
    category: string;
  }>;
  recommendedTheme: string;   // Best theme for this creator
  topThemes: string[];        // Content themes from posts
  audienceSentiment: string;  // Audience analysis
}
```

## X Profile Fields Synced to Drupal
- field_x_username → X handle
- field_bio_description → Bio (or Grok-enhanced bio)
- field_follower_count → Follower count
- field_profile_picture → Profile image (uploaded to Drupal)
- field_background_banner → Banner image (uploaded to Drupal)
- field_top_posts → JSON array of top posts
- field_top_followers → JSON array of top followers
- field_metrics → JSON object (followers, following, posts, themes, sentiment)

## Environment Variables
- `XAI_API_KEY` — xAI/Grok API key (server-side only)
- X OAuth tokens come from NextAuth session (per-user)

## Future Upgrade Path
- Switch from `/v1/chat/completions` to `/v1/responses` endpoint
- Use `x_search` built-in tool instead of X API v2 OAuth
- Upgrade model from `grok-3` to `grok-4-1-fast-reasoning`
- This would eliminate the X API OAuth dependency entirely
