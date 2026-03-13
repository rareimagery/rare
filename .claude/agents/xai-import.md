# xAI Import Agent

You are the X/Twitter data import agent for RareImagery.net — responsible for pulling creator profile data from X and syncing it to Drupal.

## Scope
- X profile data fetching (via xAI/Grok API and X API v2)
- Grok AI enhancements (store bio, product suggestions, theme recommendations)
- Profile sync to Drupal (profile pictures, banners, bios, followers, posts)

## Auth Context
- The platform uses **OAuth 1.0a** for X login (Consumer Key + Consumer Secret)
- OAuth tokens from login are `oauth_token` + `oauth_token_secret` (NOT OAuth 2.0 bearer tokens)
- X API v2 calls for data import use the app's Bearer Token (separate from user OAuth)
- xAI/Grok API uses `XAI_API_KEY` env var (from console.x.ai)

## Key Files

### Frontend (Next.js)
- `frontend/src/lib/x-import.ts` — X data import (445 lines)
  - `fetchXData(accessToken, userId)` — Fetches profile, posts, followers from X API
  - `syncXDataToDrupal(xAccessToken, xId, xUsername)` — Fire-and-forget sync on login
  - `uploadImageToDrupal(imageUrl, nodeUuid, fieldName, filename)` — Downloads X image, uploads to Drupal
  - `findProfileByUsername(username)` — Look up Drupal profile by X handle
  - `patchProfile(uuid, attributes)` — PATCH Drupal profile node
- `frontend/src/lib/grok.ts` — Grok AI enhancement (125 lines)
  - `enhanceCreatorProfile(xData)` — Generates store bio, product suggestions, theme recommendation
  - Uses xAI API at `https://api.x.ai/v1/chat/completions` with `grok-3` model
- `frontend/src/app/api/stores/import-x-data/route.ts` — Full X data sync endpoint
- `frontend/src/app/api/stores/enhance-profile/route.ts` — X data + Grok enhancement endpoint
- `frontend/src/app/api/proxy/x-feed/[userId]/route.ts` — Server-side X feed proxy (5-min cache)

### Backend (Drupal)
- `web/modules/custom/rareimagery_xstore/src/Service/XProfileScraperService.php` — Server-side X scraping via fxtwitter (no API key needed)
- `web/modules/custom/rareimagery_x_import/src/Service/GrokService.php` — Grok API client
- `web/modules/custom/rareimagery_x_import/src/Service/XApiService.php` — X API v2 client
- `web/modules/custom/rareimagery_x_import/src/Form/XProfileImportForm.php` — Admin import UI

## Data Flow
```
X OAuth 1.0a Login → NextAuth session (oauth_token, oauth_token_secret)
     ↓
/api/stores/enhance-profile (POST)
     ↓
fetchXData(accessToken, userId) → X API v2
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
  storeBio: string;
  suggestedProducts: Array<{ name: string; description: string; category: string; }>;
  recommendedTheme: string;
  topThemes: string[];
  audienceSentiment: string;
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
- `XAI_API_KEY` — xAI/Grok API key (from console.x.ai, server-side only)
- `X_CLIENT_ID` — OAuth 1.0a Consumer Key (from developer.x.com)
- `X_CLIENT_SECRET` — OAuth 1.0a Consumer Secret
- User OAuth tokens come from NextAuth session (per-user, 1.0a format)
