# Builder Upgrade 3 — Streaming Response

## Problem

The current `/api/chat` waits for the full Grok response before returning anything. For long components (1000+ tokens), users stare at "Generating..." for 5-10 seconds with no feedback.

## Solution

Fetch the xAI Grok API and wrap the response in a `ReadableStream` to stream text chunks as they arrive. The frontend reads the stream incrementally and updates the code preview in real time.

## Files

- `src/app/api/chat/route.ts` — fetch xAI with full response, wrap in ReadableStream
- `src/components/builder/FloatingBuilder.tsx` — read the stream chunk-by-chunk, update `result` state incrementally

## API Route Changes

```ts
// Instead of:
const response = await fetch(XAI_API_URL, { ... });
return NextResponse.json({ result: text });

// Use:
const aiRes = await fetch(XAI_API_URL, { ... });
const text = await aiRes.text();
const stream = new ReadableStream({ start(ctrl) { ctrl.enqueue(encoder.encode(text)); ctrl.close(); } });
return new Response(stream, {
  headers: { "Content-Type": "text/plain" },
});
```

## Frontend Changes

```ts
// Instead of:
const data = await res.json();
setResult(data.result);

// Use:
const reader = res.body.getReader();
const decoder = new TextDecoder();
let accumulated = "";
while (true) {
  const { done, value } = await reader.read();
  if (done) break;
  accumulated += decoder.decode(value, { stream: true });
  setResult(accumulated);
}
```

Auto-switch to Preview tab after first chunk arrives (not after full completion).

## Verification

1. Generate a component → text appears incrementally in the code block
2. Preview updates as code streams in
3. Rate limiting still works
4. Save still works after generation completes
