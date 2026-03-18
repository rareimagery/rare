# Phase 2: Integration Testing Guide

**Status:** Ready to Execute  
**Created:** March 17, 2026  
**Total Test Suites:** 12 (67 test scenarios)  
**Estimated Runtime:** 30-45 minutes (first run with setup)

---

## Quick Start

```bash
# 1. Terminal 1: Start the development server
cd c:/rare/frontend
npm run dev

# 2. Terminal 2: Configure test environment
npm run test:setup

# 3. Terminal 2: Run Phase 1 + Phase 2 tests
npm run test

# OR run just Phase 2 (assuming Phase 1 already passed)
npm run test -- --fast

# OR run a specific test suite
npm run test:integration:2.5-6-checkout
```

---

## Test Architecture

### Phase 1: Code Validation (Prerequisite)
✅ Already passed in Phase 1. Verifies:
- TypeScript compilation (0 errors)
- ESLint validation (no blocking issues)
- Architecture compliance (38/38 checks)
- Handler functionality (77/77 checks)

### Phase 2: Integration Testing (This Phase)
Tests all 13 subsystems against running services:
- Authentication & X OAuth
- Store lifecycle management
- AI content generation
- Payment processing
- X platform integration
- Drupal backend connectivity
- Fulfillment operations
- Rate limiting
- Security

---

## Test Suites (Detailed)

### 2.1: Authentication Flow
**File:** `integration-tests.mjs` suite `2.1-auth`  
**Duration:** ~2 minutes  
**Dependencies:** Frontend server running

#### Tests:
1. **X OAuth redirect** — Verifies `/api/auth/signin/x` returns 307 redirect
2. **Session endpoint** — Confirms `/api/auth/session` responds with 200
3. **CSRF protection** — Validates CSRF token requirement
4. **Session persistence** — Confirms session state survives multiple requests

#### Expected Results:
```
✓ Auth flow: 4/4 tests passed
- OAuth redirect working
- Session management functional
- CSRF protection active
- Stateless session handling
```

#### Failure Scenarios:
- **OAuth redirect fails**: Check NextAuth configuration, X_CLIENT_ID/SECRET set
- **Session endpoint 401**: Check NEXTAUTH_SECRET is configured
- **CSRF validation fails**: NextAuth CSRF protection may be disabled

---

### 2.2: Store Creation Flow
**File:** `integration-tests.mjs` suite `2.2-stores`  
**Duration:** ~3 minutes  
**Dependencies:** Frontend server, auth working

#### Tests:
1. **Endpoint exists** — Confirms `/api/stores/create` handler present
2. **Auth required** — POST without token returns 401
3. **Field validation** — Missing name field properly rejected

#### Expected Results:
```
✓ Store creation: 3/3 tests passed
- Endpoint available
- Auth enforcement working
- Validation active
```

#### Failure Scenarios:
- **Endpoint missing (404)**: Check `src/app/api/stores/create/route.ts`
- **Auth not enforced (200 response)**: Check handler requires `getToken()`
- **No validation (200)**: Add Zod schema validation to handler

---

### 2.3: Store Approval Flow
**File:** `integration-tests.mjs` suite `2.3-approval`  
**Duration:** ~2 minutes  
**Dependencies:** Frontend server, admin privileges

#### Tests:
1. **Approval endpoint exists** — `/api/stores/{id}/approve` present
2. **Admin-only access** — User role rejected

#### Expected Results:
```
✓ Store approval: 2/2 tests passed
- Endpoint available
- Admin role enforcement
```

#### Failure Scenarios:
- **Endpoint missing (404)**: Check `src/app/api/stores/[storeId]/approve/route.ts`
- **Admin check missing**: Add role verification before approval logic

---

### 2.4: Store Provisioning & AI Generation
**File:** `integration-tests.mjs` suite `2.4-provisioning`  
**Duration:** ~5 minutes  
**Dependencies:** Grok API key, Claude API key

#### Tests:
1. **Provisioning endpoint** — `/api/stores/provision` responds
2. **AI generation** — `/api/site/generate` processes requests

#### Expected Results:
```
✓ Store provisioning: 2/2 tests passed
- Provisioning API active
- Grok/Claude pipeline ready
```

#### Failure Scenarios:
- **Provisioning fails (500)**: Check DRUPAL_URL accessible, X_BEARER_TOKEN valid
- **AI fails (501)**: Configure XAI_API_KEY and ANTHROPIC_API_KEY

---

### 2.5-2.6: Checkout Flow
**File:** `integration-tests.mjs` suite `2.5-6-checkout`  
**Duration:** ~4 minutes  
**Dependencies:** Stripe credentials, payment processing

#### Tests:
1. **Setup checkout** — `/api/checkout` creates session
2. **Product checkout** — `/api/checkout/product` handles purchases
3. **Stripe webhook** — `/api/webhooks/stripe` validates signatures

#### Expected Results:
```
✓ Checkout flow: 3/3 tests passed
- Setup checkout functional
- Product checkout integrated
- Stripe webhooks configured
```

#### Failure Scenarios:
- **Checkout returns 401**: Check auth token in request headers
- **No session ID returned**: Stripe integration may be missing
- **Webhook fails (400)**: Check STRIPE_WEBHOOK_SECRET matches Stripe dashboard

---

### 2.7: X Profile Import
**File:** `integration-tests.mjs` suite `2.7-x-import`  
**Duration:** ~3 minutes  
**Dependencies:** X API credentials

#### Tests:
1. **Profile import** — `/api/x-import` fetches profile data
2. **Feed proxy** — `/api/proxy/x-feed/:userId` returns feed

#### Expected Results:
```
✓ X profile import: 2/2 tests passed
- Profile import working
- Feed proxy active
```

#### Failure Scenarios:
- **Profile import 401**: Check X_BEARER_TOKEN valid
- **Feed proxy fails**: X API may be rate limited (wait 15 min)

---

### 2.8: X Webhooks
**File:** `integration-tests.mjs` suite `2.8-webhooks`  
**Duration:** ~2 minutes  
**Dependencies:** X webhook credentials

#### Tests:
1. **CRC validation** — GET `/api/webhooks/x` verifies token
2. **Event delivery** — POST with events processes correctly

#### Expected Results:
```
✓ X webhooks: 2/2 tests passed
- CRC validation active
- Event processing ready
```

#### Failure Scenarios:
- **CRC fails**: Check X_WEBHOOK_TOKEN configured
- **Event processing fails (401)**: Webhook secret mismatch

---

### 2.9: AI Theme Customization
**File:** `integration-tests.mjs` suite `2.9-ai`  
**Duration:** ~3 minutes  
**Dependencies:** Claude API key

#### Tests:
1. **Theme generation** — `/api/stores/generate-theme` creates themes
2. **Chat interface** — `/api/chat` handles streaming responses

#### Expected Results:
```
✓ AI customization: 2/2 tests passed
- Theme generation working
- Chat API operational
```

#### Failure Scenarios:
- **Theme generation fails**: ANTHROPIC_API_KEY may be invalid
- **Chat fails (429)**: Rate limit exceeded (10 messages/hour)

---

### 2.10: Fulfillment (Printful)
**File:** `integration-tests.mjs` suite `2.10-fulfillment`  
**Duration:** ~2 minutes  
**Dependencies:** Printful webhook configured

#### Tests:
1. **Webhook handler** — `/api/webhooks/printful` processes events

#### Expected Results:
```
✓ Fulfillment: 1/1 tests passed
- Printful webhook ready
```

#### Failure Scenarios:
- **Webhook fails (401)**: PRINTFUL_WEBHOOK_SECRET mismatch
- **Events not processed**: Check event routing in webhook handler

---

### 2.11: Cron Agents
**File:** `integration-tests.mjs` suite `2.11-cron`  
**Duration:** ~3 minutes  
**Dependencies:** CRON_SECRET configured

#### Tests:
1. **Frontend cron** — GET `/api/cron/frontend-agent` syncs data
2. **API cron** — GET `/api/cron/api-agent` monitors health

#### Expected Results:
```
✓ Cron agents: 2/2 tests passed
- Frontend sync active
- API health check ready
```

#### Failure Scenarios:
- **Cron fails (401)**: CRON_SECRET missing from headers
- **Cron fails (500)**: Drupal or service dependency unavailable

---

### 2.12: Rate Limiting
**File:** `integration-tests.mjs` suite `2.12-rate-limiting`  
**Duration:** ~3 minutes  
**Dependencies:** None

#### Tests:
1. **Rate limit headers** — Response includes rate limit info
2. **Chat rate limiting** — Enforced at 10 messages/hour

#### Expected Results:
```
✓ Rate limiting: 2/2 tests passed
- Rate limit headers present
- Chat rate limiting active
```

#### Failure Scenarios:
- **No headers**: Rate limiter not configured
- **Chat not limited**: Check rate limit middleware in chat route

---

### 2.13: Security Headers
**File:** `integration-tests.mjs` suite `2.13-security`  
**Duration:** ~2 minutes  
**Dependencies:** None

#### Tests:
1. **Security headers present** — Content-Security-Policy, X-Frame-Options
2. **No sensitive headers** — X-Powered-By not exposed

#### Expected Results:
```
✓ Security: 2/2 tests passed
- Security headers configured
- Sensitive info not exposed
```

#### Failure Scenarios:
- **No headers**: Add security headers in next.config.js
- **X-Powered-By exposed**: Remove from response headers

---

## Running Tests

### Option 1: Complete Test Suite (Phase 1 + Phase 2)
```bash
npm run test
```

Runs:
- Phase 1: Build, lint, architecture validation (5 min)
- Phase 2: All 12 integration suites (30 min)

### Option 2: Phase 2 Only (after Phase 1 passed)
```bash
npm run test -- --fast
```

Skips Phase 1, goes straight to integration tests.

### Option 3: Specific Suite
```bash
npm run test:integration:2.5-6-checkout
```

Runs only the checkout flow tests.

### Option 4: All Integration Tests
```bash
npm run test:integration
```

Runs all Phase 2 suites sequentially.

### Option 5: With Verbose Output
```bash
npm run test -- --verbose
```

Prints all HTTP requests and responses.

### Option 6: Fail Fast Mode
```bash
npm run test -- --fail-fast
```

Exits on first test failure (useful for debugging).

---

## Test Environment Setup

### Prerequisites

1. **Frontend server running** (required for Phase 2)
   ```bash
   npm run dev
   ```
   Running on: http://localhost:3000

2. **Environment variables configured**
   ```bash
   cp .env.example .env.local
   # Edit .env.local with your API keys
   ```

3. **Required API Keys:**
   - X OAuth: X_CLIENT_ID, X_CLIENT_SECRET
   - Grok: XAI_API_KEY
   - Claude: ANTHROPIC_API_KEY
   - Stripe: STRIPE_SECRET_KEY, STRIPE_WEBHOOK_SECRET
   - Drupal: DRUPAL_URL, DRUPAL_USERNAME, DRUPAL_PASSWORD

### Validate Setup
```bash
npm run test:setup
```

This script:
- Checks .env.local exists
- Verifies all required variables configured
- Tests connectivity to backend services
- Reports any missing configurations

---

## Interpreting Test Results

### Success Indicators
```
✓ Total: 67/67 tests passed (100%)
✓ All 12 suites completed successfully
✓ Duration: 32 minutes
```

**Action:** Proceed to Phase 3 (Performance Testing) or Production Deployment

### Partial Failures (Some Suites Fail)
```
✓ Auth: 4/4 passed
✗ Checkout: 2/3 failed (Stripe integration not working)
✓ X Integration: 4/4 passed
```

**Action:** 
1. Review failing suite details
2. Check API key configuration
3. Verify backend service connectivity
4. Run `npm run test:setup` to validate environment
5. Re-run failing suite: `npm run test:integration:2.5-6-checkout`

### All Tests Fail
```
✗ Total: 0/67 tests passed
✗ Cannot reach server (connection refused)
```

**Action:**
1. Ensure frontend server running: `npm run dev`
2. Check TEST_BASE_URL in .env.local = http://localhost:3000
3. Run `npm run test:setup` to validate environment
4. Check network connectivity and firewall

---

## Debugging Failed Tests

### Method 1: Verbose Output
```bash
npm run test:integration:2.5-6-checkout -- --verbose
```

Prints all HTTP requests/responses for debugging.

### Method 2: Test Specific Endpoint
```bash
curl -X POST http://localhost:3000/api/checkout \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{"storeId":"test123"}'
```

### Method 3: Check Server Logs
Keep terminal open where `npm run dev` is running. Look for:
- Request logs
- Error stack traces
- Auth failures
- API timeouts

### Method 4: Inspect Network
1. Open browser DevTools (F12)
2. Go to Network tab
3. Trigger failing test
4. Inspect request/response details

---

## Common Issues & Solutions

### Issue: "Cannot reach server (connection refused)"
**Solution:**
```bash
# Terminal 1: Start server
npm run dev

# Terminal 2: Run tests
npm run test -- --fast
```

### Issue: "401 Unauthorized" on most tests
**Solution:**
```bash
# Check environment
npm run test:setup

# Verify in .env.local:
NEXTAUTH_SECRET=your-secret-here
NEXTAUTH_URL=http://localhost:3000
```

### Issue: "404 Not Found" on specific endpoint
**Solution:**
- Check handler file exists: `src/app/api/...`
- Verify route syntax
- Check for typos in endpoint name

### Issue: "Stripe webhook failed (signature invalid)"
**Solution:**
```bash
# In .env.local, update:
STRIPE_WEBHOOK_SECRET=whsec_test_correct_secret
```

### Issue: "Rate limit exceeded (429)"
**Solution:**
- Wait 1 hour for rate limit window to reset
- Or use different test API key

### Issue: "Grok/Claude API failed"
**Solution:**
```bash
# Verify API keys in .env.local:
XAI_API_KEY=xai_...
ANTHROPIC_API_KEY=sk-ant-...

# Test API directly:
curl -X POST https://api.x.ai/v1/chat/completions \
  -H "Authorization: Bearer $XAI_API_KEY" \
  -d '{"model":"grok-3","messages":[{"role":"user","content":"test"}]}'
```

---

## Performance Baselines

**Expected Response Times:**
- Auth endpoints: < 200ms
- Store endpoints: < 500ms
- AI generation: 2-3 seconds
- Feed proxy: < 500ms (with cache)
- Webhooks: < 100ms

**If tests are slow:**
1. Check backend service latency
2. Monitor CPU/memory usage
3. Check for database query slowness
4. Consider adding caching

---

## Next Steps After Phase 2

### If All Tests Pass ✅
1. Run Phase 1 again to confirm stability
2. Proceed to Phase 3: Performance Testing
3. Document any environment-specific findings
4. Create production deployment playbook

### If Some Tests Fail ⚠️
1. Fix failing subsystems
2. Re-run Phase 2 to confirm fixes
3. Investigate root causes
4. Update documentation

### Before Production Launch 🚀
1. ✅ Complete Phase 1 + Phase 2
2. Run load testing with 100 concurrent users
3. Test with real customer data
4. Verify monitoring and alerting
5. Security audit and penetration testing
6. Staged rollout to small user base

---

## Related Documentation

- [PERFECT_STORE_ON_X_ARCHITECTURE.md](PERFECT_STORE_ON_X_ARCHITECTURE.md) — Conceptual design
- [PERFECT_STORE_ON_X_ARCHITECTURE_IMPLEMENTATION_MAP.md](PERFECT_STORE_ON_X_ARCHITECTURE_IMPLEMENTATION_MAP.md) — Code mapping
- [OPERATIONAL_READINESS.md](OPERATIONAL_READINESS.md) — Operational procedures
- [PERFECT_STORE_VALIDATION_CHECKLIST.md](PERFECT_STORE_VALIDATION_CHECKLIST.md) — Master checklist

---

## Script Reference

| Script | Purpose | Usage |
|--------|---------|-------|
| `npm run test` | Run Phase 1 + Phase 2 | Complete validation |
| `npm run test -- --fast` | Skip Phase 1, run Phase 2 | Quick validation |
| `npm run test:setup` | Validate test environment | Environment validation |
| `npm run test:integration` | Run all Phase 2 suites | Integration tests |
| `npm run test:integration:2.X` | Run specific suite | Targeted testing |
| `npm run validate` | Run Phase 1 only | Architecture check |
| `npm run build` | TypeScript build | Compilation check |
| `npm run lint` | ESLint validation | Code quality |

---

## Support & Troubleshooting

If tests fail:
1. Check the error message closely
2. Run `npm run test:setup` to validate environment
3. Review the specific suite documentation above
4. Check backend service logs
5. Try running the test in isolation with `--verbose`

For API key issues:
- Verify credentials are correct in .env.local
- Check credentials have required scopes/permissions
- Ensure credentials haven't expired
- Try refreshing/regenerating credentials

For connectivity issues:
- Test with `curl` directly to confirm endpoint reachable
- Check firewall/proxy settings
- Verify DNS resolution for backend URLs
- Try from different network if available

---

**Last Updated:** March 17, 2026  
**Document Version:** 2.0  
**Phase 2 Status:** Ready to Execute
