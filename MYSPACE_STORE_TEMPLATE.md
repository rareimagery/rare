# MySpace-Style Creator Store Template
### RareImagery X Marketplace — Storefront UI

---

## Overview

Every creator store at `[creator].rareimagery.net` renders with a fully customizable MySpace-era design. Tiled backgrounds, glitter text, cursor sparkle trails, a music player, blinking badges, animated rainbow text, a comments section, and a Top 8 products grid — all powered by Next.js and data from Drupal.

Each creator controls their entire theme from the console. Zero code required.

---

## File Structure

```
app/
├── stores/
│   └── [creator]/
│       ├── page.tsx              ← Server component (fetches Drupal data)
│       └── MyspaceStore.tsx      ← Client component (the full template)
└── api/
    └── stores/
        └── theme/
            └── route.ts          ← PATCH endpoint for saving theme changes
```

---

## Visual Elements

| Element | Implementation | Customizable |
|---------|---------------|-------------|
| Tiled background | CSS `background-image` repeat | Emoji pattern or custom URL |
| Glitter text | Per-character color cycling + CSS keyframes | Colors via theme |
| Cursor sparkle trail | DOM injection on `mousemove` | On/off |
| Marquee ticker | CSS `translateX` animation | Message text |
| Blinking badges | CSS `step-start` animation | Auto on product tags |
| Rainbow follower count | CSS `color` keyframe loop | — |
| Floating PFP | CSS `translateY` bounce | — |
| Scanline overlay | Fixed `background-image` gradient | — |
| Music player | HTML5 `<audio>` with custom skin | Song URL, title, artist |
| Pulsing borders | CSS `border-color` keyframe | Border color |
| Visitor counter | From Drupal field + random offset | Seed number |

---

## Theme Config Object

Stored in Drupal as `field_store_theme` (JSON field on Commerce Store). The Next.js template reads this and applies it at render time.

```typescript
interface StoreTheme {
  // Background
  bgColor: string            // CSS color e.g. '#000033'
  bgTile: 'stars' | 'hearts' | 'skulls' | 'custom'
  bgTileCustomUrl: string    // URL to tiling image (custom only)

  // Colors
  accentColor: string        // Main accent — glitter, borders, buttons
  secondColor: string        // Secondary accent — highlights, hover
  textColor: string          // Body text
  tableBorderColor: string   // Panel borders
  tableBgColor: string       // Panel backgrounds

  // Typography
  font: 'comic' | 'impact' | 'cursive' | 'times'

  // Effects
  glitterText: boolean       // Glitter animation on store name
  cursorTrail: boolean       // Sparkle cursor trail

  // Content
  marqueeText: string        // Scrolling ticker message
  profileMood: string        // "Mood: 🎵 Feeling creative"
  onlineNow: boolean         // Show green ONLINE badge
  visitorCount: number       // Seed number for visitor counter

  // Music
  songUrl: string            // mp3/ogg URL
  songTitle: string
  songArtist: string
}
```

---

## Step 1: Add Theme Field to Drupal

In Drupal admin: **Commerce → Configuration → Store types → Manage fields**

| Field | Machine Name | Type |
|-------|-------------|------|
| Store Theme | `field_store_theme` | Long text (plain, max 10000) |

Store owners paste JSON here via the console theme editor (see Step 4).

---

## Step 2: Server Component — Fetch Data

### `app/stores/[creator]/page.tsx`

```typescript
import MyspaceStore from './MyspaceStore'

async function getStoreData(slug: string) {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/commerce_store/online` +
    `?filter[field_store_slug]=${slug}` +
    `&include=field_linked_x_profile`,
    {
      headers: { Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}` },
      next: { revalidate: 60 },
    }
  )
  return res.json()
}

export default async function CreatorStorePage({
  params,
}: {
  params: { creator: string }
}) {
  const data = await getStoreData(params.creator)
  const store = data?.data?.[0]

  if (!store) {
    return (
      <div style={{ background: '#000', color: '#ff00ff', fontFamily: 'Comic Sans MS',
        height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontSize: '2em', textAlign: 'center' }}>
        ✨ 404 ✨<br />
        <span style={{ fontSize: '0.5em' }}>This store doesn&apos;t exist yet!!</span>
      </div>
    )
  }

  const xProfile = data?.included?.[0]?.attributes

  // Parse theme JSON safely
  let themeConfig = {}
  try {
    themeConfig = JSON.parse(store.attributes.field_store_theme || '{}')
  } catch {}

  // Build store data shape for the template
  const storeData = {
    name: store.attributes.name,
    xUsername: xProfile?.field_x_username || '',
    bio: xProfile?.field_bio || '',
    pfpUrl: xProfile?.field_pfp_url || '',
    bannerUrl: xProfile?.field_banner_url || '',
    followerCount: xProfile?.field_follower_count || 0,
    topFollowers: xProfile?.field_top_8_followers || [],
    products: store.relationships?.products?.data || [],  // populate from Commerce products
    comments: [],  // populate from Drupal comments/webform
  }

  return <MyspaceStore storeData={storeData} themeConfig={themeConfig} />
}
```

---

## Step 3: Customize Theme via Console

### `app/console/stores/[id]/theme/page.tsx`

The theme editor in the console lets store owners customize their look without touching code.

```typescript
'use client'
import { useState } from 'react'

const PRESET_THEMES = {
  'Y2K Pink': { bgColor: '#1a0010', accentColor: '#ff69b4', secondColor: '#ff00ff',
    tableBorderColor: '#ff1493', tableBgColor: '#2d0020', font: 'comic', bgTile: 'hearts' },
  'Dark Emo': { bgColor: '#0d0d0d', accentColor: '#8b0000', secondColor: '#4b0082',
    tableBorderColor: '#8b0000', tableBgColor: '#1a0000', font: 'impact', bgTile: 'skulls' },
  'Neon Cyber': { bgColor: '#000022', accentColor: '#00ffff', secondColor: '#ff00ff',
    tableBorderColor: '#00ffff', tableBgColor: '#000044', font: 'impact', bgTile: 'stars' },
  'Scene Gold': { bgColor: '#1a1000', accentColor: '#ffd700', secondColor: '#ff6600',
    tableBorderColor: '#ffd700', tableBgColor: '#2a1a00', font: 'comic', bgTile: 'stars' },
}

export default function ThemeEditorPage({ params }: { params: { id: string } }) {
  const [theme, setTheme] = useState({
    bgColor: '#000033', accentColor: '#ff00ff', secondColor: '#00ffff',
    textColor: '#ffffff', tableBorderColor: '#ff00ff', tableBgColor: '#000066',
    font: 'comic', bgTile: 'stars', bgTileCustomUrl: '',
    glitterText: true, cursorTrail: true,
    marqueeText: '✨ Welcome to my store! ✨',
    profileMood: '🎵 Feeling creative',
    onlineNow: true, visitorCount: 1000,
    songUrl: '', songTitle: 'My Song', songArtist: 'Unknown',
  })
  const [saved, setSaved] = useState(false)

  const applyPreset = (presetName: string) => {
    setTheme(t => ({ ...t, ...PRESET_THEMES[presetName] }))
  }

  const save = async () => {
    await fetch(`/api/stores/theme`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ storeId: params.id, theme }),
    })
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  return (
    <div style={{ fontFamily: 'monospace', padding: 20 }}>
      <h1>🎨 Theme Editor</h1>

      <h2>Quick Presets</h2>
      <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
        {Object.keys(PRESET_THEMES).map(name => (
          <button key={name} onClick={() => applyPreset(name)}
            style={{ padding: '6px 12px', cursor: 'pointer' }}>
            {name}
          </button>
        ))}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
        {/* Colors */}
        <div>
          <h3>Colors</h3>
          {[
            ['Background Color', 'bgColor'],
            ['Accent Color', 'accentColor'],
            ['Second Color', 'secondColor'],
            ['Text Color', 'textColor'],
            ['Panel Border', 'tableBorderColor'],
            ['Panel Background', 'tableBgColor'],
          ].map(([label, key]) => (
            <div key={key} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6 }}>
              <label style={{ width: 140 }}>{label}</label>
              <input type="color" value={theme[key]}
                onChange={e => setTheme(t => ({ ...t, [key]: e.target.value }))} />
              <code>{theme[key]}</code>
            </div>
          ))}
        </div>

        {/* Options */}
        <div>
          <h3>Options</h3>
          <div>
            <label>Font: </label>
            <select value={theme.font} onChange={e => setTheme(t => ({ ...t, font: e.target.value }))}>
              <option value="comic">Comic Sans</option>
              <option value="impact">Impact</option>
              <option value="cursive">Brush Script</option>
              <option value="times">Times New Roman</option>
            </select>
          </div>
          <div style={{ marginTop: 8 }}>
            <label>Background Tile: </label>
            <select value={theme.bgTile} onChange={e => setTheme(t => ({ ...t, bgTile: e.target.value }))}>
              <option value="stars">⭐ Stars</option>
              <option value="hearts">💗 Hearts</option>
              <option value="skulls">💀 Skulls</option>
              <option value="custom">Custom URL</option>
            </select>
          </div>
          {theme.bgTile === 'custom' && (
            <input placeholder="Tile image URL" value={theme.bgTileCustomUrl}
              onChange={e => setTheme(t => ({ ...t, bgTileCustomUrl: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          )}
          <div style={{ marginTop: 8 }}>
            <label>
              <input type="checkbox" checked={theme.glitterText}
                onChange={e => setTheme(t => ({ ...t, glitterText: e.target.checked }))} />
              {' '}Glitter Text on Name
            </label>
          </div>
          <div>
            <label>
              <input type="checkbox" checked={theme.cursorTrail}
                onChange={e => setTheme(t => ({ ...t, cursorTrail: e.target.checked }))} />
              {' '}Sparkle Cursor Trail
            </label>
          </div>
          <div style={{ marginTop: 8 }}>
            <label>Marquee Message:</label>
            <input value={theme.marqueeText}
              onChange={e => setTheme(t => ({ ...t, marqueeText: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          </div>
          <div style={{ marginTop: 8 }}>
            <label>Mood:</label>
            <input value={theme.profileMood}
              onChange={e => setTheme(t => ({ ...t, profileMood: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          </div>
        </div>

        {/* Music */}
        <div>
          <h3>🎵 Music Player</h3>
          <div style={{ marginBottom: 4 }}>
            <label>Song URL (.mp3 or .ogg):</label>
            <input placeholder="https://..." value={theme.songUrl}
              onChange={e => setTheme(t => ({ ...t, songUrl: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          </div>
          <div style={{ marginBottom: 4 }}>
            <label>Song Title:</label>
            <input value={theme.songTitle}
              onChange={e => setTheme(t => ({ ...t, songTitle: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          </div>
          <div>
            <label>Artist:</label>
            <input value={theme.songArtist}
              onChange={e => setTheme(t => ({ ...t, songArtist: e.target.value }))}
              style={{ width: '100%', marginTop: 4 }} />
          </div>
        </div>
      </div>

      <button onClick={save} style={{ marginTop: 20, padding: '10px 30px',
        fontSize: '1em', cursor: 'pointer', background: saved ? '#00aa00' : '#ff00ff',
        color: '#fff', border: 'none', fontWeight: 'bold' }}>
        {saved ? '✔ SAVED!' : '💾 Save Theme'}
      </button>
    </div>
  )
}
```

---

## Step 4: Theme Save API Route

### `app/api/stores/theme/route.ts`

```typescript
import { NextRequest, NextResponse } from 'next/server'
import { getToken } from 'next-auth/jwt'

export async function PATCH(req: NextRequest) {
  const token = await getToken({ req })
  if (!token) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { storeId, theme } = await req.json()

  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/commerce_store/online/${storeId}`,
    {
      method: 'PATCH',
      headers: {
        Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}`,
        'Content-Type': 'application/vnd.api+json',
      },
      body: JSON.stringify({
        data: {
          type: 'commerce_store--online',
          id: storeId,
          attributes: {
            field_store_theme: JSON.stringify(theme),
          },
        },
      }),
    }
  )

  if (!res.ok) return NextResponse.json({ error: 'Drupal update failed' }, { status: 500 })

  // Trigger ISR revalidation so the live store reflects changes immediately
  await fetch(`${process.env.NEXTAUTH_URL}/api/revalidate?path=/stores/${storeId}`)

  return NextResponse.json({ success: true })
}
```

---

## Built-in Theme Presets

| Preset | Vibe | Colors |
|--------|------|--------|
| Y2K Pink | Scene / McBling | Hot pink + magenta on near-black |
| Dark Emo | Scene emo | Blood red + deep purple on black |
| Neon Cyber | Futuristic | Cyan + magenta on navy |
| Scene Gold | Warm scene | Gold + orange on dark brown |

Creators can start from a preset and tweak individual values.

---

## CSS Animations Reference

All animations are pure CSS, no library needed.

| Animation | Effect | Used On |
|-----------|--------|---------|
| `glitter` | Scale + rotate + brightness pulse | Glitter text chars, prices |
| `blink` | `step-start` opacity toggle | Badges, blinkies |
| `rainbow` | Color cycles through ROYGBIV | Follower count |
| `marquee` | `translateX` scroll right→left | Ticker bar |
| `float` | Gentle `translateY` bounce | Profile picture |
| `pulse-border` | Border color alternates accent↔second | Store header, cart button |
| `sparkle-fade` | Opacity + scale + translateY | Cursor trail sparks |

---

## Next Steps

- **Grok import button** in `app/console/stores/[id]/page.tsx` — pulls real X PFP, banner, bio, and top 8 followers into the store automatically
- **On-demand ISR** — call `revalidatePath` after every theme save so the live store updates in seconds
- **Product sync** — pull Commerce products from Drupal and render them in the Top 8 grid with real images, prices, and checkout links
- **Comment submission** — wire the comment box to a Drupal Webform or custom entity
