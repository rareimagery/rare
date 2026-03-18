# AI Provider Module — Install Guide

## Prerequisites

- Drupal 10.3 + PostgreSQL on Hostinger VPS (existing)
- Anthropic API key (`sk-ant-...`)
- SSH access to VPS
- Existing `ai_provider` concept from prior spec (this guide replaces the monolithic doc)

## Install Order

Follow these files in sequence. Each file is self-contained — complete one before moving to the next.

| Step | File | What It Does |
|------|------|--------------|
| 1 | `01_ENV_VARS.md` | Add Anthropic credentials and routing config to VPS |
| 2 | `02_MODULE_SCAFFOLD.md` | Create the module directory structure and info/services/routing YAML |
| 3 | `03_PROVIDER_INTERFACE.md` | The `AiProviderInterface` contract and `AiResponse` DTO |
| 4 | `04_GROK_PROVIDER.md` | Wrap existing Grok calls in the new interface |
| 5 | `05_CLAUDE_PROVIDER.md` | Anthropic API integration — the Claude provider class |
| 6 | `06_MANAGER_AND_ROUTING.md` | `AiProviderManager` — dispatch, failover, logging |
| 7 | `07_CONTROLLER_ENDPOINT.md` | JSON:API endpoint that the Next.js frontend hits |
| 8 | `08_VERIFY.md` | Smoke tests and validation checklist |

## Architecture Recap

```
FloatingBuilder.tsx
        │
  POST /api/ai/generate  (Next.js thin proxy)
        │
  Drupal /jsonapi/ai-provider/generate
        │
  AiProviderManager::dispatch()
        │
   ┌────┴────┐
   Grok    Claude
 (primary) (fallback)
```

Next.js never knows which provider responded. Saved builds are provider-agnostic — `metadata.generated_by` tracks it for analytics only.
