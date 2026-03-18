# AI Provider Module — Install Guide

## What You're Getting

This folder contains a complete Drupal custom module (`ai_provider/`) plus a
Next.js API route (`nextjs-route/`). All files are ready to drop in — no
code to copy from markdown blocks.

## File Tree

```
ai_provider/                          ← COPY THIS FOLDER TO DRUPAL
├── ai_provider.info.yml              Module definition
├── ai_provider.routing.yml           POST endpoint registration
├── ai_provider.services.yml          Dependency injection wiring
└── src/
    ├── AiProviderManager.php         Dispatch + failover orchestrator
    ├── Controller/
    │   └── AiGenerateController.php  /jsonapi/ai-provider/generate endpoint
    ├── Exception/
    │   ├── AiGenerationException.php
    │   └── ProviderUnavailableException.php
    ├── Model/
    │   └── AiResponse.php            Normalized response DTO
    └── Provider/
        ├── AiProviderInterface.php   Contract (all providers implement this)
        ├── ClaudeProvider.php        Anthropic Sonnet 4.6 integration (legacy, Grok preferred)
        └── GrokProvider.php          xAI Grok integration

nextjs-route/                         ← COPY THE route.ts INTO YOUR NEXT.JS APP
└── app/api/ai/generate/route.ts      Thin proxy to Drupal
```


## Step 1 — Environment Variables

SSH into your VPS. Add to your `.env` (same file with your Grok/Stripe keys):

    XAI_API_KEY=your-key-from-console.x.ai
    GROK_MODEL=grok-3-mini
    AI_PROVIDER_DEFAULT_PRIMARY=grok
    AI_PROVIDER_DEFAULT_FALLBACK=grok
    AI_PROVIDER_PAGE_BUILDER_PRIMARY=grok

Restart PHP-FPM after:

    sudo systemctl restart php-fpm


## Step 2 — Copy Drupal Module

Copy the `ai_provider/` folder (NOT `nextjs-route/`) into your Drupal
custom modules directory:

    cp -r ai_provider/ /path/to/drupal/modules/custom/ai_provider/

Verify the structure:

    find /path/to/drupal/modules/custom/ai_provider -type f | sort

You should see all 11 files listed above.


## Step 3 — Enable Module

    cd /path/to/drupal
    composer dump-autoload
    drush en ai_provider -y
    drush cr

If you get class-not-found errors, run `composer dump-autoload` again
and `drush cr`.

Verify:

    drush pm:list --status=enabled | grep ai_provider
    drush route:list | grep ai-provider

The route should show:
    ai_provider.generate  POST  /jsonapi/ai-provider/generate


## Step 4 — Copy Next.js Route

Copy the API route into your Next.js app:

    cp nextjs-route/app/api/ai/generate/route.ts \
       /path/to/nextjs/app/api/ai/generate/route.ts

Make sure DRUPAL_BASE_URL and DRUPAL_API_TOKEN are set in your
Next.js `.env.local`.


## Step 5 — Test xAI/Grok API Key

From VPS, confirm the key works:

    curl -s https://api.x.ai/v1/chat/completions \
      -H "Authorization: Bearer $XAI_API_KEY" \
      -H "content-type: application/json" \
      -d '{
        "model": "grok-3-mini",
        "max_tokens": 256,
        "messages": [{"role": "user", "content": "Reply with: {\\"status\\": \\"ok\\"}"}]
      }'

Should return JSON with choices[0].message.content containing {"status": "ok"}.


## Step 6 — Test Drupal Endpoint

    curl -s -X POST http://localhost/jsonapi/ai-provider/generate \
      -H "Content-Type: application/json" \
      -d '{
        "task_type": "page_builder",
        "prompt_data": {
          "user_input": "Create a simple hero section",
          "subculture": "emo"
        }
      }'

Expected: JSON with "status": "ok" and "provider": "grok".


## Step 7 — Test Failover

Temporarily set XAI_API_KEY to garbage, restart PHP-FPM, repeat the
curl from Step 6. Response should show "provider": "claude".

Check logs:

    drush watchdog:show --type=ai_provider --count=5

Should show a warning about Grok failing and Claude taking over.

Restore your real XAI_API_KEY when done.


## Step 8 — Test End-to-End from Next.js

    curl -s -X POST http://localhost:3000/api/ai/generate \
      -H "Content-Type: application/json" \
      -d '{
        "task_type": "page_builder",
        "prompt_data": {
          "user_input": "Create a product grid",
          "subculture": "scene_kid"
        }
      }'

Full chain: Next.js -> Drupal -> Grok (or Claude fallback) -> Response.


## What to Port

GrokProvider.php has TODO comments in four prompt methods. These are
stubs — port your existing Grok system prompts into them:

  - getPageBuilderPrompt()      ← your current page builder prompt
  - getThemeGenerationPrompt()  ← from MYSPACE_THEME_BOT_RULES.md
  - getProfileImportPrompt()    ← your X import prompt
  - getContentGenerationPrompt() ← new or existing

The stubs work as-is for testing. Replace them with your real
prompts before going to production.


## Promoting Claude to Primary

When ready to make Claude the primary for page builder:

    AI_PROVIDER_PAGE_BUILDER_PRIMARY=claude

Restart PHP-FPM. No code change. Grok becomes the fallback automatically.
