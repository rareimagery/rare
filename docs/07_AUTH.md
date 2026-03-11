# Step 7: Authentication

**Agent:** Connection (`drupal-nextjs-connection.md`)

## Two Auth Systems

| System | Method | Purpose |
|--------|--------|---------|
| **NextAuth** | X (Twitter) OAuth | User sessions in the browser |
| **Drupal Basic Auth** | Username/password | Server-to-server API calls |

## NextAuth (Frontend)

### Config
File: `frontend/src/app/api/auth/[...nextauth]/route.ts` (229 LOC)

```
Provider: X (Twitter) OAuth 2.0
Credentials: X_CONSUMER_KEY + X_CONSUMER_SECRET
Callback URL: https://rareimagery.net/api/auth/callback/twitter
```

### Session Data
```typescript
{
  user: { name, email, image },
  role: "admin" | "store_owner" | "creator",
  storeSlug: "rareimagery",      // if store_owner
  xUsername: "RareImagery",       // X handle
  xAccessToken: "...",           // for X API calls
  xId: "123456789"               // X user ID
}
```

### Roles
| Role | How Assigned | Access |
|------|-------------|--------|
| `admin` | Drupal uid 1 (user `rare`) | Full console, approve stores |
| `store_owner` | Has approved store in Drupal | Own store console, products |
| `creator` | Signed in via X, no store yet | Store creation wizard |

### Route Protection

`frontend/src/middleware.ts` enforces:
- `/console/*` → requires session (any role)
- `/console/stores/[id]` → must own that store or be admin
- `/build` → redirects store_owners to console

## Drupal Basic Auth (Server-to-Server)

### How It Works

Next.js API routes call Drupal JSON:API using Basic Auth:

```typescript
// frontend/src/lib/drupal.ts
export function drupalAuthHeaders(): Record<string, string> {
  const user = process.env.DRUPAL_API_USER;
  const pass = process.env.DRUPAL_API_PASS;
  const encoded = Buffer.from(`${user}:${pass}`).toString("base64");
  return {
    Authorization: `Basic ${encoded}`,
    "Content-Type": "application/vnd.api+json",
    Accept: "application/vnd.api+json",
  };
}
```

### Custom Module
Drupal's JSON:API doesn't support Basic Auth by default. The custom `jsonapi_basic_auth` module at `/opt/drupal/web/modules/custom/jsonapi_basic_auth/` enables it.

### Credentials
- User: `rare` (Drupal admin, uid 1)
- Password: set in `DRUPAL_API_PASS` env var
- Only used server-side (never exposed to browser)

## Auth Flow Diagram

```
User clicks "Sign in with X"
    ↓
NextAuth redirects to X OAuth
    ↓
User authorizes app on X
    ↓
X returns access token + user info
    ↓
NextAuth callback:
  1. Checks if X user has a store in Drupal
  2. Sets role: admin / store_owner / creator
  3. Stores xAccessToken + xId in session
    ↓
User redirected to:
  - /console (if store_owner or admin)
  - /build (if creator with no store)
```

## Key Files

| File | Purpose |
|------|---------|
| `frontend/src/app/api/auth/[...nextauth]/route.ts` | NextAuth config + callbacks |
| `frontend/src/middleware.ts` | Route protection |
| `frontend/src/lib/drupal.ts` | `drupalAuthHeaders()` helper |
| `frontend/src/components/AuthButton.tsx` | Login/logout UI |

## Next Step

→ [Step 8: Drupal ↔ Next.js API](08_API_CONNECTION.md)
