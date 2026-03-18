# Builder Redesign — Graphical, Content-Rich `/console/builder`

**Goal:** Replace the text-heavy, code-centric builder with a visual, image-first experience that displays actual user content — their X profile picture, banner, products, posts, and theme — so the creator sees their real storefront taking shape, not raw code.

---

## What Changes

| Current Builder | New Graphical Builder |
|---|---|
| Left: prompt input + raw code output | Left: visual design studio with creator's real content |
| Right: small iframe preview | Right: full live storefront preview with their pfp/banner |
| Bottom: text list of saved builds | Bottom: visual card gallery of saved builds with thumbnails |
| No creator identity visible | Creator's X pfp, banner, bio, products front and center |
| Feels like an IDE | Feels like Canva/Squarespace for X creators |

---

## Data Available from X OAuth Session

When a creator signs in through X, the session already contains:

```typescript
session: {
  xUsername: string        // @handle
  xId: string             // numeric ID
  xImage: string          // profile picture URL (pfp)
  xBannerUrl: string      // header/background banner URL (1500×500)
  xAccessToken: string    // OAuth token
  role: "creator" | "admin"
}
```

Plus from Drupal `creator_x_profile`:
- `field_profile_picture` — uploaded pfp image
- `field_background_banner` — uploaded banner image
- `field_bio_description` — bio text
- `field_follower_count` — follower count
- `field_top_posts` — JSON array of top posts (text, likes, reposts, media)
- `field_top_followers` — JSON array of top followers (usernames, avatars)
- `field_linked_store` — their commerce store entity
- `field_page_builds` — saved builds JSON
- `field_store_theme` — current theme config JSON (colors, fonts, effects)

---

## New Layout: Three-Zone Visual Builder

```
┌──────────────────────────────────────────────────────────────────────┐
│  HEADER BAR                                                         │
│  ┌─────┐  @username's Site Builder          [Theme: Neon ▾] [Publish]│
│  │ pfp │  rareimagery.net/stores/username                           │
│  └─────┘                                                            │
├────────────────────────────┬─────────────────────────────────────────┤
│  DESIGN STUDIO (Left 40%) │  LIVE PREVIEW (Right 60%)               │
│                            │                                         │
│  ┌────────────────────┐   │  ┌─────────────────────────────────┐    │
│  │ Your Content       │   │  │                                 │    │
│  │ ┌──┐ ┌──┐ ┌──┐    │   │  │  [Full storefront preview       │    │
│  │ │📷│ │📷│ │📷│    │   │  │   rendered with creator's       │    │
│  │ └──┘ └──┘ └──┘    │   │  │   actual pfp, banner,           │    │
│  │ (product images)   │   │  │   products, bio, theme]         │    │
│  │                    │   │  │                                 │    │
│  │ ┌──────────────┐   │   │  │                                 │    │
│  │ │ 🎨 AI Prompt │   │   │  │                                 │    │
│  │ │              │   │   │  │                                 │    │
│  │ │ "Make my     │   │   │  │                                 │    │
│  │ │  hero section│   │   │  │                                 │    │
│  │ │  cyberpunk"  │   │   │  │                                 │    │
│  │ └──────────────┘   │   │  │                                 │    │
│  │                    │   │  │                                 │    │
│  │ [Subculture Picks] │   │  │                                 │    │
│  │ [Y2K][Neon][Emo]   │   │  │                                 │    │
│  │ [Editorial][Minimal]│  │  │                                 │    │
│  └────────────────────┘   │  └─────────────────────────────────┘    │
│                            │                                         │
├────────────────────────────┴─────────────────────────────────────────┤
│  SAVED BUILDS (Bottom)                                               │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐                  │
│  │thumb │  │thumb │  │thumb │  │thumb │  │ + New│                   │
│  │"Hero"│  │"About"│ │"Shop"│  │"Feed"│  │Build │                   │
│  └──────┘  └──────┘  └──────┘  └──────┘  └──────┘                  │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Zone 1: Header Bar — Creator Identity Strip

**Purpose:** Immediately grounds the builder in the creator's identity. They see their face and brand, not a generic tool.

```tsx
// Header bar component
<div className="flex items-center gap-4 border-b border-zinc-800 bg-zinc-950 px-6 py-3">
  {/* Creator pfp — from session.xImage or Drupal field_profile_picture */}
  <img
    src={session.xImage || profile.field_profile_picture || "/default-pfp.jpg"}
    alt={session.xUsername}
    className="h-10 w-10 rounded-full border-2 border-indigo-500"
  />

  <div className="flex-1">
    <h1 className="text-lg font-bold text-white">
      @{session.xUsername}'s Site Builder
    </h1>
    <p className="text-xs text-zinc-500">
      {session.xUsername}.rareimagery.net
    </p>
  </div>

  {/* Active theme selector */}
  <ThemeSelector
    currentTheme={profile.field_store_theme}
    onChange={handleThemeChange}
  />

  {/* Publish / Go Live button */}
  <button className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
    Publish Site
  </button>
</div>
```

---

## Zone 2: Design Studio (Left Panel — 40%)

**Purpose:** Visual content palette + AI prompt. The creator sees their actual images, products, and posts — and instructs the AI using those real assets.

### 2A: Content Palette — "Your Content"

Displays the creator's real content as draggable/selectable visual tiles:

```tsx
<div className="space-y-4 p-4">
  {/* Banner preview */}
  <div className="relative overflow-hidden rounded-lg border border-zinc-800">
    <img
      src={profile.field_background_banner || session.xBannerUrl}
      alt="Your X banner"
      className="h-24 w-full object-cover"
    />
    <span className="absolute bottom-1 left-2 text-xs text-white/70 bg-black/50 rounded px-1">
      Your banner
    </span>
  </div>

  {/* Products grid — actual product images from Drupal Commerce */}
  <div>
    <h3 className="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">
      Your Products ({products.length})
    </h3>
    <div className="grid grid-cols-3 gap-2">
      {products.map(product => (
        <div
          key={product.id}
          className="group relative aspect-square overflow-hidden rounded-lg border border-zinc-800 cursor-pointer hover:border-indigo-500 transition-colors"
        >
          <img
            src={product.field_media?.uri || "/placeholder-product.jpg"}
            alt={product.title}
            className="h-full w-full object-cover"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
            <span className="absolute bottom-1 left-1 text-xs text-white truncate w-full pr-2">
              {product.title}
            </span>
          </div>
        </div>
      ))}
    </div>
  </div>

  {/* Top posts — visual feed cards */}
  <div>
    <h3 className="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">
      Top Posts
    </h3>
    <div className="space-y-2 max-h-32 overflow-y-auto">
      {topPosts.slice(0, 3).map(post => (
        <div key={post.id} className="rounded-lg border border-zinc-800 bg-zinc-900/40 p-2 text-xs text-zinc-400">
          <p className="line-clamp-2">{post.text}</p>
          <div className="mt-1 flex gap-3 text-zinc-600">
            <span>{post.likes} likes</span>
            <span>{post.reposts} reposts</span>
          </div>
        </div>
      ))}
    </div>
  </div>
</div>
```

### 2B: AI Prompt Area

Below the content palette — the creator types what they want. The prompt is context-aware: the AI already knows their pfp, banner, products, bio, and theme.

```tsx
<div className="border-t border-zinc-800 p-4">
  {/* Subculture quick-picks */}
  <div className="mb-3 flex flex-wrap gap-2">
    {["Y2K", "Neon", "Emo", "Cottagecore", "Hip-Hop", "Editorial", "Minimal"].map(style => (
      <button
        key={style}
        onClick={() => setPrompt(`Build me a ${style} storefront`)}
        className="rounded-full border border-zinc-700 bg-zinc-800 px-3 py-1 text-xs text-zinc-300 hover:border-indigo-500 hover:text-indigo-300 transition-colors"
      >
        {style}
      </button>
    ))}
  </div>

  {/* Prompt input */}
  <div className="flex gap-2">
    <textarea
      value={prompt}
      onChange={e => setPrompt(e.target.value)}
      placeholder="Describe what you want — e.g. 'Make my hero section dark with neon accents and feature my top 3 products'"
      className="flex-1 resize-none rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-500 focus:border-indigo-500 focus:outline-none"
      rows={3}
    />
    <button
      onClick={handleGenerate}
      disabled={isStreaming}
      className="self-end rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
    >
      {isStreaming ? "Generating..." : "Generate"}
    </button>
  </div>

  {/* Streaming status */}
  {isStreaming && (
    <div className="mt-2 flex items-center gap-2 text-xs text-indigo-400">
      <div className="h-2 w-2 animate-pulse rounded-full bg-indigo-400" />
      Building your page with AI...
    </div>
  )}
</div>
```

### 2C: Quick Examples Row (Image Prompts)

Visual example cards showing what the AI can build — clickable to auto-fill the prompt:

```tsx
<div className="border-t border-zinc-800 p-4">
  <h3 className="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">
    Examples
  </h3>
  <div className="grid grid-cols-2 gap-2">
    {examples.map(ex => (
      <button
        key={ex.label}
        onClick={() => setPrompt(ex.prompt)}
        className="group relative overflow-hidden rounded-lg border border-zinc-800 hover:border-indigo-500 transition-colors"
      >
        <img src={ex.thumbnail} alt={ex.label} className="h-20 w-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-2">
          <span className="text-xs font-medium text-white">{ex.label}</span>
        </div>
      </button>
    ))}
  </div>
</div>
```

---

## Zone 3: Live Preview (Right Panel — 60%)

**Purpose:** Full storefront preview rendered with the creator's actual data — their pfp as the store avatar, their banner as the hero background, their real product images in the grid.

```tsx
<div className="relative flex-1 bg-zinc-950">
  {/* Device frame toggle */}
  <div className="flex items-center gap-2 border-b border-zinc-800 px-4 py-2">
    <button
      onClick={() => setDevice("desktop")}
      className={`rounded px-2 py-1 text-xs ${device === "desktop" ? "bg-zinc-800 text-white" : "text-zinc-500"}`}
    >
      Desktop
    </button>
    <button
      onClick={() => setDevice("mobile")}
      className={`rounded px-2 py-1 text-xs ${device === "mobile" ? "bg-zinc-800 text-white" : "text-zinc-500"}`}
    >
      Mobile
    </button>

    <div className="flex-1" />

    {/* Live URL */}
    <span className="text-xs text-zinc-600">
      {session.xUsername}.rareimagery.net
    </span>
  </div>

  {/* Preview iframe — uses actual creator data */}
  <div className={`mx-auto ${device === "mobile" ? "max-w-[375px]" : "w-full"} h-full`}>
    <LivePreview
      code={currentCode}
      creatorData={{
        pfp: profile.field_profile_picture || session.xImage,
        banner: profile.field_background_banner || session.xBannerUrl,
        username: session.xUsername,
        bio: profile.field_bio_description,
        followers: profile.field_follower_count,
        products: products,
        topPosts: topPosts,
        theme: profile.field_store_theme,
      }}
    />
  </div>
</div>
```

**Key difference from current builder:** The preview iframe receives the creator's actual data object. The AI-generated component renders with real images, real product names, real prices — not placeholder content. The creator sees exactly what their visitors will see.

---

## Zone 4: Saved Builds Gallery (Bottom Strip)

**Purpose:** Visual thumbnails of saved builds instead of a text list. Each card shows a screenshot/thumbnail of the generated page.

```tsx
<div className="border-t border-zinc-800 bg-zinc-950 px-6 py-4">
  <div className="mb-2 flex items-center justify-between">
    <h3 className="text-sm font-semibold text-zinc-300">Saved Builds</h3>
    <span className="text-xs text-zinc-600">{builds.length} builds</span>
  </div>

  <div className="flex gap-3 overflow-x-auto pb-2">
    {builds.map(build => (
      <button
        key={build.id}
        onClick={() => loadBuild(build)}
        className={`group flex-shrink-0 rounded-lg border transition-colors ${
          activeBuild?.id === build.id
            ? "border-indigo-500 ring-1 ring-indigo-500/30"
            : "border-zinc-800 hover:border-zinc-600"
        }`}
      >
        {/* Build thumbnail — rendered via html2canvas or stored screenshot */}
        <div className="h-20 w-32 overflow-hidden rounded-t-lg bg-zinc-900">
          {build.thumbnail ? (
            <img src={build.thumbnail} alt={build.label} className="h-full w-full object-cover object-top" />
          ) : (
            <div className="flex h-full items-center justify-center text-zinc-700">
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
          )}
        </div>

        {/* Build label + actions */}
        <div className="flex items-center justify-between px-2 py-1.5">
          <span className="text-xs text-zinc-400 truncate max-w-[80px]">{build.label}</span>
          <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button onClick={e => { e.stopPropagation(); exportBuild(build); }} title="Download .tsx">
              <svg className="h-3.5 w-3.5 text-zinc-500 hover:text-white" /* download icon */ />
            </button>
            <button onClick={e => { e.stopPropagation(); deleteBuild(build.id); }} title="Delete">
              <svg className="h-3.5 w-3.5 text-zinc-500 hover:text-red-400" /* trash icon */ />
            </button>
          </div>
        </div>
      </button>
    ))}

    {/* New build card */}
    <button
      onClick={() => { setCurrentCode(null); setActiveBuild(null); }}
      className="flex h-[104px] w-32 flex-shrink-0 flex-col items-center justify-center rounded-lg border border-dashed border-zinc-700 text-zinc-600 hover:border-indigo-500 hover:text-indigo-400 transition-colors"
    >
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v16m8-8H4" />
      </svg>
      <span className="mt-1 text-xs">New Build</span>
    </button>
  </div>
</div>
```

---

## File: `src/app/console/builder/page.tsx`

The full console page component:

```tsx
"use client";

import { useSession } from "next-auth/react";
import { useEffect, useState } from "react";
import { LivePreview } from "@/components/builder/LivePreview";

interface CreatorProfile {
  pfp: string;
  banner: string;
  username: string;
  bio: string;
  followers: number;
  products: Product[];
  topPosts: TopPost[];
  theme: StoreTheme;
  builds: Build[];
}

export default function BuilderPage() {
  const { data: session } = useSession();
  const [profile, setProfile] = useState<CreatorProfile | null>(null);
  const [prompt, setPrompt] = useState("");
  const [currentCode, setCurrentCode] = useState<string | null>(null);
  const [isStreaming, setIsStreaming] = useState(false);
  const [device, setDevice] = useState<"desktop" | "mobile">("desktop");
  const [activeBuild, setActiveBuild] = useState<Build | null>(null);
  const [builds, setBuilds] = useState<Build[]>([]);

  // Fetch creator profile + products + builds on mount
  useEffect(() => {
    if (!session?.xUsername) return;
    fetchCreatorData(session.xUsername).then(data => {
      setProfile(data);
      setBuilds(data.builds || []);
    });
  }, [session?.xUsername]);

  const handleGenerate = async () => {
    if (!prompt.trim() || !profile) return;
    setIsStreaming(true);
    setCurrentCode("");

    const res = await fetch("/api/chat", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        message: prompt,
        theme: profile.theme?.preset || "xai3",
        creatorContext: {
          username: profile.username,
          bio: profile.bio,
          products: profile.products,
          followerCount: profile.followers,
          topPosts: profile.topPosts,
          pfpUrl: profile.pfp,
          bannerUrl: profile.banner,
        },
      }),
    });

    const reader = res.body!.getReader();
    const decoder = new TextDecoder();
    let code = "";

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      code += decoder.decode(value, { stream: true });
      setCurrentCode(code);
    }

    setIsStreaming(false);
  };

  const handleSave = async () => {
    if (!currentCode) return;
    const label = prompt.slice(0, 50) || "Untitled build";
    const res = await fetch("/api/builds", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ label, code: currentCode, published: false }),
    });
    const newBuild = await res.json();
    setBuilds(prev => [...prev, newBuild]);
    setActiveBuild(newBuild);
  };

  if (!session) return null;
  if (!profile) return <BuilderSkeleton />;

  return (
    <div className="flex h-screen flex-col bg-zinc-950 text-white">
      {/* Zone 1: Header */}
      <BuilderHeader session={session} profile={profile} onSave={handleSave} />

      {/* Zone 2+3: Design Studio + Preview */}
      <div className="flex flex-1 overflow-hidden">
        {/* Left: Design Studio */}
        <div className="w-[40%] overflow-y-auto border-r border-zinc-800">
          <ContentPalette profile={profile} />
          <PromptArea
            prompt={prompt}
            setPrompt={setPrompt}
            isStreaming={isStreaming}
            onGenerate={handleGenerate}
          />
          <ExamplesRow onSelect={setPrompt} />
        </div>

        {/* Right: Live Preview */}
        <div className="flex-1 flex flex-col">
          <PreviewToolbar device={device} setDevice={setDevice} username={profile.username} />
          <div className={`flex-1 overflow-auto ${device === "mobile" ? "flex justify-center" : ""}`}>
            <div className={device === "mobile" ? "w-[375px]" : "w-full"}>
              <LivePreview
                code={currentCode}
                creatorData={{
                  pfp: profile.pfp,
                  banner: profile.banner,
                  username: profile.username,
                  bio: profile.bio,
                  followers: profile.followers,
                  products: profile.products,
                  topPosts: profile.topPosts,
                  theme: profile.theme,
                }}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Zone 4: Saved Builds */}
      <SavedBuildsStrip
        builds={builds}
        activeBuild={activeBuild}
        onLoad={(build) => { setCurrentCode(build.code); setActiveBuild(build); }}
        onDelete={(id) => setBuilds(prev => prev.filter(b => b.id !== id))}
        onNew={() => { setCurrentCode(null); setActiveBuild(null); setPrompt(""); }}
      />
    </div>
  );
}
```

---

## ConsoleSidebar Update

Add the Page Builder link with a visual icon:

```tsx
// In ConsoleSidebar.tsx navigation items array, between "Subscriptions" and "Social":
{
  href: "/console/builder",
  label: "Page Builder",
  icon: (
    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
    </svg>
  ),
}
```

---

## How Creator Data Flows into the Preview

```
Creator signs in via X OAuth
        │
        ▼
NextAuth session populated:
  xImage (pfp URL), xBannerUrl, xUsername
        │
        ▼
Builder page mounts → fetches from Drupal:
  GET /jsonapi/node/creator_x_profile?filter[field_x_username]=@handle
  GET /jsonapi/commerce_product?filter[stores]=storeId
        │
        ▼
Profile data hydrated:
  pfp, banner, bio, followers, products[], topPosts[], theme{}
        │
        ▼
ContentPalette renders real images (banner, product grid, post cards)
        │
        ▼
Creator types prompt → POST /api/chat with creatorContext
        │
        ▼
AI generates component using real data references:
  "Use the creator's banner as hero background"
  "Show products with real images and prices"
  "Include profile picture as store avatar"
        │
        ▼
LivePreview receives code + creatorData props
  → Renders generated component with real images injected
  → Creator sees their actual storefront, not placeholder content
```

---

## Key Design Decisions

1. **Image-first, not code-first** — The creator never sees raw JSX unless they choose to. The builder shows their photos, products, and posts visually.

2. **Real data in preview** — The preview iframe receives the creator's actual product images, pfp, banner, and bio. The AI-generated component uses these as props, so what the creator sees is what their visitors see.

3. **Thumbnails for saved builds** — Each saved build stores a screenshot (captured via `html2canvas` on save). The builds gallery shows visual cards, not text labels.

4. **Mobile preview toggle** — Desktop/mobile toggle wraps the preview in a 375px container so creators can verify their mobile experience.

5. **Subculture quick-picks** — One tap to start with a vibe (Y2K, Neon, Emo, Cottagecore, etc.), then refine via chat.

6. **Creator identity header** — The pfp + handle + live URL in the header bar keeps the creator grounded in their brand throughout the building process.

7. **Content palette as reference** — The left panel shows the creator's banner, products, and posts as a visual inventory. This helps them reference their own content while prompting ("use my 3rd product as the hero image").

---

## API Changes Needed

### `/api/chat/route.ts` — Extend with visual context

Add `pfpUrl` and `bannerUrl` to the `creatorContext` payload. Update the system prompt:

```
You are building a storefront for @{username}.
Their profile picture: {pfpUrl}
Their banner image: {bannerUrl}
Their bio: {bio}
Their products: {products as JSON with image URLs}

When generating components:
- Use the creator's actual image URLs, not placeholders
- Reference their banner as the hero/header background
- Show their pfp as the store avatar
- Display real product names and prices
```

### `/api/builds/route.ts` — Add thumbnail storage

On POST (save), accept an optional `thumbnail` field (base64 data URL or uploaded image URL). Store alongside the build in `field_page_builds` JSON:

```json
{
  "id": "uuid",
  "label": "Cyberpunk Hero",
  "code": "export default function...",
  "thumbnail": "data:image/png;base64,...",
  "createdAt": "2026-03-18T..."
}
```

---

## Verification

1. Sign in via X → builder header shows your real pfp and @handle
2. Content palette shows your actual X banner, product images, and top posts
3. Type a prompt → preview renders with your real images, not placeholders
4. Save a build → thumbnail appears in the bottom gallery
5. Click a saved build → preview loads it with your current data
6. Toggle mobile/desktop → preview resizes correctly
7. Click a subculture chip → prompt auto-fills with that style
8. Publish → site goes live at `username.rareimagery.net`
