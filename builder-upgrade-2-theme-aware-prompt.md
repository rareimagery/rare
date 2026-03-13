# Builder Upgrade 2 — Theme-Aware System Prompt

## Problem

The `/api/chat` system prompt is hardcoded to a Y2K/MySpace aesthetic. RareImagery now has 6 themes — the AI should generate components that match the store's current theme, not always MySpace.

## Themes

| Theme | Visual Language |
|-------|----------------|
| `xai3` (default) | Dark zinc/black, indigo/purple accents, monospace stats, X-feed column layout, gold highlights |
| `minimal` | Clean white/light gray, thin borders, generous whitespace, system font stack, subtle shadows |
| `neon` | Pure black background, neon glow borders (cyan/magenta/green), synthwave gradients, bold sans |
| `editorial` | Serif headings, cream/warm tones, magazine-style grid, editorial photography feel |
| `myspace` | Y2K aesthetic — glitter, blink, marquee, tiled backgrounds, hot pink/cyber teal, pixel borders |
| `xmimic` | X/Twitter clone — timeline feed, 600px column, blue accent, avatar+handle patterns, card borders |

## File

`src/app/api/chat/route.ts`

## Changes

1. Accept an optional `theme` field in the POST body alongside `message`
2. Create a `THEME_PROMPTS` map with theme-specific style instructions
3. Compose the system prompt dynamically: base rules + theme-specific block
4. Default to `xai3` if no theme provided

## API Contract Change

```ts
// Request body:
{ message: string; theme?: string }
```

## FloatingBuilder Change

`src/components/builder/FloatingBuilder.tsx` needs to pass the store's current theme to `/api/chat`. This requires:
- Accept a `theme` prop on FloatingBuilder
- Pass it through BuilderGate from the store page

`src/components/builder/BuilderGate.tsx` needs to:
- Accept a `theme` prop
- Pass it to FloatingBuilder

`src/app/stores/[creator]/page.tsx` needs to:
- Pass `profile.store_theme` to BuilderGate

## System Prompt Structure

```
BASE_PROMPT (shared rules about code output, imports, Tailwind)
+
THEME_PROMPTS[theme] (visual language specific to the store's theme)
```

## Verification

1. Visit a store with `xai3` theme → builder generates dark/indigo components
2. Visit a store with `myspace` theme → builder generates Y2K/glitter components
3. Visit a store with `minimal` theme → builder generates clean/white components
