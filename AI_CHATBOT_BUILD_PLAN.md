# X Marketplace AI Chatbot Build Plan — Haiku-Powered Page Creator

**Project:** RareImagery (rareimagery.net)
**Goal:** Conversational chatbot in the seller dashboard — any X creator types a prompt, gets a full Tailwind storefront page, one-click publishes it, and auto-posts to X. Powered by Claude Haiku (cheapest model), Grok fallback ready.

---

## Inventory: What's Built vs. What's New

### Already Built (Existing Infrastructure)

| Component | Status | Details |
|-----------|--------|---------|
| Drupal 10.3 + PostgreSQL backend | ✅ Live | Hostinger KVM2 Ubuntu VPS, JSON:API headless |
| Next.js 15 App Router frontend | ✅ Live | Vercel Pro, TypeScript + Tailwind CSS |
| Cloudflare wildcard subdomains | ✅ Live | One-time DNS setup done; new storefronts = DB ops only |
| X OAuth creator login | ✅ Live | NextAuth for console protection |
| Stripe payments | ✅ Live | $5 setup + $2/mo via `add_invoice_items` |
| Grok (xAI) API integration | ✅ Live | Used for X profile imports + AI theme generation |
| MySpace theme bot | ✅ Live | JSON theme config output → Next.js renders; 10 subculture presets with full palettes |
| `FloatingBuilder.tsx` | ✅ Live | Draggable panel on live storefront — Generate / Preview / Saved Builds tabs |
| `field_page_builds` (Drupal) | ✅ Live | Saved builds stored as JSON on creator nodes — eliminates repeat API costs |
| Subculture presets | ✅ Live | Emo, scene kid, pop princess, hip-hop, indie, gamer/neon, cottagecore, Y2K, goth, skate — all with hex palettes + typography + decoration rules |
| Hard design limits | ✅ Enforced | Mobile responsive, products above fold, no autoplay audio, reduced-motion fallback always on |
| `MYSPACE_THEME_BOT_RULES.md` | ✅ Documented | Full theme bot output spec |
| `best_creations.json` | ✅ Scaffolded | Grows as creators rate themes in console |

### Needs To Be Built (New Work)

| Component | Phase | Why It's New |
|-----------|-------|--------------|
| Claude Haiku provider (`ClaudeProvider.php`) | 1 | No Anthropic integration exists — only Grok today |
| `ai_provider` Drupal service layer | 1 | Need a proper provider abstraction to route between Claude and Grok by task type |
| `chatbot_page_builder` task type | 1 | New routing key — distinct from existing theme generation |
| Marketplace-specific system prompt | 2 | Current Grok prompts target theme JSON; this needs full Tailwind component output with commerce hooks |
| "Post to X" auto-thread generator | 2 | No X write/post integration exists — only read (OAuth import) |
| `SellerChatbot.tsx` | 3 | Different from `FloatingBuilder.tsx` — this is a conversational chat bubble with streaming, not a builder panel |
| `/api/ai/generate` proxy endpoint | 3 | Needs to exist or be extended to accept chatbot task type and route to the correct provider |
| Live preview pane (iframe/React renderer) | 3 | `FloatingBuilder` has preview; chatbot needs its own inline preview tied to streaming output |
| "Apply to my storefront" save action | 3 | Bridge from chatbot output → `field_page_builds` on the creator's node |
| Token usage logging per creator | 4 | No usage metering exists today |
| Rate limiting (3 free / day, premium unlimited) | 4 | No tiered generation limits exist |
| Premium billing toggle | 4 | Current billing is flat $5+$2/mo; this is a new upsell SKU |
| Image compression hooks in output | 2 | Generated components need `next/image` optimization + lazy loading baked in |

---

## Phase 0: Sync & Prep

```bash
git pull && composer update && drush cr
```

**Additional prep before Phase 1:**

- Confirm current Drupal module structure — identify where `ai_provider` service will live (new custom module `rareimagery_ai` or extend existing)
- Verify Grok integration entry points so the new abstraction layer wraps them cleanly without breaking current theme generation
- Get Anthropic API key provisioned; confirm Haiku model string (currently `claude-3-5-haiku-20241022` — verify latest/cheapest at build time)
- Audit existing `/api/ai/generate` route in Next.js (if it exists) or confirm it needs to be created

---

## Phase 1: AI Provider Abstraction + Haiku Integration

### 1A: Drupal Service Layer — `ai_provider`

**What:** A pluggable provider pattern so any AI task routes to the right model/vendor.

**File: `ai_provider.services.yml`**

```yaml
services:
  ai_provider.manager:
    class: Drupal\rareimagery_ai\AiProviderManager
    arguments: ['@config.factory']

  ai_provider.claude:
    class: Drupal\rareimagery_ai\Provider\ClaudeProvider
    arguments: ['@http_client', '@config.factory', '@logger.factory']
    tags:
      - { name: ai_provider, priority: 10 }

  ai_provider.grok:
    class: Drupal\rareimagery_ai\Provider\GrokProvider
    arguments: ['@http_client', '@config.factory', '@logger.factory']
    tags:
      - { name: ai_provider, priority: 5 }
```

**Routing logic in `AiProviderManager`:**

| `task_type` | Primary Provider | Fallback |
|-------------|-----------------|----------|
| `theme_generation` | Grok (existing, don't touch) | Claude Haiku |
| `profile_import` | Grok (existing, don't touch) | — |
| `chatbot_page_builder` | Claude Haiku | Grok |
| `x_post_generator` | Claude Haiku | Grok |

**Key:** Existing Grok flows stay untouched. Haiku is the default for new chatbot tasks. Grok is wired as fallback if Haiku returns an error or times out.

### 1B: `ClaudeProvider.php`

```
Drupal\rareimagery_ai\Provider\ClaudeProvider
```

- Implements `AiProviderInterface` (shared contract with `GrokProvider`)
- Default model: `claude-3-5-haiku-20241022` (env var `CLAUDE_MODEL` — no redeploy to swap)
- API key from env var `ANTHROPIC_API_KEY` (same pattern as existing Grok key)
- Methods: `generate(string $taskType, array $messages, array $options): AiResponse`
- Streaming support: SSE passthrough for chatbot use case
- Error handling: On 429/500/timeout → `AiProviderManager` catches and retries with Grok fallback

### 1C: Extend `GrokProvider.php`

- Add `chatbot_page_builder` and `x_post_generator` to its supported task types
- Same `AiProviderInterface` contract
- No changes to existing `theme_generation` or `profile_import` paths

---

## Phase 2: System Prompt — Marketplace Page Builder

### 2A: Core System Prompt (`chatbot_page_builder`)

Stored in Drupal config (not hardcoded) so it's editable without deploys.

**Prompt must enforce:**

1. **Output format:** JSON-only response — a structured object containing Tailwind component blocks that Next.js renders directly. Same pattern as existing theme bot (JSON config, not raw JSX/CSS).

2. **Required sections in every generated page:**
   - `product_carousel` — above the fold, always
   - `hero_section` — creator branding, tagline, profile image pulled from X import data
   - `product_grid` — below carousel, responsive grid
   - `x_share_button` — native X intent URL, pre-filled with storefront link + creator handle
   - `stripe_checkout_hook` — product cards wired to existing Stripe checkout flow
   - `image_optimization` — all `<img>` tags output as `next/image` with `loading="lazy"`, `sizes`, and `quality={80}` compression

3. **Subculture awareness:** Prompt has access to the 10 existing presets from `MYSPACE_THEME_BOT_RULES.md`. When a creator says "dark emo" or "cottagecore," the system prompt injects the matching palette/typography/decoration rules as context.

4. **Hard limits (carry forward from existing rules):**
   - Mobile responsive — all components must work at 375px
   - Products above the fold — no exceptions
   - No autoplay audio
   - `prefers-reduced-motion` fallback always included
   - Accessible — minimum contrast ratios, semantic HTML in the component structure

### 2B: "Post to X" Thread Generator (`x_post_generator`)

**New capability — no X write integration exists today.**

- Input: Generated storefront URL + creator profile data (from X import)
- Output: JSON array of 1–3 tweet-length strings (280 char max each)
- Includes: Storefront link, product highlight, creator handle mention, relevant hashtags
- **Does NOT auto-post.** Generates the thread text → creator reviews in the chatbot UI → clicks "Post to X" which opens X's native compose intent URL with pre-filled text
- Fully compliant with X's rules — uses `https://twitter.com/intent/tweet?text=...` pattern, no unauthorized API writes

---

## Phase 3: Chatbot UI in Seller Dashboard

### 3A: `SellerChatbot.tsx`

**This is NOT a replacement for `FloatingBuilder.tsx`.** Different component, different purpose.

| | `FloatingBuilder.tsx` (Existing) | `SellerChatbot.tsx` (New) |
|---|---|---|
| **UX model** | Builder panel — Generate/Preview/Saved Builds tabs | Conversational chat bubble |
| **Input** | Structured controls (preset selection, sliders) | Free-text natural language |
| **AI backend** | Grok → theme JSON | Haiku → full page component JSON |
| **Output** | Theme config applied to existing page structure | Complete page layout with all sections |
| **Streaming** | No (batch response) | Yes (SSE streaming, tokens render in real-time) |

**Component structure:**

```
SellerChatbot.tsx
├── ChatBubble          — floating trigger button (bottom-right, doesn't collide with FloatingBuilder)
├── ChatWindow          — expandable chat panel
│   ├── MessageList     — scrollable conversation history
│   ├── InputBar        — text input + send button
│   └── PreviewPane     — live rendered preview of generated page (iframe or React portal)
├── ApplyButton         — "Apply to my storefront" → saves to field_page_builds
└── PostToXButton       — opens X compose intent with generated thread text
```

### 3B: `/api/ai/generate` — Next.js API Route

**Check if this exists.** If it does, extend it. If not, create it.

```
/api/ai/generate
  POST body: {
    task_type: 'chatbot_page_builder' | 'x_post_generator',
    messages: [{ role: 'user', content: '...' }],
    creator_id: string,         // Drupal node ID
    stream: boolean             // true for chatbot, false for batch
  }
```

- Proxies to Drupal's `ai_provider.manager` endpoint
- Handles SSE streaming passthrough for chatbot
- Attaches creator context (X profile data, current theme, existing products) to the request so Haiku has full context
- Auth: NextAuth session required — no anonymous access

### 3C: Preview Pane

- Renders generated JSON output in real-time as it streams in
- Approach: React portal rendering the generated component tree using the same renderer that `FloatingBuilder` preview uses — share this code, don't duplicate it
- Falls back gracefully if JSON is incomplete mid-stream (render what's valid so far)

### 3D: "Apply to My Storefront" Action

- Takes the final generated JSON → writes to `field_page_builds` on the creator's Drupal node via JSON:API PATCH
- Same storage mechanism as `FloatingBuilder` saved builds — this is just a new entry point into the same system
- Adds entry to `best_creations.json` if creator rates it ≥ 4 stars

---

## Phase 4: Usage Metering & Monetization

### 4A: Token Logging

**New Drupal entity: `ai_usage_log`**

| Field | Type | Purpose |
|-------|------|---------|
| `creator_id` | Entity reference | Which creator |
| `task_type` | String | `chatbot_page_builder`, `x_post_generator` |
| `provider` | String | `claude_haiku`, `grok` |
| `input_tokens` | Integer | From API response |
| `output_tokens` | Integer | From API response |
| `estimated_cost` | Decimal | Calculated at log time |
| `timestamp` | Datetime | When |
| `generation_id` | UUID | Links to the saved build if applied |

**Cost tracking formula (Haiku):**

```
input:  $0.80 / 1M tokens
output: $4.00 / 1M tokens
```

A full page generation (~500 input + ~2000 output tokens) ≈ **$0.0084** — well under the $0.01 target.

### 4B: Rate Limiting

**Middleware in Next.js API route (`/api/ai/generate`):**

- Check `ai_usage_log` count for `creator_id` in the last 24 hours
- **Free tier:** 3 `chatbot_page_builder` generations per day
- **Premium tier:** Unlimited
- Return `429` with clear message: "You've used your 3 free generations today. Upgrade for unlimited."
- `x_post_generator` calls are free and don't count toward the limit (low cost, high value for viral distribution)

### 4C: Premium Billing

- New Stripe product/price for the chatbot premium upsell
- Toggle in creator dashboard settings
- Options to model later: flat monthly add-on vs. per-generation credits
- **For launch:** Keep it simple — flat $X/month add-on via Stripe, same `add_invoice_items` pattern as existing billing

---

## Phase 5: Test & Ship

### Test Matrix

| Test Case | Expected Result |
|-----------|----------------|
| Prompt: "Build me a dark emo product page for my new streetwear drops" | Full page JSON with emo preset palette, product carousel above fold, X share button, Stripe hooks |
| Prompt: "Make it more Y2K with sparkles" | Iterative edit — swaps to Y2K/McBling palette, adds decoration rules |
| Haiku cost per full generation | < $0.01 (target ~$0.008) |
| Haiku timeout / error | Grok fallback fires, generation completes, log records provider switch |
| "Apply to my storefront" | JSON saved to `field_page_builds`, storefront renders new page immediately |
| "Post to X" button | Opens X compose intent with pre-filled thread text + storefront URL |
| Free tier limit hit (4th generation) | 429 response, upgrade prompt displayed |
| Mobile viewport (375px) | Generated page fully responsive, products visible above fold |
| `prefers-reduced-motion` | All animations disabled, page still functional |
| Streaming mid-generation | Preview pane renders partial output correctly, no crashes on incomplete JSON |

### Deploy Sequence

```bash
# 1. Drupal: Deploy new ai_provider module
composer update
drush cr
drush cim -y   # Import config for system prompts + AI provider settings

# 2. Next.js: Deploy chatbot component + API route
git add .
git commit -m "feat: AI Chatbot with Haiku — creators build product pages in seconds"
git push origin main
# Vercel auto-deploys from main

# 3. Verify in production
# - Test chatbot flow end-to-end with a real X creator account
# - Confirm Haiku billing in Anthropic dashboard
# - Confirm token logs populating in Drupal
# - Confirm Grok fallback by temporarily invalidating Anthropic key
```

---

## Architecture Summary

```
Creator types prompt
        │
        ▼
  SellerChatbot.tsx (Next.js)
        │
        ▼
  /api/ai/generate (Next.js API route)
        │
        ├─ Auth check (NextAuth)
        ├─ Rate limit check (ai_usage_log)
        │
        ▼
  Drupal ai_provider.manager
        │
        ├─ Primary: ClaudeProvider (Haiku)
        │     └─ System prompt + creator context → JSON page components
        │
        └─ Fallback: GrokProvider
              └─ Same contract, same output format
        │
        ▼
  SSE stream back to SellerChatbot.tsx
        │
        ├─ PreviewPane renders live
        ├─ "Apply to storefront" → PATCH field_page_builds
        └─ "Post to X" → X compose intent URL
```

---

## Open Questions for Next Session

1. **Chatbot positioning relative to FloatingBuilder** — do they coexist in the dashboard, or does chatbot eventually replace the builder? If coexist, confirm non-colliding placement (chatbot bottom-right, builder top-right?).

2. **X write API** — current OAuth scope is read-only for imports. To pre-fill compose intents we don't need write scope (intent URLs are client-side). Confirm this is the desired approach vs. actual API posting (which would need OAuth2 user context with `tweet.write` scope).

3. **Premium pricing** — what's the monthly add-on price for unlimited chatbot generations? Needs to be set before Stripe product creation.

4. **Conversation history** — should the chatbot persist conversation history across sessions (stored in Drupal) or reset each time? Persistent history enables "make it more like last time" but adds storage and complexity.

5. **Shared renderer** — confirm that `FloatingBuilder`'s preview renderer can be extracted into a shared component that both `FloatingBuilder` and `SellerChatbot` import. Avoids maintaining two preview implementations.
