import { NextRequest, NextResponse } from 'next/server'
import Anthropic from '@anthropic-ai/sdk'
import { validateDrupalSession } from '@/lib/drupalAuth'

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY })

// --- Rate limiter (in-memory; swap for Upstash Redis in production) ---
const rateLimitMap = new Map<string, { count: number; reset: number }>()
const RATE_LIMIT = 10
const RATE_WINDOW = 3600000 // 1 hour

// --- U2: Theme-aware system prompts ---

const BASE_PROMPT = `You are a Next.js frontend engineer building components for RareImagery store owner storefronts.

Rules:
- Output only valid Next.js code (App Router, TypeScript)
- Use only Tailwind CSS utility classes for layout and spacing
- Use inline CSS or <style jsx> for animations Tailwind cannot express
- Never explain concepts — respond with code only
- Always include all necessary imports
- Output a single self-contained component per response`

const THEME_PROMPTS: Record<string, string> = {
  xai3: `Visual language: Dark zinc/black backgrounds, indigo/purple accent colors, monospace stat blocks, X-feed center column layout, gold highlight touches. Typography: Sora + DM Sans + JetBrains Mono. Premium, data-rich, X-native aesthetic.`,

  minimal: `Visual language: Clean white/light gray backgrounds, thin 1px borders, generous whitespace, system font stack (Inter/SF Pro), subtle box shadows. Never bold colors. Elegant restraint.`,

  neon: `Visual language: Pure black (#000) backgrounds, neon glow borders (cyan #00ffff, magenta #ff00ff, green #39ff14), synthwave gradients, bold sans-serif fonts. Glowing text shadows, animated pulse effects.`,

  editorial: `Visual language: Serif headings (Playfair Display/Georgia), cream/warm background tones (#faf8f5), magazine-style CSS grid layouts, editorial photography feel, generous line-height, muted earth tones.`,

  myspace: `Visual language: Y2K aesthetic — glitter textures, blink animations, marquee elements, tiled emoji backgrounds, hot pink/electric purple/cyber teal/scene gold. Pixel-art borders, thick neon glows, dashed/dotted colored borders. Dense, expressive, asymmetric layout.`,

  xmimic: `Visual language: X/Twitter clone layout — 600px center column, #1DA1F2 blue accent, avatar+handle patterns, card borders with subtle gray, timeline feed layout. Segoe UI / system font. Minimal, familiar, feed-native.`,
}

export async function POST(req: NextRequest) {
  // Auth guard
  const session =
    req.cookies.get('session_token')?.value ||
    req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  // Rate limit
  const now = Date.now()
  const limit = rateLimitMap.get(user.id) ?? { count: 0, reset: now + RATE_WINDOW }
  if (now > limit.reset) {
    limit.count = 0
    limit.reset = now + RATE_WINDOW
  }
  if (limit.count >= RATE_LIMIT) {
    return NextResponse.json(
      { error: 'Rate limit reached. Try again in an hour.' },
      { status: 429 }
    )
  }
  limit.count++
  rateLimitMap.set(user.id, limit)

  // Parse request — U2: accept theme field
  const { message, theme } = await req.json()
  if (!message || typeof message !== 'string') {
    return NextResponse.json({ error: 'Invalid message' }, { status: 400 })
  }

  // U2: Compose theme-aware system prompt
  const themeKey = theme && THEME_PROMPTS[theme] ? theme : 'xai3'
  const systemPromptText = `${BASE_PROMPT}\n\n${THEME_PROMPTS[themeKey]}`

  // U3: Stream response via ReadableStream
  const stream = client.messages.stream({
    model: 'claude-haiku-4-5-20251001',
    max_tokens: 2000,
    // U4: Prompt caching — cache_control on system block
    system: [
      {
        type: 'text' as const,
        text: systemPromptText,
        cache_control: { type: 'ephemeral' as const },
      },
    ],
    messages: [{ role: 'user' as const, content: message }],
  })

  const readableStream = new ReadableStream({
    async start(controller) {
      const encoder = new TextEncoder()
      try {
        for await (const event of stream) {
          if (
            event.type === 'content_block_delta' &&
            event.delta.type === 'text_delta'
          ) {
            controller.enqueue(encoder.encode(event.delta.text))
          }
        }
      } catch (err) {
        controller.enqueue(
          encoder.encode(`\n\n[Error: ${err instanceof Error ? err.message : 'Stream failed'}]`)
        )
      } finally {
        controller.close()
      }
    },
  })

  return new Response(readableStream, {
    headers: {
      'Content-Type': 'text/event-stream',
      'Cache-Control': 'no-cache',
      Connection: 'keep-alive',
    },
  })
}
