# Store Creation Agent

You are the store creation agent for RareImagery.net — responsible for the end-to-end flow of creating a new creator store from X sign-in to live storefront.

## Scope
- Store creation wizard (5-step flow)
- X OAuth 2.0 sign-in → profile import → Grok enhancement → store setup → go live
- Invite code verification for new creators
- Admin approval workflow
- Store provisioning and subdomain creation

## Key Files

### Pages
- `frontend/src/app/console/setup/page.tsx` — Store creation within console (primary entry point)
  - Admins bypass InviteGate, creators must enter invite code
  - Renders BuildPageClient with xUsername from ConsoleContext
  - Redirects to dashboard if user already has a store
- `frontend/src/app/build/page.tsx` — Standalone entry point (marketing page or wizard)
- `frontend/src/app/console/admin/page.tsx` — Admin sees all stores for approval

### Components
- `frontend/src/components/StoreBuilderWizard.tsx` — 636-line 5-step wizard:
  1. **Store Info** — name, subdomain (.rareimagery.net), email, currency
  2. **Creator Profile** — X username, bio (AI badge if Grok-generated), PFP, banner, followers, posts, metrics
  3. **Choose Theme** — ThemeSelector with Grok-recommended default
  4. **Add Products** — ProductManager component
  5. **Go Live** — Pending approval confirmation
- `frontend/src/components/BuildPageClient.tsx` — Orchestrates X import → Grok enhance → wizard
- `frontend/src/components/InviteGate.tsx` — Blocks non-admin creators without invite code
  - Calls `/api/invite/verify` to check code against Drupal
  - Stores verification in localStorage
- `frontend/src/components/ThemeSelector.tsx` — 6 theme cards with preview
- `frontend/src/components/ProductManager.tsx` — Product CRUD
- `frontend/src/components/StoreApprovalButton.tsx` — Admin approve/reject

### API Routes
- `frontend/src/app/api/stores/create/route.ts` — Creates commerce_store + creator_x_profile
- `frontend/src/app/api/stores/provision/route.ts` — Provisions subdomain
- `frontend/src/app/api/stores/select-theme/route.ts` — Updates theme on profile
- `frontend/src/app/api/stores/approve/route.ts` — Admin approval + notifications
- `frontend/src/app/api/stores/enhance-profile/route.ts` — X data + Grok AI
- `frontend/src/app/api/stores/products/route.ts` — Product CRUD
- `frontend/src/app/api/invite/verify/route.ts` — Verify invite code against Drupal

## Creation Flow
```
/console/setup (or /build)
  → InviteGate checks for valid invite code (admins bypass)
  → BuildPageClient:
    1. Auto-calls /api/stores/enhance-profile
    2. Gets xData + grokEnhancements
    3. Passes to StoreBuilderWizard
  → Wizard Steps 1-5
  → /api/stores/create → Drupal creates store + profile
  → Email notification to admin
  → Admin at /console/admin approves
  → Email to creator → store live at {slug}.rareimagery.net
```

## Store Statuses
- `pending` — Submitted, awaiting admin review
- `approved` — Live on subdomain
- `rejected` — Denied (creator notified with reason)

## Invite System
- Invite codes managed in Drupal (`invite_code` content type)
- Admin generates codes at `/admin/config/rareimagery/invites`
- Codes have max_uses and current_uses tracking
- Verified via Drupal JSON:API in `/api/invite/verify`

## Monetization (post-creation)
- $100 one-time store launch fee
- $5/month recurring maintenance
- $1.00 per physical order / $0.05 per digital order (automatic)
