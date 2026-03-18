# Builder Upgrade 4 — xAI/Grok Prompt Strategy

## Note

This upgrade was originally designed for Anthropic prompt caching. The project has since migrated to xAI/Grok exclusively. xAI does not use the same `cache_control` mechanism.

## Current Approach

The system prompt is sent with every request to `https://api.x.ai/v1/chat/completions`. The `grok-3-mini` model is cost-efficient enough that explicit prompt caching is not required at current usage levels.

## File

`src/app/api/chat/route.ts`

## Current Pattern

```ts
body: JSON.stringify({
  model: "grok-3-mini",
  messages: [
    { role: "system", content: systemPromptText },
    { role: "user",   content: message },
  ],
  temperature: 0.6,
  max_tokens: 4000,
})
```

## Cost Impact

| Metric | Value |
|--------|-------|
| System prompt tokens per call | ~300 |
| Grok-3-mini input cost | Very low (see pay.x.ai) |
| Action needed | None at current scale |

## Verification

Check xAI dashboard usage at console.x.ai for token counts per request.
