# RareImagery — Drupal Commerce Pricing Model

## Structure Overview

| Charge | Amount | Type |
|--------|--------|------|
| Store Setup Fee | $5.00 | One-time, billed at creation |
| Monthly Maintenance | $2.00 | Recurring subscription |

---

## 1. Required Modules

```
drupal/commerce              # Core
drupal/commerce_payment      # Payment gateway integration
drupal/commerce_recurring    # Subscription/recurring billing
drupal/commerce_stripe       # Stripe gateway (recommended)
```

Install:
```bash
composer require drupal/commerce_recurring drupal/commerce_stripe
drush en commerce_recurring commerce_stripe -y
drush cr
```

---

## 2. Product & Plan Configuration

### 2a. Create a Product Type: "Creator Store Plan"

**Path:** `/admin/commerce/config/product-types/add`

| Field | Value |
|-------|-------|
| Label | Creator Store Plan |
| Machine name | `creator_store_plan` |
| Variation type | Store Subscription |

### 2b. Create a Variation Type: "Store Subscription"

**Path:** `/admin/commerce/config/product-variation-types/add`

Enable:
- [x] Generate variation titles automatically
- [x] Allow recurring orders (from `commerce_recurring`)

Add custom fields:
- `field_setup_fee` — Decimal, label: "One-Time Setup Fee"
- `field_billing_cycle` — Text (plain), default: `monthly`

### 2c. Create the Product

**Path:** `/admin/commerce/products/add/creator_store_plan`

| Field | Value |
|-------|-------|
| Title | RareImagery Creator Store |
| Price (recurring) | $2.00 / month |
| Setup fee | $5.00 |
| Status | Active |

---

## 3. Billing Cycle Configuration

**Path:** `/admin/commerce/config/billing-schedules/add`

| Setting | Value |
|---------|-------|
| Label | Monthly Creator Plan |
| Plugin | Fixed (calendar month) |
| Start trial | No |
| Dunning schedule | 3 retries over 7 days |
| Grace period | 3 days |

---

## 4. Setup Fee Implementation

`commerce_recurring` does not natively support one-time setup fees alongside subscriptions. Use an **initial order item** approach:

### Option A — Programmatic (Recommended)

In `rareimagery_store.module` (or a custom module):

```php
/**
 * Implements hook_commerce_order_presave().
 * Appends the $5 setup fee to first-ever subscription orders.
 */
function rareimagery_store_commerce_order_presave(OrderInterface $order) {
  if ($order->bundle() !== 'default') {
    return;
  }

  $uid = $order->getCustomerId();

  // Check if this customer has ever completed an order before.
  $existing = \Drupal::entityTypeManager()
    ->getStorage('commerce_order')
    ->loadByProperties([
      'uid' => $uid,
      'state' => 'completed',
    ]);

  if (!empty($existing)) {
    return; // Not a first-time customer, skip setup fee.
  }

  // Load the setup fee product variation (create a $5 SKU: SETUP-FEE).
  $variation_storage = \Drupal::entityTypeManager()
    ->getStorage('commerce_product_variation');
  $setup_fee_variation = $variation_storage->loadByProperties([
    'sku' => 'SETUP-FEE',
  ]);

  if (empty($setup_fee_variation)) {
    return;
  }

  $setup_fee_variation = reset($setup_fee_variation);

  $order_item = \Drupal\commerce_order\Entity\OrderItem::create([
    'type'                    => 'default',
    'purchased_entity'        => $setup_fee_variation,
    'title'                   => 'Store Setup Fee (one-time)',
    'quantity'                => 1,
    'unit_price'              => $setup_fee_variation->getPrice(),
    'overridden_unit_price'   => TRUE,
  ]);
  $order_item->save();
  $order->addItem($order_item);
}
```

### Option B — Stripe: Add Metadata on Subscription Create

Pass setup fee as a Stripe `add_invoice_items` entry when creating the subscription from your Next.js console API route:

```js
// In your Stripe subscription creation call
const subscription = await stripe.subscriptions.create({
  customer: stripeCustomerId,
  items: [{ price: MONTHLY_PRICE_ID }],
  add_invoice_items: [
    { price: SETUP_FEE_PRICE_ID } // One-time $5 price object in Stripe
  ],
});
```

> **Recommendation:** Use Option B (Stripe-side) for simplicity. Create two Price objects in Stripe — `price_setup_fee` ($5, one-time) and `price_monthly` ($2, recurring monthly). Drupal Commerce stores the subscription record; Stripe handles the billing split.

---

## 5. Stripe Price Objects to Create

In Stripe Dashboard → Products → Create product: **"RareImagery Creator Store"**

| Price Object | Amount | Type | Stripe ID (set env var) |
|---|---|---|---|
| Setup Fee | $5.00 | One time | `STRIPE_PRICE_SETUP_FEE` |
| Monthly Plan | $2.00 | Recurring / monthly | `STRIPE_PRICE_MONTHLY` |

---

## 6. Next.js Console — Store Creation API Update

In your store creation API route, after the Drupal Commerce Store POST, trigger the Stripe subscription:

```js
// /app/api/stores/create/route.js (addition)

import Stripe from 'stripe';
const stripe = new Stripe(process.env.STRIPE_SECRET_KEY);

export async function POST(req) {
  const { creatorId, email, slug } = await req.json();

  // 1. Create Drupal Commerce Store (existing logic)
  // ...

  // 2. Create or retrieve Stripe customer
  const customer = await stripe.customers.create({ email, metadata: { creatorId } });

  // 3. Create subscription with one-time setup fee on first invoice
  const subscription = await stripe.subscriptions.create({
    customer: customer.id,
    items: [{ price: process.env.STRIPE_PRICE_MONTHLY }],
    add_invoice_items: [
      { price: process.env.STRIPE_PRICE_SETUP_FEE }
    ],
    payment_behavior: 'default_incomplete',
    expand: ['latest_invoice.payment_intent'],
  });

  // 4. Return client_secret to frontend to complete payment
  return Response.json({
    subscriptionId: subscription.id,
    clientSecret: subscription.latest_invoice.payment_intent.client_secret,
  });
}
```

---

## 7. Drupal Commerce Store Entity — Subscription Status Field

Add a field to the Commerce Store entity to track subscription state, used for gating storefront access:

```bash
drush field:create \
  --entity-type=commerce_store \
  --bundle=online \
  --field-name=field_subscription_status \
  --field-label="Subscription Status" \
  --field-type=list_string \
  --field-widget=options_select
```

Allowed values:
- `active` — Store live, subscription current
- `past_due` — Payment failed, grace period active (store still visible)
- `cancelled` — Subscription ended, store hidden
- `pending` — Setup in progress

Update via Stripe webhook → Next.js webhook handler → Drupal PATCH.

---

## 8. Storefront Access Gate (Next.js Middleware)

```js
// middleware.js addition — check subscription_status from Drupal
const storeData = await fetchStoreBySlug(slug);

if (storeData?.field_subscription_status !== 'active' &&
    storeData?.field_subscription_status !== 'past_due') {
  return NextResponse.redirect(new URL('/store-inactive', req.url));
}
```

---

## Summary

```
Creator signs up
  → Next.js console POSTs Drupal Commerce Store entity
  → Stripe customer + subscription created ($5 setup + $2/mo)
  → Payment intent returned to browser → Stripe Elements collects payment
  → Stripe webhook fires → Next.js updates Drupal field_subscription_status = 'active'
  → Middleware allows subdomain access
```
