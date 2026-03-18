# Scheduled Task: Midnight Site Audit

Use this file to create the scheduled task in a fresh Claude Code conversation.
Run `/schedule` or ask Claude to create a scheduled task with the details below.

---

## Task Config

| Field | Value |
|---|---|
| **Task ID** | `midnight-site-audit` |
| **Description** | Nightly browser crawl of rareimagery.net — clicks every link, tests key flows, reports broken pages and errors |
| **Schedule** | `0 0 * * *` (every day at midnight, local time) |

---

## Prompt

```
You are a QA agent for the RareImagery Marketplace at https://rareimagery.net. Crawl the entire site, interact with every major link and UI element, and produce a report of what is broken, erroring, or needs updating.

## Setup
Use browser tools (tabs_context_mcp, navigate, read_page, find, computer, read_console_messages, read_network_requests) to inspect the site. Start by calling tabs_context_mcp with createIfEmpty: true to get a tab ID.

## Pages to Test

For each page:
1. Navigate to the URL
2. Take a screenshot
3. Read the accessibility tree to verify key elements load
4. Click every nav link, button, and CTA — note what happens
5. Check console errors: read_console_messages (pattern: "error|Error|warn")
6. Check failed requests: read_network_requests (filter: "failed")

### Page Checklist
- https://rareimagery.net — homepage / marketplace
- https://rareimagery.net/login — verify email login form, X login button, "Sign up" link
- https://rareimagery.net/signup — verify registration form renders with all fields
- https://rareimagery.net/console — should redirect to /login if not authenticated
- https://rareimagery.net/console/setup — store setup / onboarding
- https://rareimagery.net/console/printful — Printful integration page
- https://rareimagery.net/console/products — product management
- https://rareimagery.net/console/orders — orders list
- https://rareimagery.net/console/accounting — accounting dashboard
- https://rareimagery.net/console/settings — settings page
- https://rareimagery.net/console/social — social features
- https://rareimagery.net/console/subscriptions — subscription tiers
- https://rareimagery.net/console/theme — theme selector
- https://rareimagery.net/eula — EULA (verify it loads with content)
- https://rareimagery.net/privacy — privacy policy
- https://rareimagery.net/terms — terms of service
- At least 2 store pages: find creator slugs from the homepage, visit https://rareimagery.net/stores/[creator]

### API Health Checks
Navigate to these and verify JSON response (not a 500 or blank):
- https://rareimagery.net/api/printful/status
- https://rareimagery.net/api/subscriptions/tiers

## What to Flag
- 404 / 500 errors
- Blank or empty pages (loads but shows nothing meaningful)
- Missing expected UI elements (buttons, forms, nav items)
- JS console errors (uncaught exceptions, failed imports)
- Failed API/network requests (4xx or 5xx)
- Broken images
- Redirect loops
- Non-functional forms (inputs missing, submit buttons disabled)
- Placeholder or hardcoded test content

## Output Format

Produce a markdown report:

### ✅ Working
List all pages and features confirmed working.

### ❌ Broken
For each broken item:
- **URL**: full URL
- **Issue**: what is wrong
- **Evidence**: error message, console output, or screenshot description

### ⚠️ Needs Attention
Items that load but look off — stale content, UI glitches, missing copy.

### 🔧 Suggested Fixes
For each broken item, note the likely cause and fix (e.g. missing env var, undeployed route, component not rendering).

Keep it concise — this is a nightly dev standup, not a full QA doc.
```

---

## How to Create This Task

In a **new** Claude Code conversation (not one started by a scheduled task), say:

> "Create a scheduled task called `midnight-site-audit` that runs at midnight every night using the prompt in `C:/rare/MIDNIGHT_SITE_AUDIT_TASK.md`"

Or paste the prompt above directly into the task creation dialog.
