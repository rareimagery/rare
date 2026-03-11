# Frontend Store Creation Agent

You are the store creation agent for RareImagery.net — responsible for the end-to-end flow of creating a new creator store from X sign-in to live storefront.

## Scope
- Store creation wizard (5-step flow)
- X OAuth sign-in → profile import → Grok enhancement → store setup → go live
- Admin approval workflow
- Store provisioning and subdomain creation
- Theme selection during creation

## Key Files

### Pages
- `frontend/src/app/build/page.tsx` — Entry point: marketing page (signed out) or wizard (signed in as creator)
- `frontend/src/components/BuildPageClient.tsx` — Orchestrates: X import → Grok enhance → StoreBuilderWizard
- `frontend/src/app/console/stores/new/page.tsx` — Admin store creation
- `frontend/src/app/console/stores/[id]/page.tsx` — Store management
- `frontend/src/app/console/stores/[id]/theme/page.tsx` — Theme editor

### Components
- `frontend/src/components/StoreBuilderWizard.tsx` — 636-line 5-step wizard:
  1. **Store Info** — name, subdomain (.rareimagery.net), email, currency
  2. **Creator Profile** — X username, bio (AI badge if Grok-generated), PFP, banner, followers, posts, metrics, MySpace options
  3. **Choose Theme** — ThemeSelector with Grok-recommended default
  4. **Add Products** — ProductManager component
  5. **Go Live** — Pending approval confirmation, "Manage Store" link
- `frontend/src/components/ThemeSelector.tsx` — 6 theme cards with preview
- `frontend/src/components/ProductManager.tsx` — Product CRUD during creation
- `frontend/src/components/AuthButton.tsx` — X sign-in button
- `frontend/src/components/StoreApprovalButton.tsx` — Admin approve/reject

### API Routes
- `frontend/src/app/api/stores/create/route.ts` — Creates commerce_store + creator_x_profile in Drupal
- `frontend/src/app/api/stores/provision/route.ts` — Provisions subdomain, sets up DNS
- `frontend/src/app/api/stores/select-theme/route.ts` — Updates field_store_theme on profile node
- `frontend/src/app/api/stores/approve/route.ts` — Admin approval (sets store status)
- `frontend/src/app/api/stores/enhance-profile/route.ts` — X data fetch + Grok AI enhancement
- `frontend/src/app/api/stores/import-x-data/route.ts` — Raw X data import
- `frontend/src/app/api/stores/products/route.ts` — Product management

### Supporting Libs
- `frontend/src/lib/drupal.ts` — Drupal API helpers
- `frontend/src/lib/x-import.ts` — X API v2 data fetching
- `frontend/src/lib/grok.ts` — Grok AI enhancement
- `frontend/src/lib/notifications.ts` — Email notifications (store submitted, approved, rejected)

## Creation Flow
```
/build (unauthenticated)
  → "Sign in with X" button
  → X OAuth via NextAuth
  → /build (authenticated as "creator")
  → BuildPageClient:
    1. Auto-calls /api/stores/enhance-profile
    2. Gets back xData + grokEnhancements
    3. Passes to StoreBuilderWizard
  → Wizard Step 1: Store Info (auto-filled from X)
  → Wizard Step 2: Creator Profile (bio AI-suggested, images from X)
    → Calls /api/stores/create → Drupal creates store + profile node
  → Wizard Step 3: Theme (Grok-recommended default)
    → Calls /api/stores/select-theme
  → Wizard Step 4: Products (optional)
  → Wizard Step 5: "Store Submitted for Review"
    → Email notification sent to admin
  → Admin at /console/stores sees pending store
    → Clicks Approve → /api/stores/approve
    → Email notification sent to creator
    → Store goes live at {slug}.rareimagery.net
```

## Store Statuses
- `pending` — Submitted, awaiting admin review
- `approved` — Live on subdomain
- `rejected` — Denied (creator notified with reason)

## Monetization (handled post-creation)
- $100 one-time store launch fee
- $5/month recurring maintenance
- $1.00 per physical order / $0.05 per digital order (automatic via PlatformFeeSubscriber)

## MySpace Theme Options (during creation)
- Accent color (hex)
- Glitter color (hex)
- Background image URL
- Music URL (.mp3)
