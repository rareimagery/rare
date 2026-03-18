# Printful API Test Playbook — RareImagery

> **Purpose:** Validate the `commerce_printful` Drupal module integration end-to-end using real Printful API calls against a dedicated test store. All products and orders created here are disposable — cleanup commands at the bottom.

---

## Prerequisites

1. **Create a test store:** Printful Dashboard → Stores → "Connect via API" → creates a Manual Order / API store
2. **Generate a Private Token:** [Developer Portal](https://developers.printful.com/tokens) → scoped to the test store → scopes: `orders`, `sync_products`, `file_library`, `webhooks`
3. **Set your token:**

```bash
export PF_TOKEN="your_private_token_here"
export PF_BASE="https://api.printful.com"
```

4. **Test print file:** We'll use Printful's own public logo as a placeholder design. Replace with your own URLs when testing real designs.

```bash
export TEST_PRINT_URL="https://www.printful.com/static/images/layout/printful-logo.png"
```

---

## Phase 0: Catalog Discovery (No Auth Required)

Before creating anything, pull real variant IDs from the catalog. These IDs are what you actually pass to product and order creation — **never use Product IDs for that**.

### 0A. List product categories

```bash
curl -s "$PF_BASE/categories" | jq '.result[] | {id, title}'
```

Grab category IDs for the product types you care about (T-Shirts, Posters, Mugs, etc.).

### 0B. Get products in a category

```bash
# Example: T-Shirts (category_id varies — use 0A output)
curl -s "$PF_BASE/products?category_id=24" | jq '.result[] | {id, title, type, is_discontinued}'
```

### 0C. Get variants for a specific product

This is the critical step. You need the `variant.id` values from here.

```bash
# Product 71 = Bella + Canvas 3001 (most popular DTG tee)
curl -s "$PF_BASE/products/71" | jq '{
  product: .result.product.title,
  variant_count: .result.product.variant_count,
  print_files: [.result.product.files[] | {type, title, additional_price}],
  techniques: .result.product.techniques,
  variants: [.result.variants[] | {id, name, size, color, color_code, price, in_stock}]
}'
```

```bash
# Product 1 = Gildan 18000 (Sweatshirt)
curl -s "$PF_BASE/products/1" | jq '.result.variants[:5] | .[] | {id, name, size, color, price, in_stock}'
```

```bash
# Product 358 = Poster (for non-apparel testing)
curl -s "$PF_BASE/products/358" | jq '.result.variants[:5] | .[] | {id, name, size, price, in_stock}'
```

```bash
# Product 19 = Mug 11oz
curl -s "$PF_BASE/products/19" | jq '.result.variants[:3] | .[] | {id, name, price, in_stock}'
```

### 0D. Get print file specs for a product

Needed for file positioning and mockup generation.

```bash
curl -s "$PF_BASE/mockup-generator/printfiles/71" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result.printfiles[] | {printfile_id, width, height, dpi, fill_mode}'
```

> **Save the variant IDs you get from 0C.** The commands below use placeholder IDs (`VARIANT_ID_HERE`) — replace them with your actual catalog lookups.

---

## Phase 1: Create Test Sync Products

These simulate what happens when a creator sets up merch in the RareImagery console.

### 1A. DTG T-Shirt — Multi-variant (3 sizes, 2 colors = 6 variants)

This is the bread-and-butter product type. Replace `variant_id` values with real IDs from Phase 0C.

```bash
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — DTG Tee Multi-Variant",
    "external_id": "ri_test_prod_001",
    "thumbnail": "'"$TEST_PRINT_URL"'"
  },
  "sync_variants": [
    {
      "variant_id": 4011,
      "external_id": "ri_test_var_001_white_s",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    },
    {
      "variant_id": 4012,
      "external_id": "ri_test_var_001_white_m",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    },
    {
      "variant_id": 4013,
      "external_id": "ri_test_var_001_white_l",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    },
    {
      "variant_id": 4017,
      "external_id": "ri_test_var_001_black_s",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    },
    {
      "variant_id": 4018,
      "external_id": "ri_test_var_001_black_m",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    },
    {
      "variant_id": 4019,
      "external_id": "ri_test_var_001_black_l",
      "retail_price": "24.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    }
  ]
}' | jq '.'
```

**Save the returned `result.id`** — that's the Printful Sync Product ID.

### 1B. DTG T-Shirt — Front + Back Print

Tests multi-placement file handling and additional pricing.

```bash
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — DTG Tee Front+Back",
    "external_id": "ri_test_prod_002"
  },
  "sync_variants": [
    {
      "variant_id": 4012,
      "external_id": "ri_test_var_002_white_m",
      "retail_price": "29.99",
      "files": [
        {"type": "front", "url": "'"$TEST_PRINT_URL"'"},
        {"type": "back", "url": "'"$TEST_PRINT_URL"'"}
      ]
    }
  ]
}' | jq '.'
```

### 1C. Poster (Non-Apparel)

Validates that the integration handles non-clothing product types.

```bash
# Use variant IDs from: curl -s "$PF_BASE/products/358" | jq '.result.variants[:3]'
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — Poster 18x24",
    "external_id": "ri_test_prod_003"
  },
  "sync_variants": [
    {
      "variant_id": 10075,
      "external_id": "ri_test_var_003_18x24",
      "retail_price": "19.99",
      "files": [{"type": "default", "url": "'"$TEST_PRINT_URL"'"}]
    }
  ]
}' | jq '.'
```

### 1D. Mug (11oz)

```bash
# Use variant IDs from: curl -s "$PF_BASE/products/19" | jq '.result.variants[:3]'
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — Mug 11oz",
    "external_id": "ri_test_prod_004"
  },
  "sync_variants": [
    {
      "variant_id": 1320,
      "external_id": "ri_test_var_004_11oz",
      "retail_price": "14.99",
      "files": [{"type": "default", "url": "'"$TEST_PRINT_URL"'"}]
    }
  ]
}' | jq '.'
```

### 1E. T-Shirt with Native Inside Label

Tests the label replacement feature (tear-away label products only).

```bash
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — DTG Tee + Inside Label",
    "external_id": "ri_test_prod_005"
  },
  "sync_variants": [
    {
      "variant_id": 4012,
      "external_id": "ri_test_var_005_white_m",
      "retail_price": "27.99",
      "files": [
        {"type": "front", "url": "'"$TEST_PRINT_URL"'"},
        {
          "type": "label_inside",
          "url": "'"$TEST_PRINT_URL"'",
          "options": [{"id": "template_type", "value": "native"}]
        }
      ]
    }
  ]
}' | jq '.'
```

---

## Phase 2: Verify Created Products

### 2A. List all sync products in the test store

```bash
curl -s "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result[] | {id, external_id, name, variants, synced}'
```

### 2B. Get a specific product by External ID

```bash
curl -s "$PF_BASE/store/products/@ri_test_prod_001" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

### 2C. Get a specific variant by External ID

```bash
curl -s "$PF_BASE/store/variants/@ri_test_var_001_black_m" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

---

## Phase 3: Modify a Sync Product

Tests the PUT behavior — **remember: omitted variant IDs get deleted**.

### 3A. Update product name + add a new variant

First, get the current sync variant IDs:

```bash
SYNC_PROD_ID=$(curl -s "$PF_BASE/store/products/@ri_test_prod_001" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result.sync_product.id')

echo "Sync Product ID: $SYNC_PROD_ID"

# Get all existing sync variant IDs (you MUST include these to keep them)
curl -s "$PF_BASE/store/products/@ri_test_prod_001" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '[.result.sync_variants[].id]'
```

Then PUT with all existing IDs + a new variant (no `id` field = Printful creates it):

```bash
# Replace EXISTING_IDS with the array from above
curl -s -X PUT "$PF_BASE/store/products/@ri_test_prod_001" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {
    "name": "RI Test — DTG Tee Multi-Variant (Updated)"
  },
  "sync_variants": [
    {"id": EXISTING_VARIANT_ID_1},
    {"id": EXISTING_VARIANT_ID_2},
    {"id": EXISTING_VARIANT_ID_3},
    {"id": EXISTING_VARIANT_ID_4},
    {"id": EXISTING_VARIANT_ID_5},
    {"id": EXISTING_VARIANT_ID_6},
    {
      "variant_id": 4014,
      "external_id": "ri_test_var_001_white_xl",
      "retail_price": "26.99",
      "files": [{"type": "front", "url": "'"$TEST_PRINT_URL"'"}]
    }
  ]
}' | jq '.'
```

> **Rate limit warning:** Product modifications are capped at 10 requests per 60 seconds.

---

## Phase 4: Draft Orders (Free — No Fulfillment, No Charge)

These validate order creation, cost calculation, and external ID mapping without spending money.

### 4A. Create a draft order using a Sync Variant

```bash
curl -s -X POST "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "external_id": "ri_test_ord_001",
  "recipient": {
    "name": "Test Customer",
    "address1": "19749 Dearborn St",
    "city": "Chatsworth",
    "state_code": "CA",
    "country_code": "US",
    "zip": "91311"
  },
  "items": [
    {
      "sync_variant_id": SYNC_VARIANT_ID_HERE,
      "quantity": 1
    }
  ],
  "retail_costs": {
    "subtotal": "24.99",
    "shipping": "3.99",
    "tax": "2.25"
  }
}' | jq '.'
```

**No `"confirm": true`** → stays in `draft` status → no charge.

### 4B. Create a draft order using External Variant ID

```bash
curl -s -X POST "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "external_id": "ri_test_ord_002",
  "recipient": {
    "name": "Test Customer Two",
    "address1": "456 Oak Ave",
    "city": "Austin",
    "state_code": "TX",
    "country_code": "US",
    "zip": "73301"
  },
  "items": [
    {
      "external_variant_id": "ri_test_var_001_black_m",
      "quantity": 2
    }
  ]
}' | jq '.'
```

### 4C. Create a draft order using Catalog Variant (no sync product needed)

This tests the on-the-fly product path — useful if we ever support one-off orders without pre-synced products.

```bash
curl -s -X POST "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "external_id": "ri_test_ord_003",
  "recipient": {
    "name": "Test Customer Three",
    "address1": "789 Pine Rd",
    "city": "Denver",
    "state_code": "CO",
    "country_code": "US",
    "zip": "80201"
  },
  "items": [
    {
      "variant_id": 4018,
      "quantity": 1,
      "files": [
        {
          "type": "front",
          "url": "'"$TEST_PRINT_URL"'"
        }
      ]
    }
  ]
}' | jq '.'
```

### 4D. Multi-item draft order

```bash
curl -s -X POST "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "external_id": "ri_test_ord_004",
  "recipient": {
    "name": "Test Multi-Item",
    "address1": "100 Broadway",
    "city": "New York",
    "state_code": "NY",
    "country_code": "US",
    "zip": "10001"
  },
  "items": [
    {
      "external_variant_id": "ri_test_var_001_white_s",
      "quantity": 1
    },
    {
      "external_variant_id": "ri_test_var_004_11oz",
      "quantity": 2
    }
  ]
}' | jq '.'
```

---

## Phase 5: Cost Estimation (No Side Effects)

Returns pricing without creating any order. Use for checkout validation.

### 5A. Estimate a single item

```bash
curl -s -X POST "$PF_BASE/orders/estimate" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "recipient": {
    "address1": "19749 Dearborn St",
    "city": "Chatsworth",
    "state_code": "CA",
    "country_code": "US",
    "zip": "91311"
  },
  "items": [
    {
      "external_variant_id": "ri_test_var_001_black_m",
      "quantity": 1
    }
  ]
}' | jq '.result.costs'
```

### 5B. Estimate with shipping options

```bash
curl -s -X POST "$PF_BASE/shipping/rates" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "recipient": {
    "address1": "19749 Dearborn St",
    "city": "Chatsworth",
    "state_code": "CA",
    "country_code": "US",
    "zip": "91311"
  },
  "items": [
    {
      "external_variant_id": "ri_test_var_001_black_m",
      "quantity": 1
    }
  ]
}' | jq '.result[] | {id, name, rate, currency, minDeliveryDays: .minDeliveryDays, maxDeliveryDays: .maxDeliveryDays}'
```

### 5C. Estimate international shipping

```bash
curl -s -X POST "$PF_BASE/orders/estimate" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "shipping": "STANDARD",
  "recipient": {
    "address1": "10 Downing Street",
    "city": "London",
    "country_code": "GB",
    "zip": "SW1A 2AA"
  },
  "items": [
    {
      "external_variant_id": "ri_test_var_001_white_m",
      "quantity": 1
    }
  ]
}' | jq '.result.costs'
```

---

## Phase 6: Verify Draft Orders

### 6A. List all orders

```bash
curl -s "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result[] | {id, external_id, status, created}'
```

### 6B. Get order by External ID

```bash
curl -s "$PF_BASE/orders/@ri_test_ord_001" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

---

## Phase 7: Webhook Setup & Testing

### 7A. Register webhook endpoint

Point this at your Drupal test environment or a temporary receiver like [webhook.site](https://webhook.site) for initial testing.

```bash
curl -s -X POST "$PF_BASE/webhooks" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "url": "https://your-test-endpoint.webhook.site/unique-id",
  "types": [
    "package_shipped",
    "order_failed",
    "order_canceled",
    "order_updated",
    "order_created",
    "order_put_hold",
    "order_remove_hold",
    "stock_updated",
    "product_synced",
    "product_updated"
  ]
}' | jq '.'
```

### 7B. Verify webhook config

```bash
curl -s "$PF_BASE/webhooks" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

### 7C. Use Printful's Webhook Simulator

Go to: **Printful Dashboard → Settings → Store settings → API → Webhook Simulator**

This lets you fire test events (`package_shipped`, `order_failed`, etc.) at your registered URL without needing a real order to go through fulfillment. Use this to validate your Drupal webhook receiver and `store_notifications` module.

---

## Phase 8: Mockup Generation

### 8A. Get printfile specs

```bash
curl -s "$PF_BASE/mockup-generator/printfiles/71" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result'
```

### 8B. Create a mockup generation task

```bash
curl -s -X POST "$PF_BASE/mockup-generator/create-task/71" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "variant_ids": [4012, 4018],
  "files": [
    {
      "placement": "front",
      "image_url": "'"$TEST_PRINT_URL"'",
      "position": {
        "area_width": 1800,
        "area_height": 2400,
        "width": 1800,
        "height": 1800,
        "top": 300,
        "left": 0
      }
    }
  ]
}' | jq '.'
```

**Save the returned `task_key`.**

### 8C. Poll for mockup result

```bash
TASK_KEY="your_task_key_here"
curl -s "$PF_BASE/mockup-generator/task?task_key=$TASK_KEY" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

Repeat until `status` is `completed`. The `mockups` array will contain downloadable image URLs.

> **Rate limit:** Mockup generation has a lower rate limit than the general API. Don't hammer this in a loop — add a 5-second sleep between polls.

---

## Phase 9: Error Handling Tests

### 9A. Invalid variant ID (should return 400)

```bash
curl -s -X POST "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "sync_product": {"name": "RI Test — Bad Variant", "external_id": "ri_test_prod_err_001"},
  "sync_variants": [{"variant_id": 9999999, "retail_price": "10.00", "files": [{"url": "'"$TEST_PRINT_URL"'"}]}]
}' | jq '.'
```

### 9B. Missing required fields (should return 400)

```bash
curl -s -X POST "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
  "external_id": "ri_test_ord_err_001",
  "recipient": {"name": "Incomplete Address"},
  "items": [{"external_variant_id": "ri_test_var_001_black_m", "quantity": 1}]
}' | jq '.'
```

### 9C. Duplicate external ID (should return 400)

Run the same order creation from 4A twice — second call should fail with a duplicate external_id error.

### 9D. Non-existent external ID lookup (should return 404)

```bash
curl -s "$PF_BASE/store/products/@does_not_exist" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

---

## Phase 10: Cleanup

**Delete all test orders first, then products.** Order deletion cancels drafts; product deletion removes all associated sync variants.

### 10A. Cancel/delete all test draft orders

```bash
for ORD_EXT_ID in ri_test_ord_001 ri_test_ord_002 ri_test_ord_003 ri_test_ord_004; do
  echo "Deleting order @$ORD_EXT_ID..."
  curl -s -X DELETE "$PF_BASE/orders/@$ORD_EXT_ID" \
    -H "Authorization: Bearer $PF_TOKEN" | jq '{code, result}'
  sleep 1
done
```

### 10B. Delete all test sync products

```bash
for PROD_EXT_ID in ri_test_prod_001 ri_test_prod_002 ri_test_prod_003 ri_test_prod_004 ri_test_prod_005; do
  echo "Deleting product @$PROD_EXT_ID..."
  curl -s -X DELETE "$PF_BASE/store/products/@$PROD_EXT_ID" \
    -H "Authorization: Bearer $PF_TOKEN" | jq '{code, result: .result.sync_product.name}'
  sleep 1
done
```

### 10C. Remove webhook config

```bash
curl -s -X DELETE "$PF_BASE/webhooks" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.'
```

### 10D. Verify store is clean

```bash
echo "=== Remaining Products ==="
curl -s "$PF_BASE/store/products" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result | length'

echo "=== Remaining Orders ==="
curl -s "$PF_BASE/orders" \
  -H "Authorization: Bearer $PF_TOKEN" | jq '.result | length'
```

---

## Variant ID Quick Reference

These are commonly referenced Product IDs for catalog lookups. **Always verify variant IDs with a fresh catalog call before using them** — Printful can add/remove variants at any time.

| Product ID | Product Name | Notes |
|---|---|---|
| 71 | Bella + Canvas 3001 (Unisex Tee) | Most popular DTG tee. Tear-away label. |
| 380 | Bella + Canvas 3001 (All-Over Print) | All-over sublimation variant |
| 1 | Gildan 18000 (Sweatshirt) | Heavy blend crewneck |
| 19 | Mug 11oz | White ceramic, wraparound print |
| 358 | Enhanced Matte Paper Poster | Multiple size variants |
| 83 | Canvas 3480 (Tank Top) | Unisex, DTG |
| 186 | Sticker (various sizes) | Kiss-cut, custom border color option |
| 171 | All-Over Print Tote | Sublimation |

Run `curl -s "$PF_BASE/products/{ID}" | jq '.result.variants[] | {id, name, size, color, price}'` against any of these to get the current variant IDs.

---

## What This Playbook Validates

| Integration Concern | Covered By |
|---|---|
| Catalog browsing + variant ID resolution | Phase 0 |
| Sync Product CRUD + external ID mapping | Phases 1, 2, 3 |
| Multi-variant product creation | 1A |
| Multi-placement files (front + back) | 1B |
| Non-apparel products (poster, mug) | 1C, 1D |
| Native inside label | 1E |
| Order creation via sync variant | 4A |
| Order creation via external variant ID | 4B |
| Order creation via catalog variant (on-the-fly) | 4C |
| Multi-item orders | 4D |
| Cost estimation (checkout math) | 5A |
| Shipping rate calculation | 5B |
| International shipping | 5C |
| Webhook registration + event simulation | Phase 7 |
| Mockup generation (async) | Phase 8 |
| Error handling (bad IDs, missing fields, dupes) | Phase 9 |
| Full cleanup | Phase 10 |
| PUT gotcha (omitted variants get deleted) | Phase 3 |
