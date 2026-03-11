# Step 5: Commerce & Payments

**Agent:** Drupal (`drupal.md`)

## Entity Relationships

```
X Creator (signs in via OAuth)
    ↓
creator_x_profile (node) — stores X data, theme, bio
    ↓ field_linked_store
commerce_store (type: creator) — transactional store entity
    ↓
commerce_product (3 types) — items for sale
    ↓
commerce_product_variation (3 types) — size/color/file variants
    ↓
commerce_order (3 types) — split by product type in cart
```

## 3 Product Types

| Type | Bundle | Fulfillment | Use Case |
|------|--------|-------------|----------|
| Physical POD | `physical_pod` | Printful auto-fulfill | T-shirts, mugs, posters |
| Physical Custom | `physical_custom` | Manual shipping | Handmade, signed items |
| Digital Download | `digital_download` | Instant file delivery | Presets, PDFs, art files |

### Variation Types
| Variation | Attributes | Fields |
|-----------|-----------|--------|
| `pod_variation` | size, color | Printful product/variant ID |
| `custom_variation` | size, color, material | — |
| `digital_variation` | — | File attachment |

## 3 Order Types

Orders split automatically via `StoreOrderTypeResolver`:

| Order Type | Triggered By | Fulfillment |
|-----------|-------------|-------------|
| `pod_order` | Cart has pod_variation | PrintfulOrderSubscriber |
| `custom_order` | Cart has custom_variation | Manual |
| `digital_order` | Cart has digital_variation | Instant download |

Mixed carts create **separate orders per type** in a single checkout.

## SKU Convention

```
[STORE]-[TYPE]-[PRODUCT]-[VARIANT]
Example: RAREIMAGERY-POD-001-SM-BLK
```

## Platform Fees

`PlatformFeeSubscriber` adds fees on ORDER_PRE_SAVE:

| Order Type | Fee | Label |
|-----------|-----|-------|
| pod_order | $1.00 | RareImagery Platform Fee |
| custom_order | $1.00 | RareImagery Platform Fee |
| digital_order | $0.05 | RareImagery Platform Fee |

Fees are **locked** (customer can't remove) and **non-taxable**.

## Stripe Connect Payment Flow

```
Customer pays $25.00
    ↓
Platform Stripe account receives $25.00
    ↓
application_fee_amount = $1.00 (platform keeps)
    ↓
transfer_data.destination = creator's Stripe Connect account
    ↓
Creator receives $24.00
```

### Stripe Connect Onboarding
1. Creator clicks "Connect Stripe" in dashboard
2. `/api/stores/stripe-connect/onboarding` creates Connect account
3. Creator completes Stripe onboarding flow
4. Stripe account ID saved to store entity

### Subscription Model (Creator Fees)
- **$100** one-time store launch fee
- **$5/month** recurring maintenance
- Managed via `SubscriptionManagerService`
- Webhook: `StripeSubscriptionWebhookController`

## Printful Integration

### Sync Flow
```
Creator connects Printful → PrintfulSyncService.syncProducts()
    ↓
Fetches products/variants from Printful API
    ↓
Creates/updates commerce_product (physical_pod) + pod_variation entities
    ↓
Stores Printful product_id and variant_id on each variation
```

### Order Flow
```
Customer places pod_order → PrintfulOrderSubscriber fires
    ↓
Sends order to Printful API with shipping address
    ↓
Printful prints and ships
    ↓
PrintfulWebhookController receives status updates
    ↓
Order status updated in Drupal
```

## Key Files

| File | Purpose |
|------|---------|
| `src/EventSubscriber/PlatformFeeSubscriber.php` | Per-order platform fees |
| `src/EventSubscriber/StripeConnectSubscriber.php` | Payout routing |
| `src/EventSubscriber/PrintfulOrderSubscriber.php` | POD fulfillment trigger |
| `src/Service/PrintfulSyncService.php` | Printful product sync |
| `src/Service/SubscriptionManagerService.php` | Subscription billing |
| `src/Resolver/StoreOrderTypeResolver.php` | Mixed cart splitting |
| `config/install/commerce_payment_gateway.*` | Stripe gateway config |
| `config/install/commerce_checkout_flow.*` | Checkout flow config |

## Next Step

→ [Step 6: Next.js Frontend](06_NEXTJS.md)
