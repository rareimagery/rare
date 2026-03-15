# Tailwind Page Builder — RareImagery Store Owner Tool (v2)

A cost-controlled, store-owner-only AI chatbot that generates Tailwind CSS components for Next.js storefronts. Floats over the live storefront so owners see changes in context. Saves named builds to Drupal — zero API cost on reuse.

---

## Architecture Overview

```
Store Owner (authenticated, viewing their storefront)
        ↓
FloatingBuilder.tsx — draggable panel, live preview iframe
        ↓
/api/chat/route.ts — auth guard, rate limit, Anthropic proxy
        ↓
claude-haiku-4-5 — stateless, Y2K-aware system prompt
        ↓
/api/builds/route.ts — save/load named builds via Drupal JSON:API
        ↓
Drupal Commerce Store — builds stored as JSON field on store entity
```

**Key principle:** No conversation memory is sent to the API. Every request is a single prompt → response. Saved builds are stored outputs, not conversation history — storage is text, cost is negligible, and reusing a build skips the API entirely.

---

## File Structure

```
/app
  /api
    /chat
      route.ts              ← Anthropic proxy + auth guard + rate limit
    /builds
      route.ts              ← CRUD for saved builds (Drupal JSON:API)
/components
  /builder
    FloatingBuilder.tsx     ← Draggable floating panel + tabbed UI
    BuildLibrary.tsx        ← Saved builds list + load/delete
    LivePreview.tsx         ← Sandboxed iframe preview
/lib
  drupalAuth.ts             ← Validate session/JWT
  drupalBuilds.ts           ← Drupal JSON:API helpers for build storage
```

---

## 1. Drupal — Store Entity Field

Add a `field_page_builds` JSON field to the `commerce_store` entity bundle. Each build is an object in a JSON array:

```json
[
  {
    "id": "uuid-here",
    "label": "Hero banner - neon cyber",
    "code": "export default function Hero() { ... }",
    "createdAt": "2025-01-15T10:30:00Z"
  }
]
```

No custom entity needed — this rides on the existing store entity. Add the field via Drupal UI or a config export.

---

## 2. Builds API Route — `/app/api/builds/route.ts`

```ts
import { NextRequest, NextResponse } from 'next/server'
import { validateDrupalSession } from '@/lib/drupalAuth'
import { getBuilds, saveBuilds } from '@/lib/drupalBuilds'
import { v4 as uuidv4 } from 'uuid'

// GET — fetch all saved builds for this store
export async function GET(req: NextRequest) {
  const session = req.cookies.get('session_token')?.value
    || req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const builds = await getBuilds(user.storeId)
  return NextResponse.json({ builds })
}

// POST — save a new build
export async function POST(req: NextRequest) {
  const session = req.cookies.get('session_token')?.value
    || req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { label, code } = await req.json()
  if (!label || !code) {
    return NextResponse.json({ error: 'label and code required' }, { status: 400 })
  }

  const builds = await getBuilds(user.storeId)
  const newBuild = { id: uuidv4(), label, code, createdAt: new Date().toISOString() }
  const updated = [...builds, newBuild]
  await saveBuilds(user.storeId, updated)

  return NextResponse.json({ build: newBuild })
}

// DELETE — remove a build by id
export async function DELETE(req: NextRequest) {
  const session = req.cookies.get('session_token')?.value
    || req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { id } = await req.json()
  const builds = await getBuilds(user.storeId)
  const updated = builds.filter(b => b.id !== id)
  await saveBuilds(user.storeId, updated)

  return NextResponse.json({ ok: true })
}
```

---

## 3. Drupal Builds Helper — `/lib/drupalBuilds.ts`

```ts
const DRUPAL_BASE = process.env.DRUPAL_API_URL
const SERVICE_TOKEN = process.env.DRUPAL_SERVICE_TOKEN // server-to-server token

export async function getBuilds(storeId: string): Promise<Build[]> {
  const res = await fetch(
    `${DRUPAL_BASE}/jsonapi/commerce_store/online/${storeId}?fields[commerce_store--online]=field_page_builds`,
    {
      headers: {
        Authorization: `Bearer ${SERVICE_TOKEN}`,
        Accept: 'application/vnd.api+json',
      },
      cache: 'no-store',
    }
  )
  if (!res.ok) return []
  const data = await res.json()
  return data.data?.attributes?.field_page_builds ?? []
}

export async function saveBuilds(storeId: string, builds: Build[]): Promise<void> {
  await fetch(`${DRUPAL_BASE}/jsonapi/commerce_store/online/${storeId}`, {
    method: 'PATCH',
    headers: {
      Authorization: `Bearer ${SERVICE_TOKEN}`,
      'Content-Type': 'application/vnd.api+json',
      Accept: 'application/vnd.api+json',
    },
    body: JSON.stringify({
      data: {
        type: 'commerce_store--online',
        id: storeId,
        attributes: { field_page_builds: builds },
      },
    }),
  })
}

export interface Build {
  id: string
  label: string
  code: string
  createdAt: string
}
```

> `DRUPAL_SERVICE_TOKEN` is a long-lived server-to-server token (e.g. a Simple OAuth client_credentials grant). It never touches the client.

---

## 4. Chat Route — `/app/api/chat/route.ts`

Same as v1 with an updated system prompt that's aware of the storefront's visual language.

```ts
import { NextRequest, NextResponse } from 'next/server'
import Anthropic from '@anthropic-ai/sdk'
import { validateDrupalSession } from '@/lib/drupalAuth'

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY })

const rateLimitMap = new Map<string, { count: number; reset: number }>()
const RATE_LIMIT = 10
const RATE_WINDOW = 3600000

const SYSTEM_PROMPT = `You are a Next.js frontend engineer building components for RareImagery store owner storefronts.

RareImagery storefronts use a MySpace-era Y2K aesthetic. Components you generate must feel native to this visual language:
- Backgrounds: tiled emoji patterns, glitter textures, dark or neon gradients
- Typography: CSS blink, rainbow text, glitter-text animations, marquee elements
- Colors: hot pink, electric purple, cyber teal, scene gold, deep black — never neutral palettes
- Borders: pixel-art style, thick neon glows, dashed/dotted with color
- Layout: expressive, asymmetric, dense — not clean or minimal

Rules:
- Output only valid Next.js code (App Router, TypeScript)
- Use only Tailwind CSS utility classes for layout and spacing
- Use inline CSS or <style jsx> for Y2K animations (blink, marquee, glitter) — Tailwind cannot express these
- Never explain concepts — respond with code only
- Always include all necessary imports
- Output a single self-contained component per response
- If the request is unclear, default to a Y2K-styled card or section component`

export async function POST(req: NextRequest) {
  const session = req.cookies.get('session_token')?.value
    || req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const now = Date.now()
  const limit = rateLimitMap.get(user.id) ?? { count: 0, reset: now + RATE_WINDOW }
  if (now > limit.reset) { limit.count = 0; limit.reset = now + RATE_WINDOW }
  if (limit.count >= RATE_LIMIT) {
    return NextResponse.json({ error: 'Rate limit reached. Try again in an hour.' }, { status: 429 })
  }
  limit.count++
  rateLimitMap.set(user.id, limit)

  const { message } = await req.json()
  if (!message || typeof message !== 'string') {
    return NextResponse.json({ error: 'Invalid message' }, { status: 400 })
  }

  const response = await client.messages.create({
    model: 'claude-haiku-4-5-20251001',
    max_tokens: 2000,
    system: SYSTEM_PROMPT,
    messages: [{ role: 'user', content: message }],
  })

  const text = response.content
    .filter(block => block.type === 'text')
    .map(block => block.text)
    .join('')

  return NextResponse.json({ result: text })
}
```

---

## 5. Floating Builder Component — `/components/builder/FloatingBuilder.tsx`

Draggable panel with three tabs: **Generate**, **Preview**, **Saved Builds**.

```tsx
'use client'

import { useState, useRef } from 'react'
import BuildLibrary from './BuildLibrary'
import LivePreview from './LivePreview'

type Tab = 'generate' | 'preview' | 'saved'

export default function FloatingBuilder() {
  const [open, setOpen] = useState(false)
  const [tab, setTab] = useState<Tab>('generate')
  const [prompt, setPrompt] = useState('')
  const [result, setResult] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [saveLabel, setSaveLabel] = useState('')
  const [saving, setSaving] = useState(false)

  async function handleGenerate() {
    if (!prompt.trim()) return
    setLoading(true)
    setError('')
    setResult('')

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: prompt }),
      })
      if (res.status === 429) { setError('Rate limit reached. Try again in an hour.'); return }
      if (!res.ok) { setError('Something went wrong.'); return }
      const data = await res.json()
      setResult(data.result)
      setTab('preview') // auto-switch to preview after generation
    } catch {
      setError('Request failed. Check your connection.')
    } finally {
      setLoading(false)
    }
  }

  async function handleSave() {
    if (!result || !saveLabel.trim()) return
    setSaving(true)
    try {
      await fetch('/api/builds', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ label: saveLabel.trim(), code: result }),
      })
      setSaveLabel('')
    } finally {
      setSaving(false)
    }
  }

  function handleLoad(code: string) {
    setResult(code)
    setTab('preview')
  }

  if (!open) {
    return (
      <button
        onClick={() => setOpen(true)}
        className="fixed bottom-6 right-6 z-50 px-4 py-3 bg-purple-700 text-white
                   text-sm font-semibold rounded-full shadow-lg hover:bg-purple-800
                   transition-colors"
      >
        ✦ Page Builder
      </button>
    )
  }

  return (
    <div className="fixed bottom-6 right-6 z-50 w-[480px] max-h-[80vh] flex flex-col
                    bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">

      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 bg-purple-700 text-white">
        <span className="text-sm font-semibold">✦ Page Builder</span>
        <button onClick={() => setOpen(false)} className="text-white/70 hover:text-white text-lg leading-none">×</button>
      </div>

      {/* Tabs */}
      <div className="flex border-b border-gray-200">
        {(['generate', 'preview', 'saved'] as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`flex-1 py-2 text-xs font-medium capitalize transition-colors
              ${tab === t ? 'border-b-2 border-purple-700 text-purple-700' : 'text-gray-500 hover:text-gray-700'}`}
          >
            {t === 'saved' ? 'Saved Builds' : t}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-y-auto">

        {tab === 'generate' && (
          <div className="p-4 space-y-3">
            <textarea
              className="w-full h-28 p-3 border border-gray-200 rounded-lg text-sm
                         focus:outline-none focus:ring-2 focus:ring-purple-600 resize-none"
              placeholder="Describe the component or section you need..."
              value={prompt}
              onChange={e => setPrompt(e.target.value)}
            />
            <button
              onClick={handleGenerate}
              disabled={loading || !prompt.trim()}
              className="w-full py-2 bg-purple-700 text-white text-sm font-medium rounded-lg
                         hover:bg-purple-800 disabled:opacity-50 transition-colors"
            >
              {loading ? 'Generating...' : 'Generate'}
            </button>
            {error && <p className="text-xs text-red-500">{error}</p>}
            {result && (
              <div className="relative">
                <pre className="p-3 bg-gray-900 text-gray-100 text-xs rounded-lg overflow-x-auto whitespace-pre-wrap">
                  {result}
                </pre>
                <button
                  onClick={() => navigator.clipboard.writeText(result)}
                  className="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-700
                             text-gray-300 rounded hover:bg-gray-600"
                >
                  Copy
                </button>
              </div>
            )}
            {result && (
              <div className="flex gap-2">
                <input
                  className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm
                             focus:outline-none focus:ring-2 focus:ring-purple-600"
                  placeholder="Name this build..."
                  value={saveLabel}
                  onChange={e => setSaveLabel(e.target.value)}
                />
                <button
                  onClick={handleSave}
                  disabled={saving || !saveLabel.trim()}
                  className="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg
                             hover:bg-gray-700 disabled:opacity-50 transition-colors"
                >
                  {saving ? 'Saving...' : 'Save'}
                </button>
              </div>
            )}
          </div>
        )}

        {tab === 'preview' && (
          <LivePreview code={result} />
        )}

        {tab === 'saved' && (
          <BuildLibrary onLoad={handleLoad} />
        )}
      </div>
    </div>
  )
}
```

---

## 6. Build Library — `/components/builder/BuildLibrary.tsx`

```tsx
'use client'

import { useEffect, useState } from 'react'
import { Build } from '@/lib/drupalBuilds'

export default function BuildLibrary({ onLoad }: { onLoad: (code: string) => void }) {
  const [builds, setBuilds] = useState<Build[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/builds')
      .then(r => r.json())
      .then(d => setBuilds(d.builds ?? []))
      .finally(() => setLoading(false))
  }, [])

  async function handleDelete(id: string) {
    await fetch('/api/builds', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    })
    setBuilds(prev => prev.filter(b => b.id !== id))
  }

  if (loading) return <p className="p-4 text-sm text-gray-400">Loading builds...</p>
  if (!builds.length) return <p className="p-4 text-sm text-gray-400">No saved builds yet.</p>

  return (
    <ul className="divide-y divide-gray-100">
      {builds.map(b => (
        <li key={b.id} className="flex items-center gap-2 px-4 py-3">
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-800 truncate">{b.label}</p>
            <p className="text-xs text-gray-400">{new Date(b.createdAt).toLocaleDateString()}</p>
          </div>
          <button
            onClick={() => onLoad(b.code)}
            className="px-3 py-1 text-xs bg-purple-700 text-white rounded hover:bg-purple-800"
          >
            Load
          </button>
          <button
            onClick={() => handleDelete(b.id)}
            className="px-3 py-1 text-xs bg-gray-100 text-gray-500 rounded hover:bg-gray-200"
          >
            Delete
          </button>
        </li>
      ))}
    </ul>
  )
}
```

---

## 7. Live Preview — `/components/builder/LivePreview.tsx`

Renders generated code in a sandboxed iframe using a simple Babel/CDN transform.

```tsx
'use client'

import { useEffect, useRef } from 'react'

export default function LivePreview({ code }: { code: string }) {
  const iframeRef = useRef<HTMLIFrameElement>(null)

  useEffect(() => {
    if (!iframeRef.current || !code) return
    const html = `
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  <style>
    body { margin: 0; background: #0a0a0a; }
    @keyframes blink { 50% { opacity: 0; } }
    @keyframes rainbow { 0%{color:#ff0080} 25%{color:#00ffff} 50%{color:#ffff00} 75%{color:#00ff80} 100%{color:#ff0080} }
  </style>
</head>
<body>
  <div id="root"></div>
  <script type="text/babel" data-presets="react">
    ${code}
    // Try to render whatever the default export is
    try {
      const Component = typeof exports !== 'undefined' && exports.default ? exports.default : null
      if (Component) {
        ReactDOM.render(React.createElement(Component), document.getElementById('root'))
      }
    } catch(e) {
      document.getElementById('root').innerHTML = '<p style="color:#ff4444;padding:1rem;font-family:monospace">' + e.message + '</p>'
    }
  </script>
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
</body>
</html>`
    iframeRef.current.srcdoc = html
  }, [code])

  if (!code) {
    return (
      <div className="flex items-center justify-center h-48 text-sm text-gray-400">
        Generate a component to preview it here
      </div>
    )
  }

  return (
    <iframe
      ref={iframeRef}
      sandbox="allow-scripts"
      className="w-full h-[400px] border-0"
      title="Component Preview"
    />
  )
}
```

---

## 8. Environment Variables

```env
ANTHROPIC_API_KEY=sk-ant-...
DRUPAL_API_URL=https://api.rareimagery.net
DRUPAL_SERVICE_TOKEN=your-server-to-server-oauth-token
```

---

## Where It Lives

Mount `<FloatingBuilder />` in the storefront layout, gated behind an `isOwner` check:

```tsx
// app/[store]/layout.tsx
import FloatingBuilder from '@/components/builder/FloatingBuilder'

export default function StoreLayout({ children, isOwner }) {
  return (
    <>
      {children}
      {isOwner && <FloatingBuilder />}
    </>
  )
}
```

The owner sees their live storefront, opens the panel, generates a component, previews it in context, and saves it to their library — all without leaving the page.

---

## Cost Profile

| Volume | Chat API Cost | Builds Storage |
|--------|--------------|----------------|
| 500 req/month | ~$1.50 | ~0 (text in Drupal) |
| 1,000 req/month | ~$3.00 | ~0 |
| Reused builds | $0.00 | $0.00 |

---

## Production Upgrades

- **Rate limiter:** Swap in-memory map for Upstash Redis (Vercel-native, survives cold starts)
- **Prompt caching:** Enable Anthropic cache\_control on the system prompt — cuts cost ~90% on the prompt tokens
- **Streaming:** `stream: true` on the Anthropic call for faster perceived response on long outputs
- **Build limit:** Cap saved builds per store (e.g. 20) to bound Drupal field size
- **Export:** Add a one-click copy-to-clipboard or download on each saved build
