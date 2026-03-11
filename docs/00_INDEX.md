# RareImagery.net — Setup & Operations Guide

Follow these 12 steps in order to understand, set up, and operate the full platform.

| Step | Guide | Agent | Description |
|------|-------|-------|-------------|
| 1 | [Architecture Overview](01_ARCHITECTURE.md) | — | System diagram, tech stack, repo structure |
| 2 | [Environment Setup](02_ENVIRONMENT.md) | — | Prerequisites, env vars, secrets |
| 3 | [Docker & Local Dev](03_DOCKER.md) | Drupal | Containers, Makefile, local workflow |
| 4 | [Drupal Backend](04_DRUPAL.md) | Drupal | Custom modules, entities, config |
| 5 | [Commerce & Payments](05_COMMERCE.md) | Drupal | Product types, orders, Stripe Connect, fees |
| 6 | [Next.js Frontend](06_NEXTJS.md) | Next.js | App Router, pages, components, Tailwind |
| 7 | [Authentication](07_AUTH.md) | Connection | NextAuth, X OAuth, Drupal Basic Auth |
| 8 | [Drupal ↔ Next.js API](08_API_CONNECTION.md) | Connection | JSON:API, API routes, data mapping |
| 9 | [xAI & Grok Import](09_XAI_IMPORT.md) | xAI Import | X profile fetch, Grok AI, data sync |
| 10 | [Store Creation Flow](10_STORE_CREATION.md) | Store Creation | Wizard, approval, provisioning |
| 11 | [Themes & Page Builder](11_THEMES.md) | Next.js | 6 themes, Tailwind Page Builder, Claude AI |
| 12 | [Deployment & Operations](12_DEPLOYMENT.md) | — | Vercel, VPS, deploy scripts, monitoring |

## Agents

5 specialized agents live in `.claude/agents/`. Each guide notes which agent handles that domain.

```
.claude/agents/
├── nextjs.md                  # Frontend: themes, components, pages
├── drupal-nextjs-connection.md # API integration layer
├── drupal.md                  # Backend: modules, entities, config
├── xai-import.md              # X/Twitter data import via Grok
└── store-creation.md          # Store creation wizard flow
```

## Quick Start

```bash
# 1. Clone
git clone https://github.com/rareimagery/rare.git && cd rare

# 2. Environment
cp .env.example .env           # Fill in secrets
cp frontend/.env.example frontend/.env.local

# 3. Backend
make up && make install

# 4. Frontend
cd frontend && npm install && npm run dev
```
