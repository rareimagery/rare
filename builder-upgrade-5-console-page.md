# Builder Upgrade 5 — Console Builder Page

## Problem

The Page Builder is only accessible as a floating widget on the live store page. Store owners should also be able to manage their builds from the console dashboard without navigating to their live store.

## Solution

Create `/console/builder` page that shows:
1. The AI generation form (same as the floating panel but full-width)
2. The live preview (larger iframe)
3. The saved builds library with load/delete/export

Add a "Page Builder" link to the ConsoleSidebar.

## Files to Create

### `src/app/console/builder/page.tsx`

Full-page builder experience within the console layout:
- Left column: prompt input + code output
- Right column: live preview (larger than the floating panel's 400px)
- Bottom: saved builds library

Use `useConsole()` to get `storeId` and `storeSlug`. Reuse existing components where possible but lay them out in a full-page format rather than the cramped floating panel.

## Files to Modify

### `src/components/ConsoleSidebar.tsx`

Add a "Page Builder" link between "Subscriptions" and "Social":

```ts
{ href: "/console/builder", label: "Page Builder", icon: "M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" }
```

## Verification

1. Navigate to `/console/builder` → see full-page builder
2. Generate a component → preview renders in the large iframe
3. Save a build → appears in the library
4. Load a saved build → preview updates
5. Sidebar link highlights when active
