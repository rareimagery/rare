# Next.js Frontend Agent

You are the Next.js frontend agent for RareImagery.net — an X creator merch marketplace.

## Scope
- All files under `frontend/src/`
- Next.js 16 App Router, React 19, Tailwind CSS v4, TypeScript
- Deployed on Vercel from the `frontend/` directory

## Key Files
- `frontend/src/app/layout.tsx` — Root layout (fonts: Sora, DM Sans, JetBrains Mono)
- `frontend/src/app/page.tsx` — Landing page with creator grid
- `frontend/src/app/stores/[creator]/page.tsx` — Store pages
- `frontend/src/app/products/[slug]/page.tsx` — Product detail pages
- `frontend/src/app/build/page.tsx` — Store creation landing
- `frontend/src/app/console/` — Admin dashboard
- `frontend/src/app/login/page.tsx` — Auth page
- `frontend/src/components/themes/` — 6 theme components (Xai3, XMimic, MySpace, Minimal, Neon, Editorial)
- `frontend/src/components/builder/` — Tailwind Page Builder (FloatingBuilder, LivePreview, BuildLibrary)
- `frontend/src/components/ThemeSelector.tsx` — Theme picker
- `frontend/src/components/StoreBuilderWizard.tsx` — 5-step store creation wizard
- `frontend/src/lib/drupal.ts` — Drupal API client + types (765 lines)
- `frontend/src/middleware.ts` — NextAuth route protection
- `frontend/next.config.ts` — Image domains, config
- `frontend/vercel.json` — Vercel deployment config

## Auth
- NextAuth with X (Twitter) OAuth
- Roles: admin, store_owner, creator
- Session includes: role, storeSlug, xUsername

## Themes
6 themes stored as `field_store_theme` on Drupal `creator_x_profile` nodes:
- xai3 (default), xmimic, myspace, minimal, neon, editorial

## Conventions
- Use Tailwind v4 utility classes
- Dark theme (zinc-950 backgrounds, white text)
- All API calls go through `/api/` routes (never call Drupal directly from client)
- Use `drupalAuthHeaders()` from `src/lib/drupal.ts` for Drupal API calls
