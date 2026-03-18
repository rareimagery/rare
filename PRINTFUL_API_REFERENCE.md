# Printful API Reference — RareImagery Integration

## Base URL & Protocol

All requests go to `https://api.printful.com/`. RESTful, JSON in/out. Standard HTTP methods: GET, POST, PUT, DELETE.

Response envelope is always:

```json
{
  "code": 200,
  "result": { ... },
  "paging": { "total": 100, "offset": 0, "limit": 20 }  // when applicable
}
```

Errors return `code` outside 2xx with `error.reason` and `error.message`. 4xx = your fault, 5xx = Printful's fault. All timestamps are UNIX integers.

---

## Authentication

**For RareImagery: Use a Private Token (Store-level).**

We're a single platform integration, not a public OAuth app. Generate the token in Printful's [Developer Portal](https://developers.printful.com/tokens). Tokens don't need refreshing — they remain valid until expiry or manual deletion. **Copy it on creation; it won't be shown again.**

```
Authorization: Bearer {private_token}
```

If we ever move to Account-level tokens (managing multiple stores), add `X-PF-Store-Id` header per request.

**Scopes to enable:** `orders`, `sync_products`, `file_library`, `webhooks`

---

## Rate Limits

| Scope | Limit |
|---|---|
| General | 120 requests / minute |
| Resource-intensive (mockup generator) | Lower (unspecified exact number) |
| Catalog API (unauthenticated) | 30 requests / 60 seconds, 60s lockout on exceed |

**Implication for RareImagery:** Batch catalog browsing during product setup, not on every storefront page load. Cache catalog data in Drupal. Mockup generation should be queued/async.

---

## Key Concept: Variant ID vs Product ID

**Always use Variant ID when creating products or orders.** Product ID is for browsing the catalog only. A Variant is a specific size + color combination. Using Product ID by mistake will create the wrong item.

---

## API Surface — What RareImagery Needs

### 1. Catalog API (Public, No Auth Required)

Browse Printful's blank product catalog. Use during creator product setup in the console.

| Endpoint | Method | Purpose |
|---|---|---|
| `/products` | GET | List all available products (filterable by `category_id`) |
| `/products/{id}` | GET | Get product details + all its variants |
| `/products/variant/{id}` | GET | Get single variant details |
| `/products/{id}/sizes` | GET | Size guide data |
| `/categories` | GET | List product categories |

**Key fields in product response:**
- `files[]` — available print placements (front, back, label_inside, etc.) with `additional_price`
- `options[]` — product-specific options (embroidery type, thread colors)
- `techniques[]` — DTG, EMBROIDERY, SUBLIMATION, etc.
- `avg_fulfillment_time` — useful for showing estimated delivery
- `is_discontinued` — filter these out

**Key fields in variant response:**
- `id` — **this is what you pass to orders/sync products**
- `price` — Printful's cost (your base cost)
- `in_stock`, `availability_regions`, `availability_status`
- `color`, `color_code`, `size`

### 2. Products API (Auth Required) — Sync Products & Variants

Manages products in RareImagery's Printful store. Each creator product maps to a Sync Product; each size/color combo maps to a Sync Variant.

| Endpoint | Method | Purpose |
|---|---|---|
| `/store/products` | GET | List all sync products |
| `/store/products` | POST | **Create new sync product + variants** |
| `/store/products/{id}` | GET | Get sync product + its variants |
| `/store/products/{id}` | PUT | Modify sync product (partial update) |
| `/store/products/{id}` | DELETE | Delete sync product + all variants |
| `/store/variants/{id}` | GET/PUT/DELETE | Manage individual sync variants |
| `/store/products/{id}/variants` | POST | Add variant to existing product |

**Creating a Sync Product — minimum payload:**

```json
{
  "sync_product": {
    "name": "Creator's Custom Tee",
    "external_id": "ri_product_42",         // our Drupal entity ID
    "thumbnail": "https://cdn.rareimagery.net/..."
  },
  "sync_variants": [
    {
      "variant_id": 3001,                    // Printful catalog variant ID
      "external_id": "ri_variant_42_black_m", // our Drupal variant ID
      "retail_price": "29.99",
      "files": [
        {
          "type": "default",
          "url": "https://cdn.rareimagery.net/designs/creator42/front.png"
        }
      ]
    }
  ]
}
```

**Critical PUT behavior:** When modifying sync variants, you must include IDs of ALL existing variants you want to keep. Omitted variants get **deleted**. New variants (no ID) get created.

**Max 100 Sync Variants per Sync Product.**

**Rate limit on PUT:** 10 requests / 60 seconds for product modifications.

### 3. Orders API (Auth Required)

The core commerce flow. Customer buys → Drupal creates Printful order → Printful fulfills → ships to customer.

| Endpoint | Method | Purpose |
|---|---|---|
| `/orders` | GET | List orders (with `offset`/`limit` paging) |
| `/orders` | POST | **Create new order** |
| `/orders/{id}` | GET | Get order details |
| `/orders/{id}` | PUT | Update draft/failed order |
| `/orders/{id}` | DELETE | Cancel order |
| `/orders/{id}/confirm` | POST | Confirm draft → pending |
| `/orders/estimate` | POST | **Estimate costs without creating** |

**Order lifecycle:**

```
draft → pending → inprocess → partial/fulfilled
              ↘ failed (fixable, resubmittable)
              ↘ canceled
              ↘ onhold (needs Printful CS)
              ↘ inreview (can't cancel during review)
```

**Three ways to specify items:**

1. **Sync Variant** (recommended for RareImagery): `sync_variant_id` or `@external_variant_id`
2. **Catalog Variant** (on-the-fly, no pre-created product): `variant_id` + `files[]`
3. **Product Template**: `product_template_id` + `variant_id`

**Minimal order creation payload:**

```json
{
  "external_id": "ri_order_1234",
  "shipping": "STANDARD",
  "recipient": {
    "name": "John Smith",
    "address1": "123 Main St",
    "city": "Los Angeles",
    "state_code": "CA",
    "country_code": "US",
    "zip": "90001"
  },
  "items": [
    {
      "sync_variant_id": 10,
      "quantity": 1
    }
  ],
  "retail_costs": {
    "subtotal": "29.99",
    "shipping": "4.99",
    "tax": "2.50"
  }
}
```

Add `"confirm": true` to skip draft state and auto-submit for fulfillment.

**External ID rules:** Max 32 chars, alphanumeric + dashes + underscores. Must be unique per store. Reference with `@` prefix: `GET /orders/@ri_order_1234`.

**Cost estimation** (`POST /orders/estimate`) — same payload as order creation, returns costs without creating anything. Use this for checkout price calculation.

**File positioning** — for precise print placement, use the `position` object on files:

```json
"position": {
  "area_width": 1800,
  "area_height": 2400,
  "width": 900,
  "height": 900,
  "top": 300,
  "left": 450,
  "limit_to_print_area": true
}
```

Get printfile dimensions from Mockup Generator API's printfiles endpoint.

### 4. Webhook API (Auth Required)

Printful pushes order/product events to our endpoint. **This is how we know when orders ship, fail, etc.**

| Event | Fires When |
|---|---|
| `package_shipped` | Package shipped with tracking info |
| `package_returned` | Package returned |
| `order_created` | Order created in Printful |
| `order_updated` | Order data changed |
| `order_failed` | Order failed (address issue, charge fail, etc.) |
| `order_canceled` | Order canceled |
| `order_put_hold` | Order put on hold |
| `order_put_hold_approval` | Order needs approval |
| `order_remove_hold` | Order removed from hold |
| `order_refunded` | Order refunded |
| `product_synced` | Product synced to store |
| `product_updated` | Product updated |
| `product_deleted` | Product deleted |
| `stock_updated` | Variant stock level changed |

| Endpoint | Method | Purpose |
|---|---|---|
| `/webhooks` | GET | Get current webhook config |
| `/webhooks` | POST | Set webhook URL + events |
| `/webhooks` | DELETE | Disable webhooks |

**RareImagery webhook receiver:** Drupal endpoint at `https://api.rareimagery.net/printful/webhook` (or similar). The `commerce_printful` module processes these and updates order status, triggers `store_notifications`.

**Key events to subscribe to:**
- `package_shipped` — update order status, notify creator + customer
- `order_failed` — alert creator, flag in console
- `order_canceled` — update status
- `order_put_hold` / `order_remove_hold` — for async embroidery cost calculation edge case
- `stock_updated` — update variant availability on storefronts

### 5. Mockup Generator API (Auth Required)

Generate product mockups for storefront display. **Resource-intensive = lower rate limit.**

| Endpoint | Method | Purpose |
|---|---|---|
| `/mockup-generator/create-task/{product_id}` | POST | Start mockup generation (async) |
| `/mockup-generator/task` | GET | Check task status + get result URLs |
| `/mockup-generator/printfiles/{product_id}` | GET | Get printfile dimensions for a product |
| `/mockup-generator/templates/{product_id}` | GET | Get available layout templates |

**Flow:** Create task → poll for completion → download mockup images → cache in Drupal/CDN.

**RareImagery strategy:** Generate mockups during product creation in the console, store the resulting image URLs (or re-host them) in Drupal. Never generate on storefront page load.

### 6. Shipping Rate API

| Endpoint | Method | Purpose |
|---|---|---|
| `/shipping/rates` | POST | Calculate shipping for given items + recipient |

Use during checkout to show shipping options before order confirmation.

### 7. File Library API

| Endpoint | Method | Purpose |
|---|---|---|
| `/files` | POST | Upload a print file |
| `/files/{id}` | GET | Get file info |
| `/files/thread-colors` | POST | Suggest thread colors from image (embroidery) |

Files can also be specified by URL when creating products/orders (Printful downloads and stores them). URL-based is simpler for RareImagery since creator designs are already hosted.

### 8. Tax Rate API

| Endpoint | Method | Purpose |
|---|---|---|
| `/tax/countries` | GET | Countries where tax applies |
| `/tax/rates` | POST | Calculate tax for recipient address |

### 9. Store Information API

| Endpoint | Method | Purpose |
|---|---|---|
| `/stores` | GET | List stores on account |
| `/stores/{id}` | GET | Get store info |
| `/store/packing-slip` | POST | Update packing slip branding |

---

## Localization

Pass `X-PF-Language` header for translated product names. Supported: `en_US`, `en_GB`, `en_CA`, `es_ES`, `fr_FR`, `de_DE`, `it_IT`, `ja_JP`.

---

## Integration Architecture for RareImagery

### Data Flow

```
Creator Console (Next.js)
  ↓ creator selects product, uploads design
Drupal Backend (commerce_printful module)
  ↓ POST /store/products — creates Sync Product
  ↓ POST /mockup-generator/create-task — generates mockups
  ↓ stores mockup URLs + Printful IDs on Drupal entity
  ↓ exposes product to storefront via JSON:API

Customer Storefront (Next.js)
  ↓ customer adds to cart, checks out
  ↓ POST /shipping/rates — show shipping options
  ↓ POST /orders/estimate — show final price
  ↓ customer pays via Stripe
Drupal Backend
  ↓ POST /orders (confirm: true) — submit to Printful
  ↓ stores Printful order ID + external_id mapping

Printful Webhooks → Drupal
  ↓ package_shipped → update order, notify via Brevo/Telnyx
  ↓ order_failed → flag in creator console
```

### External ID Strategy

| RareImagery Entity | External ID Pattern | Example |
|---|---|---|
| Sync Product | `ri_prod_{drupal_node_id}` | `ri_prod_42` |
| Sync Variant | `ri_var_{drupal_node_id}_{color}_{size}` | `ri_var_42_black_m` |
| Order | `ri_ord_{drupal_order_id}` | `ri_ord_1234` |
| Order Line Item | `ri_item_{drupal_line_item_id}` | `ri_item_5678` |

This lets us always reference Printful entities by our IDs using `@` prefix, avoiding the need to store Printful's internal IDs (though we should store them too as a backup).

### Caching Strategy

| Data | Cache Location | TTL | Invalidation |
|---|---|---|---|
| Catalog products/variants | Drupal (custom entity or config) | 24h | Cron refresh |
| Variant stock/availability | Drupal field | Real-time via `stock_updated` webhook | Webhook |
| Mockup images | Cloudflare CDN | Indefinite | Re-generate on design change |
| Shipping rates | Not cached | — | Calculate per checkout |
| Tax rates | Not cached | — | Calculate per checkout |

### Error Handling

- **Order creation fails (4xx):** Surface error in creator console / checkout UI. Common causes: bad address, missing printfiles, invalid variant.
- **Webhook delivery fails:** Printful retries. Implement idempotency on our webhook receiver (check `order_id` + `event_type` combo against processed log).
- **Rate limit hit:** Queue and retry with exponential backoff. The `commerce_printful` module should use a Drupal Queue for all non-checkout Printful API calls.
- **Async cost calculation:** If order goes to hold for embroidery cost calc, subscribe to `order_remove_hold` webhook. Order returns to draft — must re-confirm via API.

### Important Constraints

- **Jewelry products are NOT supported via API** — exclude from catalog sync
- **Native inside labels only** — fully custom labels deprecated since April 2020
- **Max 100 Sync Variants per Sync Product** — plan color/size matrix accordingly
- **Product modifications rate-limited to 10/min** — batch updates carefully
- **File URL reuse** — Printful deduplicates by URL, so consistent CDN URLs prevent redundant uploads
