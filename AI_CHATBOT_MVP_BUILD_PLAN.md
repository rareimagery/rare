# AI Chatbot MVP Build Plan — Ship in 3 Sprints

**Project:** RareImagery (rareimagery.net)
**Goal:** Conversational chatbot in the seller dashboard. Creator types a prompt, gets a live-previewed storefront page, saves it, shares to X. Ship fast by reusing everything that already works.

---

## What Already Exists (No Rebuild Needed)

Here's what's already live and can be reused directly:

| Existing Component | File | Reuse For |
|---|---|---|
| Claude Haiku streaming API | `src/app/api/chat/route.ts` | Chatbot backend — already streams JSX via Haiku with rate limiting (10/hr) |
| Builds CRUD API | `src/app/api/builds/route.ts` | "Apply to storefront" — already saves/loads/deletes builds in `field_page_builds` |
| Drupal builds persistence | `src/lib/drupalBuilds.ts` | Storage layer — already handles JSON read/write to Drupal via cookie auth |
| LivePreview renderer | `src/components/builder/LivePreview.tsx` | Preview pane — already extracted as shared component |
| Grok profile enhancement | `src/lib/grok.ts` | Creator context injection — `enhanceCreatorProfile()` already returns bio, products, theme, sentiment |
| Dual-AI site generation | `src/lib/ai/generate-site.ts` | Orchestration pattern — Grok analyzes → Claude generates, with graceful fallback |
| Theme generation + presets | `src/app/api/stores/generate-theme/route.ts` | Subculture awareness — 10 presets already wired with palettes + typography |
| Drupal auth helpers | `src/lib/drupal.ts` | Auth — `drupalWriteHeaders()` for saves, `drupalAuthHeaders()` for reads |

**Bottom line:** The AI backend, streaming, builds storage, preview rendering, and Grok integration are all live. The MVP is primarily a **new UI component** + **minor API extensions**.

---

## What's Actually New (MVP Scope)

| New Work | Priority | Effort |
|---|---|---|
| `SellerChatbot.tsx` — chat UI component | P0 | Medium |
| Extend `/api/chat` with creator context injection | P0 | Small |
| "Post to X" compose intent button | P1 | Tiny |
| Conversation history (session-only, in-memory) | P1 | Small |
| Token usage display in chat | P2 | Small |

---

## Cost Reality Check

| Item | Cost | Notes |
|------|------|-------|
| Haiku per storefront generation | ~$0.008 | ~500 input + ~2000 output tokens |
| 100 creators × 3 generations each | ~$2.40 | Negligible |
| 1000 creators × 5 generations each | ~$40.00 | Still negligible |
| Anthropic API key | Free to provision | Pay-as-you-go |

**Bottom line:** You can run this for months before AI costs matter.

---

## Sprint 1: Chat UI + Shared Preview (Core Loop)

### 1A: `SellerChatbot.tsx` — Chat Component

**LivePreview** is already extracted at `src/components/builder/LivePreview.tsx` — the chatbot imports it directly.

**File:** `src/components/chatbot/SellerChatbot.tsx`

```
src/components/chatbot/
├── SellerChatbot.tsx      ← Main component (chat window + toggle)
├── ChatMessage.tsx        ← Single message bubble (user or assistant)
├── ChatInput.tsx          ← Text input + send button
└── ChatPreview.tsx        ← Wrapper around shared LivePreview for chat context
```

**Behavior:**
1. Floating chat bubble — bottom-right corner, below FloatingBuilder if both visible
2. Click to expand chat window (400px wide, 600px tall, resizable)
3. User types prompt → streams response from `/api/chat`
4. Assistant messages render as:
   - Text explanation (above)
   - Live preview of generated code (below, using shared `LivePreview`)
5. Conversation stays in React state (session-only, resets on page reload)

**State management:**
```typescript
interface ChatState {
  messages: ChatMessage[];      // conversation history
  isStreaming: boolean;         // currently receiving response
  currentCode: string | null;  // latest generated code (for preview + save)
  isOpen: boolean;              // chat window visible
}
```

**Placement:** Add to console layout only (not public store pages).

```typescript
// src/app/console/layout.tsx (or equivalent)
<FloatingBuilder ... />
<SellerChatbot creatorSlug={creator} storeId={storeId} />
```

### 1B: Wire to Existing `/api/chat` Route

**Current `/api/chat/route.ts` already does:**
- Accepts messages array + theme context
- Streams Claude Haiku responses
- Rate limits (10/hr per user)
- Returns JSX/component code as text stream

**Extend with:**
1. Accept optional `creatorContext` in request body:
   ```typescript
   {
     messages: Message[],
     theme: string,
     creatorContext?: {        // NEW
       username: string,
       bio: string,
       products: Product[],
       followerCount: number,
       topPosts: TopPost[],
     }
   }
   ```
2. Prepend creator context to the system prompt when provided:
   ```
   You are building a storefront for @{username}.
   Their bio: {bio}
   Their products: {products as JSON}
   Their audience: {followerCount} followers
   Their top content: {topPosts summary}

   Generate components that reflect their brand and showcase their products.
   ```
3. No other changes to the existing route — streaming, rate limiting, theme awareness all stay.

**Client-side in `SellerChatbot.tsx`:**
- Fetch creator profile data on mount via `getCreatorProfile()`
- Pass as `creatorContext` with every chat request
- Use `EventSource` or `fetch` with `ReadableStream` for SSE consumption (same pattern as FloatingBuilder)

---

## Sprint 2: Save + Share Actions

### 2A: "Apply to My Storefront" Button

**This is trivial — the save mechanism already exists.**

In `SellerChatbot.tsx`, when `currentCode` is set (generation complete):

```typescript
const handleApply = async () => {
  const res = await fetch('/api/builds', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      storeSlug: creatorSlug,
      label: `Chatbot: ${messages[0]?.content.slice(0, 50)}...`,
      code: currentCode,
      published: true,
    }),
  });
  // Show success toast
};
```

- Uses existing `/api/builds` POST endpoint
- Saves to `field_page_builds` via `drupalBuilds.ts`
- Published by default (creator can toggle in Saved Builds tab of FloatingBuilder)
- Label auto-generated from first message in conversation

### 2B: "Post to X" Button

**No API integration needed — uses X compose intent URL (client-side only).**

```typescript
const handlePostToX = () => {
  const storeUrl = `https://rareimagery.net/stores/${creatorSlug}`;
  const text = `Check out my new storefront! ${storeUrl}`;
  const intentUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`;
  window.open(intentUrl, '_blank', 'width=550,height=420');
};
```

**Enhance later** (post-MVP): Use Claude to generate a custom tweet thread based on the generated page content. For MVP, the pre-filled text is sufficient.

### 2C: Subculture Preset Quick-Select

Add a row of preset buttons above the chat input:

```
[ Emo ] [ Y2K ] [ Cottagecore ] [ Neon ] [ Hip-Hop ] [ + more ]
```

Clicking a preset inserts a starter prompt:
```
"Build me a {preset} storefront page with my products featured above the fold"
```

This leverages the existing theme awareness in `/api/chat` — the system prompt already knows how to apply preset palettes from `MYSPACE_THEME_BOT_RULES.md`.

---

## Sprint 3: Polish + Soft Launch

### 3A: Loading & Error States

- Typing indicator while streaming
- Graceful error message if Claude fails (no raw error dumps)
- "Try again" button on failed generations
- Rate limit hit → show remaining count + reset time

### 3B: Mobile Responsiveness

- Chat bubble works at 375px
- Chat window goes full-screen on mobile (bottom sheet pattern)
- Preview pane stacks below chat on narrow viewports

### 3C: Keyboard Shortcuts

- `Enter` to send (existing chat convention)
- `Shift+Enter` for newline
- `Escape` to close chat window
- `Cmd/Ctrl+S` to save current generation

### 3D: Analytics Events

Track in existing analytics (if any) or console.log for now:
- `chatbot_opened` — how many creators use it
- `chatbot_prompt_sent` — prompt text + theme context
- `chatbot_generation_applied` — conversion rate from generation → save
- `chatbot_post_to_x` — viral loop activation

---

## File Changes Summary

### New Files
```
src/components/chatbot/SellerChatbot.tsx     — Main chat component
src/components/chatbot/ChatMessage.tsx       — Message bubble
src/components/chatbot/ChatInput.tsx         — Input bar
src/components/chatbot/ChatPreview.tsx       — Preview wrapper
```

### Modified Files
```
src/app/api/chat/route.ts                   — Add creatorContext to system prompt
src/app/console/layout.tsx                  — Add <SellerChatbot /> to layout
```

---

## Architecture (MVP)

```
Creator types prompt in SellerChatbot
        │
        ▼
  SellerChatbot.tsx (React state manages conversation)
        │
        ├─ Fetches creator profile on mount (getCreatorProfile)
        │
        ▼
  POST /api/chat (existing route, extended with creatorContext)
        │
        ├─ System prompt: theme rules + creator context + subculture presets
        ├─ Model: claude-haiku (already configured)
        ├─ Rate limit: 10/hr (already enforced)
        │
        ▼
  SSE stream → SellerChatbot
        │
        ├─ ChatMessage renders text explanation
        ├─ LivePreview (shared) renders generated code live
        │
        ▼
  Creator reviews output
        │
        ├─ "Apply to storefront" → POST /api/builds (existing)
        │     └─ Saves to field_page_builds via drupalBuilds.ts
        │
        └─ "Post to X" → twitter.com/intent/tweet (client-side URL)
```

---

## Success Criteria

| Metric | Target |
|--------|--------|
| Prompt → live preview | < 5 seconds to first render |
| Full generation time | < 15 seconds |
| Mobile usable | Works at 375px viewport |
| Save works | Build appears in FloatingBuilder's Saved Builds tab |
| No regressions | FloatingBuilder, theme gen, Grok import all still work |

---

## What's Deferred (Parked, Not Forgotten)

| Feature | Why Deferred | Trigger to Revisit |
|---------|-------------|-------------------|
| Drupal `ai_provider` service layer | Next.js calls Claude directly — no need for Drupal routing | Need centralized provider management |
| Premium chatbot tier | Haiku too cheap to meter at launch | Costs exceed $50/mo or abuse |
| Rate limiting tiers | 10/hr already enforced; no premium/free split needed yet | Abuse or cost spike |
| Token usage logging in Drupal | Anthropic dashboard is sufficient | Before premium tier goes live |
| Persistent conversation history | Stateless is fine at launch | Creators ask for "edit my last generation" |
| Separate SellerChatbot from FloatingBuilder | Separate component gives better UX | If builder feels too crowded |
| AI-generated X thread copy | Creators can copy their link for now | Creator feedback requests it |

---

## Post-MVP Roadmap (After Validation)

1. **AI-generated X thread** — Claude writes custom tweet copy based on generated page
2. **Persistent conversation history** — Store in Drupal, enable "make it like last time"
3. **Usage metering + premium tier** — Token logging, 3 free/day, unlimited paid
4. **Grok fallback** — Auto-switch to Grok if Claude fails (provider abstraction)
5. **Multi-page generation** — Generate full storefront (hero + about + products + footer) in one prompt
6. **Shoutout Walls** — Friends promoting friends as the organic ad unit
7. **Voice input** — Web Speech API for hands-free prompting on mobile

---

## Total New Code Estimate

| Component | Lines | Files |
|-----------|-------|-------|
| `SellerChatbot.tsx` + sub-components | ~400 | 4 TSX |
| `/api/chat` extension (creatorContext) | ~30 | 1 TS (modify) |
| Console layout integration | ~10 | 1 TSX (modify) |

**~440 lines of new code across 4 new files + 2 modifications.** Everything else is already built.
