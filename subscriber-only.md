# rareimagery.net – X Subscriber-Only Store Onboarding Playbook (Test Rollout)
**Version 1.0 – March 2026**  
**Goal:** Only real paid subscribers to @RareImagery can create a full store on rareimagery.net.  
**Stack:** Drupal 10/11 (users + Store content type) + Next.js (with Grok AI) + X API v2 (OAuth 2.0 User Context)  

This setup is 100% X Terms of Service compliant (Sign in with X + subscriber perks). No scraping, no fake accounts, full consent.

## 1. Prerequisites (Do this once)

### A. X Developer Portal Setup (5 minutes)
1. Go to https://developer.x.com → Apps → Create Project/App
2. App name: `rareimagery-net-store`
3. Set callback URL: `https://your-nextjs-domain.com/api/auth/x/callback`
4. Permissions: **Read + Write** (for future posting) + **User authentication**
5. Generate:
   - API Key & Secret
   - Client ID & Client Secret (OAuth 2.0)
   - Save the **Bearer Token** (for initial testing)
6. Upgrade to **Elevated Access** (free) so you can pull tweets + followers (required for top posts & best followers).

### B. Drupal Setup (10 minutes)
1. Enable core modules:
   - JSON:API
   - REST (optional fallback)
   - File (for images)
2. Install & enable:
   - `jsonapi_extras` (for custom field control)
   - `serialization`
3. Create a **Store** content type (if not already):
   - Title = Store Name (auto-filled from X username)
   - Body = Description
   - Field_x_username (Text)
   - Field_pfp (Image, unlimited)
   - Field_background (Image, unlimited)
   - Field_top_posts (Long text, JSON format)
   - Field_best_followers (Long text, JSON format)
   - Field_status (List: Pending / Approved)
4. On the **User** entity add the same fields (so every X subscriber gets a Drupal account).
5. Create a custom module `rareimagery_x_onboard` (or use Rules + Webhooks) for auto-creation logic (code below).

### C. Next.js Setup (already has Grok AI)
Make sure you have:
```bash
npm install next-auth @twitter-api-v2/oauth2 twitter-api-v2