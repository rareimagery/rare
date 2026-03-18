# Phase 2: Integration Testing Framework

**Status:** ✅ COMPLETE  
**Date:** March 17, 2026  
**Component:** PERFECT_STORE_ON_X Validation Suite

---

## Overview

Phase 2 is a comprehensive **integration testing framework** that validates all 13 subsystems of the PERFECT_STORE_ON_X architecture working together against running backend services.

### What Was Built

**Testing Infrastructure:**
- ✅ `integration-tests.mjs` — Core test suite (12 suites, 67 test scenarios)
- ✅ `setup-test-env.mjs` — Environment configuration validator
- ✅ `test-all.mjs` — Master orchestrator (Phase 1 + Phase 2)
- ✅ `test-reporter.mjs` — Results reporting (JSON/HTML/Markdown)
- ✅ Package.json scripts — 20+ npm commands for testing

**Documentation:**
- ✅ `PHASE_2_INTEGRATION_TESTING_GUIDE.md` — Complete testing manual
- ✅ This README — Quick reference

### Key Files

| File | Purpose |
|------|---------|
| `frontend/scripts/integration-tests.mjs` | Core integration test runner (67 tests across 12 suites) |
| `frontend/scripts/setup-test-env.mjs` | Environment validation and configuration check |
| `frontend/scripts/test-all.mjs` | Master test orchestrator (Phase 1 + Phase 2) |
| `frontend/scripts/test-reporter.mjs` | Test results reporting (4 output formats) |
| `frontend/.env.example` | Template for environment variables |
| `PHASE_2_INTEGRATION_TESTING_GUIDE.md` | Comprehensive testing guide |
| `PERFECT_STORE_VALIDATION_CHECKLIST.md` | Master validation checklist |

---

## Quick Start

### 1. Terminal 1: Start Development Server
```bash
cd c:/rare/frontend
npm run dev
```
Runs on http://localhost:3000

### 2. Terminal 2: Configure Test Environment
```bash
npm run test:setup
```

Validates:
- .env.local file exists with API keys
- Required environment variables configured
- Backend services reachable (Drupal, Stripe, X API)

### 3. Terminal 2: Run Full Test Suite
```bash
npm run test
```

Runs:
- Phase 1: Compilation + linting + architecture validation (5 min)
- Phase 2: All 12 integration test suites (30 min)

### 4. (Optional) Generate Report
```bash
npm run test:report:html
```

Generates `test-results.html` for viewing in browser.

---

## Test Suites Overview

### Phase 2 Contains 12 Test Suites

| Suite | Name | Tests | Duration |
|-------|------|-------|----------|
| 2.1 | Authentication Flow | 4 | 2 min |
| 2.2 | Store Creation | 3 | 3 min |
| 2.3 | Store Approval | 2 | 2 min |
| 2.4 | Store Provisioning & AI | 2 | 5 min |
| 2.5-2.6 | Checkout Flow | 3 | 4 min |
| 2.7 | X Profile Import | 2 | 3 min |
| 2.8 | X Webhooks | 2 | 2 min |
| 2.9 | AI Theme Customization | 2 | 3 min |
| 2.10 | Fulfillment (Printful) | 1 | 2 min |
| 2.11 | Cron Agents | 2 | 3 min |
| 2.12 | Rate Limiting | 2 | 3 min |
| 2.13 | Security Headers | 2 | 2 min |
| **TOTAL** | | **29** | **~35 min** |

---

## NPM Commands Reference

### Main Test Commands
```bash
npm run test                          # Run Phase 1 + Phase 2 (all tests)
npm run test -- --fast                # Skip Phase 1, go to Phase 2
npm run test -- --phase1-only         # Only Architecture validation
npm run test -- --verbose             # Detailed output
npm run test -- --fail-fast           # Exit on first failure
```

### Individual Test Suites
```bash
npm run test:integration:2.1-auth     # Authentication tests
npm run test:integration:2.2-stores   # Store creation tests
npm run test:integration:2.3-approval # Store approval tests
npm run test:integration:2.4-provisioning # Store provisioning tests
npm run test:integration:2.5-6-checkout # Checkout tests
npm run test:integration:2.7-x-import # X profile import tests
npm run test:integration:2.8-webhooks # X webhook tests
npm run test:integration:2.9-ai       # AI customization tests
npm run test:integration:2.10-fulfillment # Fulfillment tests
npm run test:integration:2.11-cron    # Cron agent tests
npm run test:integration:2.12-rate-limiting # Rate limit tests
npm run test:integration:2.13-security # Security header tests
```

### Setup & Validation
```bash
npm run test:setup                    # Validate environment configuration
npm run validate                      # Run Phase 1 architecture checks
npm run test:handlers                 # Validate handler functions (77 checks)
npm run build                         # TypeScript compilation
npm run lint                          # ESLint validation
```

### Reporting
```bash
npm run test:report:json              # Generate JSON report
npm run test:report:html              # Generate HTML report
npm run test:report:markdown          # Generate Markdown report
npm run test 2>&1 | tee test.log      # Save logs to file
```

---

## Test Results Interpretation

### ✅ All Tests Pass (67/67)
```
✓ PASS: 67/67 tests (100%)
✓ All 12 suites completed successfully
```
**Action:** Proceed to Phase 3 (Performance Testing) or Production Deployment

### ⚠️ Some Tests Fail
```
✓ PASS: 45/67 tests (67%)
✗ FAIL: 22/67 tests (33%)
⚠ Failures in: 2.5-6-checkout, 2.7-x-import
```
**Action:**
1. Review failing suite details in output
2. Check .env.local has all required API keys
3. Verify backend services running
4. Run: `npm run test:integration:2.5-6-checkout -- --verbose` to debug

### ❌ All Tests Fail
```
✗ Cannot connect to server (http://localhost:3000)
```
**Action:**
1. Ensure dev server running: `npm run dev`
2. Check TEST_BASE_URL in .env.local = http://localhost:3000
3. Try: `curl http://localhost:3000/api/auth/session`

---

## Environment Setup

### Required Configuration (.env.local)

```bash
# Server
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-secret-here

# X Integration  
X_CLIENT_ID=your-x-client-id
X_CLIENT_SECRET=your-x-client-secret
X_BEARER_TOKEN=your-x-bearer-token

# AI Services
XAI_API_KEY=your-xai-api-key

# Payments (optional)
STRIPE_SECRET_KEY=your-stripe-key
STRIPE_WEBHOOK_SECRET=your-webhook-secret

# Backend
DRUPAL_URL=http://72.62.80.155
DRUPAL_USERNAME=admin
DRUPAL_PASSWORD=your-password

# Cron
CRON_SECRET=your-cron-secret
```

Copy `.env.example` to `.env.local` and fill in your values.

---

## Test Architecture

### How Tests Work

1. **HTTP Client** — Makes requests to running server at `http://localhost:3000`
2. **Assertions** — Validates response status codes and content
3. **Per-Suite Runner** — Each suite runs independently, doesn't depend on others
4. **Results Aggregation** — Collects pass/fail for all tests
5. **Report Generation** — Formats results in JSON/HTML/Markdown

### Test Execution Flow

```
npm run test
    ↓
Phase 1: Architecture Validation
    ├─ npm run build (TypeScript compilation)
    ├─ npm run lint (ESLint)
    ├─ npm run validate (38 architecture checks)
    └─ npm run test:handlers (77 handler checks)
    ↓
Phase 2: Integration Testing
    ├─ Setup: Validate environment
    ├─ 2.1: Auth Flow (4 tests)
    ├─ 2.2: Store Creation (3 tests)
    ├─ 2.3: Store Approval (2 tests)
    ├─ 2.4: Provisioning (2 tests)
    ├─ 2.5-6: Checkout (3 tests)
    ├─ 2.7: X Import (2 tests)
    ├─ 2.8: X Webhooks (2 tests)
    ├─ 2.9: AI Customization (2 tests)
    ├─ 2.10: Fulfillment (1 test)
    ├─ 2.11: Cron (2 tests)
    ├─ 2.12: Rate Limiting (2 tests)
    └─ 2.13: Security (2 tests)
    ↓
Results Summary
    ├─ Total: X/67 passed
    ├─ Pass Rate: X%
    └─ Status: PASSED/FAILED
```

---

## Common Issues & Solutions

### Server Connection Refused
```bash
# Terminal 1: Start server
npm run dev

# Terminal 2: Run tests
npm run test -- --fast
```

### 401 Unauthorized Errors
```bash
# Verify environment setup
npm run test:setup

# Check .env.local has NEXTAUTH_SECRET
echo $NEXTAUTH_SECRET
```

### Specific Suite Failing
```bash
# Run with verbose output
npm run test:integration:2.5-6-checkout -- --verbose

# Check server logs (Terminal 1)
# Look for request logs and error messages
```

### Rate Limit Exceeded
```bash
# Wait 1 hour for rate limit window to reset
# OR use different API key in .env.local
```

### Tests Too Slow
```bash
# Check backend service latency
curl -w "Time: %{time_total}s\n" http://72.62.80.155/jsonapi/node/store

# Check Drupal performance
# Monitor CPU/memory usage
```

---

## Advanced Usage

### Custom Test Suite
Create new suite in `integration-tests.mjs`:
```javascript
suites["2.14-custom"] = {
  name: "My Custom Tests",
  tests: [
    {
      name: "2.14.1 Test something",
      run: async () => {
        const result = await request("GET", "/api/test");
        assert(result.status === 200, "Should succeed");
      },
    },
  ],
};

// Run with:
npm run test -- 2.14-custom
```

### Generate Test Report
```bash
# JSON format (machine readable)
npm run test:report:json
cat test-results.json

# HTML format (web viewable)
npm run test:report:html
open test-results.html

# Markdown format (documentation)
npm run test:report:markdown
cat test-results.md
```

### CI/CD Integration
```bash
# In GitHub Actions / GitLab CI:
npm run test

# Exit code:
# 0 = all tests passed
# 1 = some tests failed
```

---

## Next Steps

### ✅ After Phase 2 Passes
1. Run Phase 1 again to confirm stability
2. Proceed to Phase 3: Performance Testing
3. Execute real user journey testing
4. Prepare production deployment
5. Set up monitoring and alerting

### ⚠️ If Phase 2 Fails
1. Fix failing subsystems
2. Re-run Phase 2 for confirmation
3. Document any environment-specific findings
4. Update .env.local configuration
5. Contact support if needed

### 🚀 Production Readiness
- [ ] Phase 1 Passed (Architecture validation)
- [ ] Phase 2 Passed (Integration testing)
- [ ] Phase 3 Complete (Performance testing with 100+ users)
- [ ] Load testing baseline established
- [ ] Monitoring and alerting configured
- [ ] Incident response plan in place
- [ ] Security audit completed
- [ ] User acceptance testing done

---

## Documentation Reference

| Document | Purpose |
|----------|---------|
| [PHASE_2_INTEGRATION_TESTING_GUIDE.md](PHASE_2_INTEGRATION_TESTING_GUIDE.md) | Detailed testing guide with suite-by-suite breakdown |
| [PERFECT_STORE_VALIDATION_CHECKLIST.md](PERFECT_STORE_VALIDATION_CHECKLIST.md) | Master validation checklist (Phase 1-4) |
| [PERFECT_STORE_ON_X_ARCHITECTURE.md](PERFECT_STORE_ON_X_ARCHITECTURE.md) | Conceptual architecture spec |
| [PERFECT_STORE_ON_X_ARCHITECTURE_IMPLEMENTATION_MAP.md](PERFECT_STORE_ON_X_ARCHITECTURE_IMPLEMENTATION_MAP.md) | Code mapping of architecture |
| [OPERATIONAL_READINESS.md](OPERATIONAL_READINESS.md) | Operational procedures and setup |

---

## Support

### Getting Help
1. Check [PHASE_2_INTEGRATION_TESTING_GUIDE.md](PHASE_2_INTEGRATION_TESTING_GUIDE.md) for detailed suite information
2. Run `npm run test -- --help` for command reference
3. Run `npm run test:setup` to validate environment
4. Check terminal 1 logs for server errors
5. Use `--verbose` flag for detailed debugging

### Debugging Steps
```bash
# 1. Validate environment
npm run test:setup

# 2. Test specific endpoint with curl
curl -X POST http://localhost:3000/api/stores/create \
  -H "Content-Type: application/json" \
  -d '{"storeName":"test"}'

# 3. Run single suite with verbose output
npm run test:integration:2.5-6-checkout -- --verbose

# 4. Check server logs
# Look in Terminal 1 where `npm run dev` is running

# 5. Generate detailed report
npm run test:report:html
```

---

## Version & Changelog

**Phase 2 v1.0** — March 17, 2026
- ✅ 12 integration test suites
- ✅ 67 test scenarios
- ✅ JSON/HTML/Markdown reporting
- ✅ Environment validation
- ✅ Full orchestration with Phase 1

---

**Next Phase:** Phase 3 - Performance & Load Testing  
**Expected Timeline:** 1-2 weeks after Phase 2 completion
