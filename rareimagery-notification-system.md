# rareimagery.net — Notification System Options

## TL;DR Recommendation

**Use email as your primary notification channel.** It's the cheapest by a wide margin, the most reliable, and the easiest to integrate with Drupal. Add SMS as an opt-in upgrade for store owners who want instant phone alerts. Skip voice/phone — it's expensive and overkill for this use case.

**Best setup for rareimagery.net:**
- 📧 **Email → Brevo (free tier)** — free for up to 300 emails/day, sent from `@rareimagery.net`
- 📱 **SMS → Telnyx (pay-as-you-go)** — $0.004/message, no monthly fee, opt-in only
- ☎️ **Phone/Voice** → Skip entirely for now

---

## Option Comparison

| Channel | Best Provider | Monthly Cost | Per Message | Reliability | Setup Effort |
|---|---|---|---|---|---|
| **Email** | Brevo (free) | **$0** | $0 | ⭐⭐⭐⭐⭐ | Easy |
| **Email** | Amazon SES | ~$0–$1 | $0.10/1,000 | ⭐⭐⭐⭐⭐ | Moderate |
| **Email** | SMTP2GO | $0 (free plan) | $0 | ⭐⭐⭐⭐⭐ | Easy |
| **SMS** | Telnyx | $0 base | $0.004/msg | ⭐⭐⭐⭐ | Moderate |
| **SMS** | Plivo | $0 base | $0.0055/msg | ⭐⭐⭐⭐ | Moderate |
| **SMS** | Twilio | $0 base | $0.0079/msg | ⭐⭐⭐⭐⭐ | Moderate |
| **Phone/Voice** | Telnyx | $0 base | $0.004/min | ⭐⭐⭐⭐ | Hard |
| **Phone/Voice** | Twilio | $0 base | $0.014/min | ⭐⭐⭐⭐⭐ | Hard |

---

## Option 1: Email (Recommended — Primary Channel)

Email is the right choice because:
- Free or near-free at the volumes rareimagery.net will see
- Sent from a professional `@rareimagery.net` address — looks legitimate and trustworthy
- Drupal has built-in email support, minimal custom code needed
- Works for every store owner without them needing to set anything up
- Creates a written record of every notification (approvals, sales, etc.)
- No compliance headaches (unlike SMS which requires 10DLC registration in the US)

### Setting Up rareimagery.net Email

Store owner notifications will be sent **from** an address like `notifications@rareimagery.net` or `noreply@rareimagery.net`. This requires:

1. **DNS records** on rareimagery.net (SPF, DKIM, DMARC) — prevents spam filtering
2. **A transactional email service** — handles actual sending at scale and maintains deliverability

### Recommended: Brevo (Free Tier)

**Cost: $0/month for up to 300 emails/day (9,000/month)**

At 300 emails/day, Brevo's free plan comfortably handles a small-to-medium store network. Even with 50 store owners each receiving 5 notifications/day, that's only 250 emails — well within free limits.

| Feature | Brevo Free |
|---|---|
| Monthly emails | 9,000 |
| Daily limit | 300 |
| Custom sending domain | ✅ (`@rareimagery.net`) |
| SMTP + API | ✅ |
| Email templates | ✅ |
| Delivery tracking | ✅ |
| Drupal module | ✅ `drupal/symfony_mailer` |
| DKIM authentication | ✅ |
| Cost | **$0** |

When the store network grows past 300 emails/day, the next paid tier is $9/month for 20,000 emails — still very cheap.

### Alternative: SMTP2GO (Free Tier)

**Cost: $0/month for up to 1,000 emails/month**

SMTP2GO offers a free plan that lets you send 1,000 emails per month at no cost, and it achieved the second-best deliverability rate in independent tests — making it the top recommendation for value for money. Good fallback if Brevo's daily 300-limit is ever hit.

### Alternative: Amazon SES

**Cost: ~$0.10 per 1,000 emails (essentially pennies)**

Best if you're already on AWS. Amazon SES is cheaper than any alternative service, but it does less — features like automatic bounce handling and email analytics aren't included, leaving you to build that functionality yourself. More technical to set up but essentially free at low volumes.

### Drupal Integration (Email)

Install `symfony_mailer` (recommended for Drupal 10/11) and configure it to use Brevo's SMTP:

```
Admin → Configuration → System → Mailer
SMTP Host: smtp-relay.brevo.com
Port: 587
Auth: TLS
Username: [Brevo SMTP login]
Password: [Brevo SMTP key]
From address: notifications@rareimagery.net
```

Add SPF + DKIM records to rareimagery.net DNS (Brevo provides these during setup).

---

## Option 2: SMS (Opt-In Add-On)

SMS is a great complement to email — it's instant and gets read immediately. The key word is **opt-in**: store owners provide their cell number and choose to receive SMS alerts. This keeps costs near-zero because you only pay when messages are sent.

**Biggest cost concern: US A2P 10DLC registration.** Any business sending SMS in the US must register with carriers (AT&T, Verizon, T-Mobile) to avoid message blocking. This is a one-time setup but adds ~$20–$30 in registration fees plus a small monthly carrier fee (~$10/month). It's manageable but worth knowing upfront.

### Recommended: Telnyx

**Cost: No monthly fee. $0.004 per outbound SMS + carrier fees**

Telnyx charges per message with no monthly plan required. Outbound and inbound SMS messages start at $0.004 each, plus standard carrier fees. At $0.004/message, sending 1,000 SMS notifications costs $4.00. It also owns its own network end-to-end, which means lower latency and higher reliability than resellers.

| Feature | Telnyx |
|---|---|
| Monthly fee | $0 |
| Outbound SMS | $0.004/msg |
| Inbound SMS | $0.004/msg |
| API quality | ⭐⭐⭐⭐⭐ |
| 10DLC registration | ✅ (required for US) |
| PHP/Node.js SDKs | ✅ |
| Free incoming messages | ❌ (small fee) |

### Alternative: Plivo

**Cost: No monthly fee. $0.0055 per SMS**

Slightly more expensive than Telnyx per message but well-documented and developer-friendly. Plivo is a developer-focused messaging platform that offers clear pricing, good documentation, and reliable global coverage — most of what you need is self-serve and well-documented.

### Real-World SMS Cost Estimate

For rareimagery.net with ~25 active store owners, each receiving ~3 SMS/day:

```
25 store owners × 3 SMS/day × 30 days = 2,250 SMS/month
2,250 × $0.004 = $9.00/month
+ 10DLC monthly carrier fee ≈ $10/month
Total SMS cost ≈ $19/month
```

For the scale this platform will start at, SMS is very affordable.

### Drupal SMS Integration

Use the `sms_framework` Drupal module + a custom gateway plugin for Telnyx, or send via Telnyx's REST API directly from the `store_notifications` custom module:

```php
// Send SMS via Telnyx REST API
$response = \Drupal::httpClient()->post('https://api.telnyx.com/v2/messages', [
  'headers' => [
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type'  => 'application/json',
  ],
  'json' => [
    'from' => '+1XXXXXXXXXX',  // rareimagery.net's registered number
    'to'   => $storeOwnerPhone,
    'text' => $message,
  ],
]);
```

---

## Option 3: Voice / Phone Calls (Not Recommended)

Phone call notifications are technically possible (a robo-call announces a new sale) but are:

- More expensive per notification than SMS
- Disruptive — most people don't want phone calls from automated systems
- Complex to build (IVR trees, call handling, voicemail detection)
- Overkill for what rareimagery.net needs

**Verdict: Skip.** Email + SMS covers every urgency level already. If a store owner misses an email, they'll get the SMS. That's enough.

---

## Recommended Architecture for rareimagery.net

```
Drupal Notification Event
        ↓
store_notifications module
        ↓
┌─────────────────────────────────────────┐
│  Check store owner notification prefs  │
└─────────────────────────────────────────┘
        │
        ├──▶ Email (always)
        │    Brevo SMTP → notifications@rareimagery.net
        │
        └──▶ SMS (if opted in + phone number on file)
             Telnyx API → store owner's cell
```

Every notification goes by email. SMS is an additive layer for owners who want it.

---

## DNS Setup for rareimagery.net

To send from `@rareimagery.net` without landing in spam, add these DNS records (Brevo provides the exact values during setup):

| Record Type | Name | Value | Purpose |
|---|---|---|---|
| TXT | `@` | `v=spf1 include:sendinblue.com ~all` | SPF — authorizes Brevo to send |
| TXT | `mail._domainkey` | `[Brevo DKIM value]` | DKIM — signs outgoing emails |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:admin@rareimagery.net` | DMARC — policy for failed auth |
| CNAME | `[Brevo tracking subdomain]` | `[Brevo value]` | Click/open tracking |

These are standard copy-paste records. Brevo's setup wizard walks through each one.

---

## Store Owner Notification Preferences

In Drupal, the user profile captures notification preferences:

| Field | Options |
|---|---|
| Email | Always on — required field |
| Mobile Number | Optional — enables SMS |
| Notification Channel | Email only / Email + SMS |
| SMS Alerts For | All events / Sales only / Critical only |

**Critical alerts** (account approved/rejected, payment issues) always send both email and SMS regardless of preference, since these must not be missed.

---

## Notification Types & Channels

| Event | Email | SMS |
|---|---|---|
| New store application (to admin) | ✅ | ✅ opt-in |
| Account approved | ✅ | ✅ opt-in |
| Account rejected | ✅ | ✅ opt-in |
| New sale | ✅ | ✅ opt-in |
| Order shipped (Printful) | ✅ | ✅ opt-in |
| New product review | ✅ | ❌ email only |
| Low stock warning | ✅ | ❌ email only |
| Platform announcement | ✅ | ❌ email only |
| Monthly sales summary | ✅ | ❌ email only |

SMS is reserved for time-sensitive, action-required events. Low-priority notifications stay email-only to keep SMS costs down.

---

## Cost Summary

### Scenario: 25 Store Owners, Early Stage

| Channel | Provider | Monthly Cost |
|---|---|---|
| Email | Brevo Free | **$0** |
| SMS (opt-in, ~50% uptake) | Telnyx | ~$9 + $10 10DLC fee = **~$19** |
| **Total** | | **~$19/month** |

### Scenario: 100 Store Owners, Growing

| Channel | Provider | Monthly Cost |
|---|---|---|
| Email | Brevo $9/mo plan (20k emails) | **$9** |
| SMS (opt-in, ~50% uptake) | Telnyx | ~$36 + $10 10DLC = **~$46** |
| **Total** | | **~$55/month** |

This is a very manageable cost for a platform at 100 stores.

---

## Recommended Drupal Modules

| Module | Purpose |
|---|---|
| `symfony_mailer` | Modern Drupal 10/11 email system — replaces core mail |
| `smtp` | Alternative SMTP configuration (simpler but less featured) |
| `sms_framework` | SMS gateway abstraction layer |
| `key` | Secure credential storage for API keys |
| `rules` | Trigger notifications on commerce/user events |
| `mailsystem` | Route different email types to different providers |

---

## Setup Priority

1. ✅ Register `notifications@rareimagery.net` sending address in Brevo
2. ✅ Add SPF, DKIM, DMARC DNS records to rareimagery.net
3. ✅ Configure Drupal `symfony_mailer` to use Brevo SMTP
4. ✅ Build `store_notifications` custom module (email templates + event hooks)
5. ✅ Test all notification types end-to-end
6. ⚠️ Register for Telnyx + complete 10DLC registration (takes 1–2 weeks)
7. ⚠️ Add SMS phone field to store owner profile
8. ⚠️ Wire SMS into `store_notifications` module as secondary channel
9. 🔁 Monitor Brevo delivery stats — upgrade plan if approaching daily limit
