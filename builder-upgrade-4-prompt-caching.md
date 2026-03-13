# Builder Upgrade 4 — Anthropic Prompt Caching

## Problem

The system prompt (~300 tokens) is identical on every request. Without caching, Anthropic charges full input price for these tokens every call. Prompt caching cuts cost ~90% on cached tokens.

## Solution

Add `cache_control: { type: "ephemeral" }` to the system prompt block in the Anthropic API call. This tells Anthropic to cache the system prompt server-side and reuse it across requests within the cache TTL (5 minutes).

## File

`src/app/api/chat/route.ts`

## Change

```ts
// Before:
system: SYSTEM_PROMPT,

// After:
system: [
  {
    type: "text",
    text: systemPromptText,
    cache_control: { type: "ephemeral" },
  },
],
```

The system prompt must be passed as an array of content blocks (not a plain string) to use cache_control.

## Cost Impact

| Metric | Before | After |
|--------|--------|-------|
| System prompt tokens per call | ~300 (full price) | ~300 (90% discount after first call) |
| Effective cost for 1000 req/month | ~$3.00 | ~$0.50 |

## Verification

Check Anthropic dashboard usage — cached tokens should show up as `cache_read_input_tokens` after the first request within each 5-minute window.
