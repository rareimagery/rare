# Builder Upgrade 1 — Fix Write Auth in drupalBuilds.ts

## Problem

`drupalBuilds.ts` uses `drupalAuthHeaders()` (Basic Auth) for the PATCH call in `saveBuilds()`. Drupal Commerce entity writes require cookie session auth (`drupalWriteHeaders()`). This is the same bug that was fixed across all other write paths (store creation, Printful sync, X import, etc.).

Reads (GET) work fine with Basic Auth — only the PATCH in `saveBuilds()` is broken.

## File

`src/lib/drupalBuilds.ts`

## Changes

1. Import `drupalWriteHeaders` instead of (or in addition to) `drupalAuthHeaders`
2. In `saveBuilds()`, replace `drupalAuthHeaders()` with `await drupalWriteHeaders()` for the PATCH call
3. Make `saveBuilds()` async (it already is) — just ensure the headers await is correct
4. Keep `drupalAuthHeaders()` for the GET calls in `resolveStoreUuid()` and `getBuilds()` — reads work fine with Basic Auth

## Before

```ts
import { drupalAuthHeaders } from "./drupal";

// In saveBuilds():
headers: {
  ...drupalAuthHeaders(),
  "Content-Type": "application/vnd.api+json",
}
```

## After

```ts
import { drupalAuthHeaders, drupalWriteHeaders } from "./drupal";

// In saveBuilds():
const writeHeaders = await drupalWriteHeaders();
headers: {
  ...writeHeaders,
  "Content-Type": "application/vnd.api+json",
}
```

## Verification

Save a build via the FloatingBuilder panel → should persist to Drupal without 403/422 errors.
