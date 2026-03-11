# RareImagery.net Pricing — X Subscription Unlock (2026)

**The easiest way for any X account to get their own storefront page.**

---

## Tier 1: MySpace Vibes Page — Free with X Subscription ($2/month)

Subscribe to **@rareimagery** on X for $2/month and instantly get:

- Your own page at `yourname.rareimagery.net`
- Full MySpace theme: glitter text, marquees, auto-play music, blinking badges, cursor sparkles, Top 8 grid
- Grok auto-imports your real X data (PFP, banner, bio, top posts, top followers, analytics)
- Customize everything from the console: colors, font, background tiles, music, mood, marquee text
- 4 theme preset options (Y2K Pink, Dark Emo, Neon Cyber, Scene Gold) or full manual customization
- Personal updates, tips, and support via X DMs from @rareimagery
- **No products or selling** — pure nostalgic profile page

**How it works:**
1. Subscribe to @rareimagery on X ($2/month)
2. Sign in with X on rareimagery.net
3. System verifies your X subscription automatically
4. Your customizable MySpace page goes live instantly

---

## Tier 2: Full Creator Store ($5 setup + $1/month + 2.9% + $0.30 per sale)

Everything in Tier 1, plus:

- Sell products on your own `yourname.rareimagery.net` store
- Choose from 5 themes: **Default** (dark modern), **Minimal** (clean/light), **Neon** (glassmorphism), **Editorial** (magazine), or **MySpace** (retro)
- Full shopping cart + Stripe checkout
- Grok AI product recommendations based on your audience
- Analytics dashboard with engagement metrics
- Commerce powered by Drupal + Stripe

**Pricing breakdown:**
| Fee | Amount | When |
|-----|--------|------|
| Setup fee | $5.00 | One-time |
| Monthly subscription | $1.00/month | Recurring |
| Processing fee | 2.9% + $0.30 | Per transaction |

---

## Tier 3: Upgrade Anytime

From your dashboard, turn your MySpace page into a full selling store in one click.
Pay the $5 setup fee, pick any theme, and start listing products immediately.

---

## X Subscription Verification Flow

```
User subscribes to @rareimagery on X ($2/month)
          ↓
User visits rareimagery.net → signs in with X (OAuth 2.0)
          ↓
Backend calls X API: GET /2/users/:id/subscriptions
  → checks if user subscribes to @rareimagery
          ↓
If subscribed:
  → Auto-create Creator X Profile in Drupal
  → Grok fetches user's X data (PFP, bio, posts, followers)
  → Page goes live at username.rareimagery.net
          ↓
If not subscribed:
  → Show "Subscribe to @rareimagery on X to unlock your page"
  → Link to X subscription page
```

---

## Technical Implementation

### X Subscription Check
- Uses X API v2 with OAuth 2.0 user context
- Endpoint: Check if authenticated user has an active subscription to @rareimagery
- Cached for 1 hour to avoid rate limits
- Re-verified on each console login

### Auto-provisioning on Subscribe
1. User signs in with X → NextAuth stores `xUsername`, `xId` in JWT
2. POST `/api/stores/provision` checks X subscription status
3. If subscribed → creates Creator X Profile node in Drupal with `store_theme: "myspace"`
4. Grok API call fetches user's X data and populates fields
5. Page is live within seconds at `username.rareimagery.net`

### Upgrade Path
1. User clicks "Upgrade to Store" in console
2. Stripe Checkout session for $5 setup fee
3. On success → creates Commerce Store in Drupal
4. Links store to existing Creator X Profile
5. Enables product listing, cart, and all 5 themes
6. Monthly $1 subscription starts via Stripe

---

## Growth Strategy

This is the viral entry point. The funnel:

1. **X creators see @rareimagery posts** → "Get your own MySpace page"
2. **Subscribe for $2/month on X** → instant page, zero friction
3. **Share their page** → `username.rareimagery.net` → organic discovery
4. **Ready to sell?** → one-click upgrade to full store

The X subscription creates a direct relationship with every creator.
The MySpace nostalgia is the hook. The store is the monetization.

— Powered by Grok @ RareImagery
