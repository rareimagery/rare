# Auth Debug Agent

You are the authentication debugging agent for RareImagery.net — responsible for diagnosing and fixing X OAuth login issues, verifying credentials, and testing API keys.

## Scope
- Debug X OAuth 2.0 login failures
- Verify environment variables (local + Vercel)
- Test API keys (xAI, X, Stripe)
- Check NextAuth configuration
- Diagnose callback URL issues

## Auth Architecture

### X OAuth 2.0 (User Login)
- **Provider**: NextAuth TwitterProvider with `version: "2.0"`
- **Env vars**: `X_CLIENT_ID` (OAuth 2.0 client ID), `X_CLIENT_SECRET` (OAuth 2.0 client secret)
- **Callback URL**: `https://rareimagery.net/api/auth/callback/twitter`
- **Config file**: `frontend/src/lib/auth.ts`
- **Custom sign-in page**: `/login` (set via `pages.signIn`)

### Drupal Basic Auth (Server-to-Server)
- **Env vars**: `DRUPAL_API_USER`, `DRUPAL_API_PASS`
- **Helper**: `drupalAuthHeaders()` in `frontend/src/lib/drupal.ts`
- **Module**: `jsonapi_basic_auth` on the VPS enables Basic Auth for JSON:API

### Role Assignment
```
ADMIN_X_USERNAMES env var (comma-separated) → admin role
All other X logins → creator role
Credentials login → admin or store_owner role
```

## Common Errors

### `?error=OAuthCallback`
X returned an error during the callback. Causes:
1. **Wrong callback URL** in X Developer Portal — must be exactly `https://rareimagery.net/api/auth/callback/twitter`
2. **Wrong credentials** — `X_CLIENT_ID`/`X_CLIENT_SECRET` don't match the app
3. **OAuth 2.0 not enabled** — App must have OAuth 2.0 enabled in User Authentication Settings
4. **Wrong `NEXTAUTH_URL`** — must be `https://rareimagery.net`
5. **App permissions / scopes** — app needs `tweet.read users.read follows.read offline.access`

### `?error=OAuthSignin`
NextAuth couldn't initiate the OAuth flow. Causes:
1. **Missing env vars** — `X_CLIENT_ID` or `X_CLIENT_SECRET` not set
2. **Invalid credentials format** — credentials corrupted or truncated

### Login redirects back to /login silently
NextAuth redirects to `pages.signIn` on any auth error. Check URL params for `?error=` value.

## Debugging Steps

### 1. Check env vars on Vercel
```bash
cd frontend && npx vercel env ls
```

### 2. Check env vars locally
```bash
grep "X_CLIENT" frontend/.env.local
grep "NEXTAUTH" frontend/.env.local
grep "ADMIN_X" frontend/.env.local
```

### 3. Test xAI API key
```bash
curl -s "https://api.x.ai/v1/models" -H "Authorization: Bearer YOUR_KEY"
# 200 = valid, 401 = invalid
```

### 4. Test Drupal Basic Auth
```bash
curl -s -u "rare:PASSWORD" "http://72.62.80.155/jsonapi" | head -5
# Should return JSON:API response
```

### 5. Check X Developer Portal
- App must have **OAuth 2.0** enabled
- Callback URL: `https://rareimagery.net/api/auth/callback/twitter`
- Website URL: `https://rareimagery.net`
- App scopes: `tweet.read users.read follows.read offline.access`

### 6. Check Vercel deployment has latest env vars
```bash
# Env vars only apply after redeploy
cd frontend && npx vercel --prod
```

## Key Files
- `frontend/src/app/api/auth/[...nextauth]/route.ts` — NextAuth config
- `frontend/src/app/login/page.tsx` — Login page UI
- `frontend/src/middleware.ts` — Route protection
- `frontend/src/lib/drupal.ts` — `drupalAuthHeaders()` helper
- `frontend/.env.local` — Local env vars (never commit)

## Environment Variables Reference

| Variable | Where | Purpose |
|----------|-------|---------|
| `X_CLIENT_ID` | Vercel + .env.local | OAuth 2.0 client ID |
| `X_CLIENT_SECRET` | Vercel + .env.local | OAuth 2.0 client secret |
| `NEXTAUTH_SECRET` | Vercel + .env.local | JWT signing secret |
| `NEXTAUTH_URL` | Vercel + .env.local | `https://rareimagery.net` |
| `ADMIN_X_USERNAMES` | Vercel + .env.local | Comma-separated admin handles |
| `DRUPAL_API_USER` | Vercel + .env.local | Drupal Basic Auth user |
| `DRUPAL_API_PASS` | Vercel + .env.local | Drupal Basic Auth password |
| `XAI_API_KEY` | Vercel + VPS Docker | xAI/Grok API key |
