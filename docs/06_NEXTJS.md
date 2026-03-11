# Step 6: Next.js Frontend

**Agent:** Next.js (`nextjs.md`)

## Stack

- Next.js 16.1.6 (App Router)
- React 19.2.3
- TypeScript 5
- Tailwind CSS v4
- NextAuth 4.24.13
- Anthropic SDK (Claude Haiku)
- Stripe SDK

## Directory Structure

```
frontend/src/
├── app/
│   ├── page.tsx                          # Landing page (creator grid)
│   ├── layout.tsx                        # Root layout (Sora, DM Sans, JetBrains Mono)
│   ├── globals.css                       # Tailwind imports
│   ├── login/page.tsx                    # NextAuth login
│   ├── build/page.tsx                    # Store creation entry (242 LOC)
│   ├── stores/[creator]/page.tsx         # Public store page (176 LOC)
│   ├── products/[slug]/page.tsx          # Product detail (465 LOC)
│   ├── console/                          # Admin dashboard
│   │   ├── page.tsx                      # Dashboard home
│   │   ├── stores/page.tsx              # Store list
│   │   ├── stores/new/page.tsx          # Create store
│   │   ├── stores/[id]/page.tsx         # Edit store
│   │   └── stores/[id]/theme/page.tsx   # Theme editor (379 LOC)
│   └── api/                              # 17 API routes (see Step 8)
│
├── components/
│   ├── themes/                           # 6 theme implementations
│   │   ├── Xai3Theme.tsx                # Default (975 LOC)
│   │   ├── XMimicTheme.tsx              # X.com mimic (1,342 LOC)
│   │   ├── MySpaceTheme.tsx             # Y2K nostalgia (1,151 LOC)
│   │   ├── MinimalTheme.tsx             # Clean (509 LOC)
│   │   ├── EditorialTheme.tsx           # Magazine (353 LOC)
│   │   └── NeonTheme.tsx               # Cyberpunk (331 LOC)
│   ├── builder/                          # Page builder
│   │   ├── FloatingBuilder.tsx          # Draggable panel (178 LOC)
│   │   ├── LivePreview.tsx              # Sandboxed iframe (72 LOC)
│   │   ├── BuildLibrary.tsx             # Saved builds (69 LOC)
│   │   └── BuilderGate.tsx             # Auth gate (22 LOC)
│   ├── StoreBuilderWizard.tsx           # 5-step creation (636 LOC)
│   ├── ThemeSelector.tsx                # Theme picker (125 LOC)
│   ├── PrintfulManager.tsx              # POD products (368 LOC)
│   ├── ProductTabs.tsx                  # Product display (321 LOC)
│   ├── ProductManager.tsx               # Add/edit products (192 LOC)
│   ├── AddToCartBlock.tsx               # Cart interaction (258 LOC)
│   ├── ProductGallery.tsx               # Image gallery (157 LOC)
│   ├── Sidebar.tsx                      # Navigation (228 LOC)
│   ├── NotificationPreferences.tsx      # Alert settings (140 LOC)
│   ├── AuthButton.tsx                   # Login/logout (34 LOC)
│   └── ... (7 more small components)
│
├── lib/
│   ├── drupal.ts                        # API client + types (765 LOC)
│   ├── x-import.ts                      # X data fetch (445 LOC)
│   ├── notifications.ts                 # Email/SMS (274 LOC)
│   ├── grok.ts                          # Grok AI (125 LOC)
│   ├── drupalBuilds.ts                  # Build storage (85 LOC)
│   ├── x-subscription.ts               # Subscription check (75 LOC)
│   ├── mock-products.ts                 # Demo data (48 LOC)
│   ├── slugs.ts                         # URL slugs (31 LOC)
│   └── stripe.ts                        # Stripe helpers (16 LOC)
│
└── middleware.ts                         # Route protection
```

## Key Patterns

### Data Fetching
- Server components fetch directly from Drupal via `drupal.ts`
- Client components call `/api/*` routes (never Drupal directly)
- `drupalAuthHeaders()` adds Basic Auth for all Drupal calls

### Styling
- Tailwind v4 utility classes
- Dark theme: zinc-950 backgrounds, white text
- Fonts: Sora (headings), DM Sans (body), JetBrains Mono (code)

### Static Generation
- Store pages use `generateStaticParams()` for SSG
- Product pages are pre-rendered at build time
- Revalidation: 60s server cache + 300s stale-while-revalidate

### Error Handling
- `not-found.tsx` for invalid store URLs
- API routes return `{ error: string }` with appropriate status codes
- Drupal unreachable at build time → empty grid (graceful fallback)

## Config Files

| File | Purpose |
|------|---------|
| `next.config.ts` | Image domains (72.62.80.155, *.rareimagery.net, pbs.twimg.com) |
| `vercel.json` | Cache headers |
| `tsconfig.json` | Strict TS, `@/*` path alias |
| `package.json` | Dependencies |

## Next Step

→ [Step 7: Authentication](07_AUTH.md)
