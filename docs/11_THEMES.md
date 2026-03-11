# Step 11: Themes & Page Builder

**Agent:** Next.js (`nextjs.md`)

## 6 Store Themes

Each theme is a React component that renders the entire store page with the creator's data.

| Theme | Component | Lines | Aesthetic |
|-------|-----------|-------|-----------|
| **xai3** | `Xai3Theme.tsx` | 975 | Modern 3-column grid, dark, default |
| **xmimic** | `XMimicTheme.tsx` | 1,342 | X.com single-column feed mimic |
| **myspace** | `MySpaceTheme.tsx` | 1,151 | Y2K nostalgia, glitter, music player |
| **minimal** | `MinimalTheme.tsx` | 509 | Clean, simple, light accents |
| **editorial** | `EditorialTheme.tsx` | 353 | Magazine-style layout |
| **neon** | `NeonTheme.tsx` | 331 | Cyberpunk glow, neon borders |

All in `frontend/src/components/themes/`.

## How Themes Work

### Storage
Theme name stored as `field_store_theme` on `creator_x_profile` nodes in Drupal.

### Rendering
```
/stores/[creator]/page.tsx
    ↓
getCreatorProfile(creator) → gets field_store_theme
    ↓
Switch on theme value:
  "xai3"      → <Xai3Theme profile={...} products={...} />
  "xmimic"    → <XMimicTheme ... />
  "myspace"   → <MySpaceTheme ... />
  "minimal"   → <MinimalTheme ... />
  "editorial" → <EditorialTheme ... />
  "neon"      → <NeonTheme ... />
```

### Theme Props
Every theme receives:
```typescript
interface ThemeProps {
  profile: CreatorProfile;   // X data, bio, followers, etc.
  products: Product[];       // Store products
  topPosts?: any[];          // X posts for feed display
  topFollowers?: any[];      // Notable followers
}
```

## Theme Selector
File: `frontend/src/components/ThemeSelector.tsx` (125 LOC)

- 6 theme cards with previews
- Calls `/api/stores/select-theme` on selection
- Used in store creation wizard (Step 3) and console theme editor

## MySpace Theme Special Features

The MySpace theme supports extra customization:
- **Accent color** — hex color for highlights
- **Glitter color** — hex color for sparkle effects
- **Background image** — tiled or full-bleed URL
- **Music URL** — auto-playing MP3 (with play/pause control)

These are set during store creation and stored as JSON in Drupal.

## Theme Editor
File: `frontend/src/app/console/stores/[id]/theme/page.tsx` (379 LOC)

Full theme customization page in the console:
- Color pickers
- Font selectors
- Background options
- Live preview

---

## Tailwind Page Builder

A floating AI chatbot that generates Tailwind CSS components for store owners.

### Architecture
```
FloatingBuilder.tsx → /api/chat → Claude Haiku 4.5 → generated code
                   → /api/builds → Drupal (save/load builds)
```

### Components

| File | Lines | Purpose |
|------|-------|---------|
| `FloatingBuilder.tsx` | 178 | Draggable panel, 3 tabs |
| `LivePreview.tsx` | 72 | Sandboxed iframe with Babel + Tailwind CDN |
| `BuildLibrary.tsx` | 69 | List/load/delete saved builds |
| `BuilderGate.tsx` | 22 | Only shows builder if user owns the store |

### 3 Tabs
1. **Generate** — Text prompt → Claude Haiku → code output + copy button
2. **Preview** — Sandboxed iframe renders the generated component live
3. **Saved Builds** — Load or delete previously saved builds

### Chat API (`/api/chat/route.ts`, 70 LOC)
- Auth: NextAuth session required
- Model: `claude-haiku-4-5-20251001`
- Rate limit: 10 requests/hour per user
- System prompt: Y2K aesthetic (glitter, neon, blink, marquee)
- Stateless: no conversation memory sent to API

### Builds API (`/api/builds/route.ts`, 85 LOC)
- GET: fetch all builds for user's store
- POST: save new build (max 20 per store)
- DELETE: remove build by ID
- Storage: `field_page_builds` JSON field on commerce_store entity

### Cost Profile
| Usage | API Cost | Build Storage |
|-------|----------|--------------|
| 500 req/month | ~$1.50 | $0 (text in Drupal) |
| Reused builds | $0.00 | $0 |

### Where It Shows
`FloatingBuilder` mounts on store pages when `isOwner === true`:
```tsx
{isOwner && <FloatingBuilder />}
```

Store owners see a purple "Page Builder" button in the bottom-right corner of their storefront.

## Next Step

→ [Step 12: Deployment & Operations](12_DEPLOYMENT.md)
