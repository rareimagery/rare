# Step 10: Store Creation Flow

**Agent:** Store Creation (`store-creation.md`)

## End-to-End Flow

```
Visitor lands on /build
    ↓
"Sign in with X" → X OAuth → NextAuth session
    ↓
BuildPageClient auto-calls /api/stores/enhance-profile
    ↓
X data + Grok enhancements returned
    ↓
StoreBuilderWizard (5 steps):
    Step 1: Store Info (name, subdomain, email, currency)
    Step 2: Creator Profile (bio, images, posts — auto-filled)
       → POST /api/stores/create → Drupal creates store + profile
    Step 3: Choose Theme (Grok-recommended default)
       → POST /api/stores/select-theme
    Step 4: Add Products (optional)
    Step 5: "Store Submitted for Review"
       → Email sent to admin
    ↓
Admin visits /console/stores → sees pending store
    ↓
Clicks Approve → POST /api/stores/approve
    ↓
Email sent to creator → store goes live at {slug}.rareimagery.net
```

## Key Components

### Entry Point: `/build` page
File: `frontend/src/app/build/page.tsx` (242 LOC)
- **Not signed in:** Marketing landing with "Sign in with X" CTA
- **Signed in as creator:** Shows `BuildPageClient` → wizard
- **Signed in as store_owner:** Redirects to `/console`

### Orchestrator: `BuildPageClient`
File: `frontend/src/components/BuildPageClient.tsx` (182 LOC)
1. Calls `/api/stores/enhance-profile` on mount
2. Shows loading state while X data + Grok run
3. Passes results to `StoreBuilderWizard`

### Wizard: `StoreBuilderWizard`
File: `frontend/src/components/StoreBuilderWizard.tsx` (636 LOC)

**Step 1 — Store Info:**
- Store name (auto: "{Display Name}'s Store")
- Subdomain (auto: X handle → `{handle}.rareimagery.net`)
- Contact email
- Currency (USD/EUR/GBP/CAD/AUD)

**Step 2 — Creator Profile:**
- X username (pre-filled)
- Bio with "AI suggested" badge if Grok-generated
- Profile picture preview (from X CDN)
- Banner image preview
- Follower count
- Top posts JSON
- Top followers JSON
- Metrics JSON
- Grok product suggestions grid
- MySpace options (accent color, glitter color, background URL, music URL)
- **Action:** Calls `/api/stores/create` → creates store in Drupal

**Step 3 — Choose Theme:**
- `ThemeSelector` component with 6 theme cards
- Grok-recommended theme pre-selected
- **Action:** Calls `/api/stores/select-theme`

**Step 4 — Add Products:**
- `ProductManager` component
- Add products immediately or skip for later

**Step 5 — Submitted:**
- Confirmation with subdomain preview
- "Manage Store" link to `/console/stores/[id]`
- "While you wait" checklist

## Admin Approval

### Console: `/console/stores`
- Lists all stores with status badges (pending/approved/rejected)
- Admin sees all; store_owners see only theirs

### Approval: `StoreApprovalButton`
File: `frontend/src/components/StoreApprovalButton.tsx` (77 LOC)
- Approve → sets status, sends email to creator
- Reject → sets status with reason, sends rejection email

### Notifications
File: `frontend/src/lib/notifications.ts` (274 LOC)
- `sendStoreSubmittedEmail(adminEmail, storeDetails)` — new store alert
- `sendStoreApprovedEmail(creatorEmail, storeName, storeUrl)` — approval
- `sendStoreRejectedEmail(creatorEmail, storeName, reason)` — rejection
- `sendNewSaleEmail(creatorEmail, orderDetails)` — sale notification
- Uses Brevo SMTP for email, Telnyx for SMS

## Store Statuses

| Status | Meaning | Visible at |
|--------|---------|-----------|
| `pending` | Submitted, awaiting review | /console (admin) |
| `approved` | Live on subdomain | Public storefront |
| `rejected` | Denied with reason | /console (creator notified) |

## Monetization (Post-Creation)

| Fee | Amount | When |
|-----|--------|------|
| Store launch | $100 one-time | On approval |
| Maintenance | $5/month | Recurring via Stripe |
| Physical order | $1.00 | Per order (automatic) |
| Digital order | $0.05 | Per order (automatic) |

## Next Step

→ [Step 11: Themes & Page Builder](11_THEMES.md)
