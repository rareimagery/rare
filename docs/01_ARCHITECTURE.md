# Step 1: Architecture Overview

## System Diagram

```
                    ┌─────────────────────────────────────┐
                    │           Browser / Client           │
                    └──────────────┬──────────────────────┘
                                   │
                    ┌──────────────▼──────────────────────┐
                    │     Vercel (Next.js 16 Frontend)     │
                    │     rareimagery.net                   │
                    │                                      │
                    │  Pages: /, /stores/[creator],         │
                    │         /build, /console, /products   │
                    │  API Routes: /api/stores/*, /api/chat │
                    └──┬──────┬──────┬──────┬──────┬───────┘
                       │      │      │      │      │
          ┌────────────▼┐  ┌──▼───┐ ┌▼────┐ ┌▼───┐ ┌▼────────┐
          │ Drupal 10.3  │  │Stripe│ │xAI/ │ │X   │ │Anthropic│
          │ JSON:API     │  │  API │ │Grok │ │API │ │Claude   │
          │ 72.62.80.155 │  │      │ │     │ │v2  │ │Haiku    │
          └──────┬───────┘  └──────┘ └─────┘ └────┘ └─────────┘
                 │
          ┌──────▼───────┐
          │ PostgreSQL 16 │
          │ rare-postgres  │
          └───────────────┘
```

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Frontend | Next.js (App Router) | 16.1.6 |
| UI | React + TypeScript | 19.2.3 |
| Styling | Tailwind CSS | 4.x |
| Auth | NextAuth | 4.24.13 |
| Backend | Drupal (headless) | 10.3 |
| Commerce | Drupal Commerce | 3.x |
| Database | PostgreSQL | 16 |
| Payments | Stripe + Stripe Connect | — |
| POD | Printful API | — |
| AI (frontend) | xAI Grok | grok-3 |
| AI (page builder) | Anthropic Claude | haiku-4.5 |
| Hosting (frontend) | Vercel | — |
| Hosting (backend) | Hostinger VPS (Ubuntu 24.04) | — |
| Containers | Docker + Docker Compose | — |

## Repository Structure

Single repo: `github.com/rareimagery/rare.git`

```
c:\rare\
├── frontend/                    # Next.js 16 app (deployed to Vercel)
│   └── src/
│       ├── app/                 # 32 routes (pages + 17 API endpoints)
│       ├── components/          # 25 components (7,953 LOC)
│       └── lib/                 # 9 utility modules
│
├── web/modules/custom/          # Drupal backend (deployed to VPS)
│   ├── rareimagery_xstore/      # Core platform module (286 files)
│   ├── rareimagery_ai/          # AI admin chat (15 files)
│   └── rareimagery_x_import/    # X profile import (25 files)
│
├── web/themes/custom/           # Drupal theme
├── docker/                      # Docker configs (nginx, php, host-nginx)
├── scripts/                     # 23 PHP setup scripts
├── docs/                        # This guide (12 steps)
├── .claude/agents/              # 5 agent definitions
├── docker-compose.yml           # Local dev containers
├── Makefile                     # Dev workflow
├── Dockerfile                   # Drupal image
└── deploy.sh                    # VPS deployment
```

## Codebase Stats

| Area | Files | Lines |
|------|-------|-------|
| Frontend components | 25 | 7,953 |
| Frontend API routes | 17 | 4,102 |
| Frontend lib | 9 | ~1,800 |
| rareimagery_xstore | 286 | 2,191 PHP + 220 YAML |
| rareimagery_ai | 15 | 1,167 PHP |
| rareimagery_x_import | 25 | 606 PHP |
| **Total** | **~420** | **~17,000+** |

## Next Step

→ [Step 2: Environment Setup](02_ENVIRONMENT.md)
