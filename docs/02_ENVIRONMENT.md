# Step 2: Environment Setup

## Prerequisites

- Docker + Docker Compose
- Node.js 22+ and npm
- Git
- SSH access to 72.62.80.155 (for backend deployment)

## Environment Files

Two env files are needed:

### 1. Root `.env` (Backend — Docker + Drupal)

```bash
cp .env.example .env
```

Fill in:
```
# Database
POSTGRES_DB=rare_drupal
POSTGRES_USER=rare_user
POSTGRES_PASSWORD=<strong-password>
POSTGRES_PORT=5432

# Drupal
DRUPAL_PORT=80

# X/Twitter API
XAI_API_KEY=<from console.x.ai>
X_CONSUMER_KEY=<from developer.x.com>
X_CONSUMER_SECRET=<from developer.x.com>
```

### 2. Frontend `.env.local` (Next.js — Vercel)

```bash
cp frontend/.env.example frontend/.env.local
```

Fill in:
```
# Drupal connection
DRUPAL_BASE_URL=http://72.62.80.155
DRUPAL_API_USER=rare
DRUPAL_API_PASS=<drupal-admin-password>

# NextAuth
NEXTAUTH_SECRET=<random-32-char-string>
NEXTAUTH_URL=https://rareimagery.net

# X OAuth (same keys as root .env)
X_CONSUMER_KEY=<from developer.x.com>
X_CONSUMER_SECRET=<from developer.x.com>

# AI
XAI_API_KEY=<from console.x.ai>
ANTHROPIC_API_KEY=<from console.anthropic.com>

# Payments
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Printful
PRINTFUL_API_KEY=<from printful.com/dashboard>
```

## API Keys — Where to Get Them

| Key | Source | Purpose |
|-----|--------|---------|
| `XAI_API_KEY` | [console.x.ai](https://console.x.ai) | Grok AI for profile enhancement |
| `X_CONSUMER_KEY/SECRET` | [developer.x.com](https://developer.x.com) | X OAuth login + API v2 |
| `ANTHROPIC_API_KEY` | [console.anthropic.com](https://console.anthropic.com) | Claude Haiku page builder |
| `STRIPE_*` | [dashboard.stripe.com](https://dashboard.stripe.com) | Payments + Connect |
| `PRINTFUL_API_KEY` | [printful.com/dashboard](https://www.printful.com/dashboard) | Print-on-demand |

## Security Rules

- `.env` and `.env.local` are in `.gitignore` — never commit
- Drupal admin password only in `.env.local`, never in code
- All API keys are server-side only (never exposed to browser)
- Stripe webhook secret validates incoming webhooks

## Verify Setup

```bash
# Check env files exist
ls -la .env frontend/.env.local

# Verify no secrets in git
git status  # Should NOT show .env or .env.local
```

## Next Step

→ [Step 3: Docker & Local Dev](03_DOCKER.md)
