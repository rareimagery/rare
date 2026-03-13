# RareImagery — Cost to Run

## Fixed Monthly Infrastructure

| Service | Plan | Cost/mo | Notes |
|---|---|---|---|
| Hostinger VPS | KVM2 (2 vCPU, 8GB RAM, 100GB NVMe) | ~$7.00 | Drupal + PostgreSQL backend. Upgrade to KVM4 (~$14/mo) at ~100 creators |
| Vercel | Pro (1 seat) | $20.00 | Next.js frontend, 1TB bandwidth included |
| Cloudflare | Free | $0.00 | DNS + wildcard subdomain routing |
| Domain (rareimagery.net) | Annual ~$15 | ~$1.25 | Amortized monthly |
| **Total fixed** | | **~$28.25/mo** | |

---

## Variable Costs Per Creator

### Stripe Fees
Stripe charges **2.9% + $0.30 per transaction** — the flat $0.30 is the problem at $2/mo.

| Charge | Gross | Stripe fee | Net to you |
|---|---|---|---|
| $5 setup (first invoice) | $5.00 | $0.445 | $4.56 |
| $2 monthly | $2.00 | $0.358 | $1.64 |
| **First month total** | **$7.00** | **~$0.80** | **~$6.20** |
| **Each month after** | **$2.00** | **~$0.36** | **~$1.64** |

> The $0.30 flat fee eats 15% of every $2 charge. At scale, consider
> Stripe's monthly invoicing to batch charges, or explore switching
> to ACH/SEPA for lower flat fees.

### Grok API (per creator onboarding)
Profile import + AI theme generation ≈ 5,000–8,000 tokens per signup.

| Operation | Tokens | Cost (Grok 4.1 Fast) |
|---|---|---|
| X profile import | ~1,500 input | ~$0.0003 |
| Theme generation | ~4,000 input + ~2,000 output | ~$0.0018 |
| **Per signup total** | | **~$0.002** |

Essentially free. Even at 1,000 creator signups the API cost is ~$2 total.

---

## Break-Even Analysis

Fixed monthly costs ≈ **$28.25/mo**
Net monthly revenue per creator ≈ **$1.64/mo** (after Stripe)

```
Break-even creators = $28.25 / $1.64 = ~18 active creators
```

At 18 paying creators the platform runs for free.
Every creator beyond 18 is profit.

---

## Revenue & Profit at Scale

| Active creators | Monthly revenue (gross) | Stripe fees | Net revenue | Fixed costs | **Monthly profit** |
|---|---|---|---|---|---|
| 10 | $20 | $3.58 | $16.42 | $28.25 | **-$11.83** |
| 18 | $36 | $6.44 | $29.56 | $28.25 | **+$1.31** ← break-even |
| 50 | $100 | $17.90 | $82.10 | $28.25 | **+$53.85** |
| 100 | $200 | $35.80 | $164.20 | $35.00* | **+$129.20** |
| 250 | $500 | $89.50 | $410.50 | $35.00* | **+$375.50** |
| 500 | $1,000 | $179.00 | $821.00 | $49.00** | **+$772.00** |

*KVM4 VPS upgrade at ~100 creators ($14/mo)
**Additional Vercel seat or storage at ~500 creators

---

## Setup Fee Revenue (One-Time)

Each new creator also pays $5 setup → $4.56 net.
This offsets the first few months of fixed costs during the growth phase.

| New signups/mo | Setup revenue (net) |
|---|---|
| 5 | $22.80 |
| 10 | $45.60 |
| 25 | $114.00 |

---

## Infrastructure Scaling Triggers

| Milestone | Action | Cost increase |
|---|---|---|
| ~100 creators | Upgrade VPS: KVM2 → KVM4 | +$7/mo |
| ~300 creators | Add Vercel Blob or R2 for media storage | +$5–15/mo |
| ~500 creators | Consider dedicated DB (Neon/Supabase) | +$25/mo |
| ~1,000 creators | VPS → Cloud or managed Postgres | Evaluate |

Drupal + PostgreSQL on a single VPS can comfortably serve 200–400 concurrent
creators without optimization. The bottleneck at scale will be media storage
(profile images, banners) not compute — CDN/object storage is the next upgrade.

---

## Summary

| | |
|---|---|
| Monthly fixed floor | ~$28/mo |
| Break-even | 18 active creators |
| Stripe overhead | ~18% of $2 charges |
| AI (Grok) cost | Negligible (~$0.002/signup) |
| Margin at 100 creators | ~$129/mo |
| Margin at 500 creators | ~$772/mo |

The unit economics are healthy beyond 18 creators. The main cost pressure is
Stripe's flat $0.30 fee relative to the $2 monthly charge — worth revisiting
pricing or payment method if churn and transaction volume becomes significant.
