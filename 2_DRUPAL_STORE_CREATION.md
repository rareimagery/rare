# Drupal Store Creation – RareImagery X Marketplace
### Commerce Store + Creator X Profile via JSON:API

---

## Overview

Every creator store is two linked Drupal entities:

```
Commerce Store  ←──────────────────→  Creator X Profile
(products, currency, owner)           (PFP, banner, X posts, followers)
field_store_slug = "rareimagery"      field_x_username = "@rareimagery"
        │                                      │
        └──────── field_linked_x_profile ──────┘
```

Both are created together when a store is provisioned from the console.

---

## Step 1: Add `field_store_slug` to Commerce Store

In Drupal admin: **Commerce → Configuration → Store types → Manage fields**

| Field Label | Machine Name | Type | Settings |
|-------------|-------------|------|----------|
| Store Slug | `field_store_slug` | Text (plain) | Required · Max 30 chars |

This slug is the subdomain. It must be unique across all stores.

### Enforce uniqueness with a custom module

Create `/web/modules/custom/rareimagery_store/rareimagery_store.info.yml`:

```yaml
name: RareImagery Store
type: module
description: Custom constraints and helpers for RareImagery creator stores.
core_version_requirement: ^10
package: Custom
```

Create `/web/modules/custom/rareimagery_store/rareimagery_store.module`:

```php
<?php

/**
 * Implements hook_entity_bundle_field_info_alter().
 * Enforce unique slugs on Commerce Stores.
 */
function rareimagery_store_entity_bundle_field_info_alter(
  &$fields,
  \Drupal\Core\Entity\EntityTypeInterface $entity_type,
  $bundle
) {
  if ($entity_type->id() === 'commerce_store' && isset($fields['field_store_slug'])) {
    $fields['field_store_slug']->addConstraint('UniqueField');
  }
}
```

Enable it:

```bash
docker compose exec drupal drush en rareimagery_store -y
docker compose exec drupal drush cr
```

---

## Step 2: Verify Creator X Profile Content Type

The `creator_x_profile` content type must exist with these fields (see Creator X Profile Setup doc).
The critical field for store linking is:

| Field | Machine Name | Type |
|-------|-------------|------|
| Linked Store | `field_linked_store` | Entity Reference → Commerce Store |

---

## Step 3: Enable JSON:API + CORS

```bash
docker compose exec drupal drush en jsonapi -y
docker compose exec drupal drush cr
```

Edit `drupal/sites/default/services.yml`:

```yaml
parameters:
  cors.config:
    enabled: true
    allowedHeaders: ['*']
    allowedMethods: [GET, POST, PATCH, DELETE]
    allowedOrigins:
      - 'https://rareimagery.net'
      - 'https://*.rareimagery.net'
      - 'https://*.vercel.app'
    exposedHeaders: false
    maxAge: false
    supportsCredentials: false
```

```bash
docker compose exec drupal drush cr
```

---

## Step 4: Set Up API Authentication

```bash
docker compose exec drupal composer require drupal/simple_oauth
docker compose exec drupal drush en simple_oauth -y
docker compose exec drupal drush cr
```

1. Go to **Configuration → Simple OAuth → Generate keys**
2. Create an OAuth client for the Next.js console app
3. Store the token in Vercel as `DRUPAL_API_TOKEN`

---

## Step 5: API Endpoints Reference

All requests require:

```
Authorization: Bearer YOUR_DRUPAL_TOKEN
Content-Type: application/vnd.api+json
```

### Check if slug is taken

```
GET /jsonapi/commerce_store/online?filter[field_store_slug]=rareimagery
```

Returns empty `data[]` if available. Non-empty means taken.

---

### Create Commerce Store

```
POST /jsonapi/commerce_store/online
```

Request body:

```json
{
  "data": {
    "type": "commerce_store--online",
    "attributes": {
      "name": "Rare Imagery",
      "field_store_slug": "rareimagery",
      "mail": "owner@rareimagery.net",
      "default_currency": "USD"
    }
  }
}
```

Response includes `data.id` — the Drupal UUID of the new store. **Save this for the next call.**

---

### Create Creator X Profile (linked to store)

```
POST /jsonapi/node/creator_x_profile
```

Request body:

```json
{
  "data": {
    "type": "node--creator_x_profile",
    "attributes": {
      "title": "rareimagery X Profile",
      "field_x_username": "@rareimagery"
    },
    "relationships": {
      "field_linked_store": {
        "data": {
          "type": "commerce_store--online",
          "id": "STORE_UUID_FROM_PREVIOUS_CALL"
        }
      }
    }
  }
}
```

---

### Fetch store by slug (used by Next.js storefront)

```
GET /jsonapi/commerce_store/online?filter[field_store_slug]=rareimagery&include=field_linked_x_profile
```

---

## Step 6: Next.js API Route — Full Store Creation

This lives in the Next.js console app and orchestrates both Drupal calls.

### `app/api/stores/create/route.ts`

```typescript
import { NextRequest, NextResponse } from 'next/server'
import { getToken } from 'next-auth/jwt'
import { isValidSlug, RESERVED_SLUGS } from '@/lib/slugs'

const DRUPAL_API = process.env.DRUPAL_API_URL
const DRUPAL_TOKEN = process.env.DRUPAL_API_TOKEN

async function isSlugTaken(slug: string): Promise<boolean> {
  const res = await fetch(
    `${DRUPAL_API}/jsonapi/commerce_store/online?filter[field_store_slug]=${slug}`,
    { headers: { Authorization: `Bearer ${DRUPAL_TOKEN}` } }
  )
  const data = await res.json()
  return (data?.data?.length ?? 0) > 0
}

async function createDrupalStore(slug: string, storeName: string, ownerEmail: string) {
  const res = await fetch(`${DRUPAL_API}/jsonapi/commerce_store/online`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${DRUPAL_TOKEN}`,
      'Content-Type': 'application/vnd.api+json',
    },
    body: JSON.stringify({
      data: {
        type: 'commerce_store--online',
        attributes: {
          name: storeName,
          field_store_slug: slug,
          mail: ownerEmail,
          default_currency: 'USD',
        },
      },
    }),
  })
  if (!res.ok) throw new Error(`Drupal store creation failed: ${res.status}`)
  return res.json()
}

async function createXProfile(xUsername: string, storeId: string) {
  const res = await fetch(`${DRUPAL_API}/jsonapi/node/creator_x_profile`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${DRUPAL_TOKEN}`,
      'Content-Type': 'application/vnd.api+json',
    },
    body: JSON.stringify({
      data: {
        type: 'node--creator_x_profile',
        attributes: {
          title: `${xUsername} X Profile`,
          field_x_username: xUsername,
        },
        relationships: {
          field_linked_store: {
            data: { type: 'commerce_store--online', id: storeId },
          },
        },
      },
    }),
  })
  if (!res.ok) throw new Error(`X Profile creation failed: ${res.status}`)
  return res.json()
}

export async function POST(req: NextRequest) {
  const token = await getToken({ req })
  if (!token) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { storeName, slug, xUsername, ownerEmail } = await req.json()

  if (!isValidSlug(slug)) {
    return NextResponse.json(
      { error: 'Slug must be 3–30 lowercase letters, numbers, or hyphens' },
      { status: 400 }
    )
  }

  if (await isSlugTaken(slug)) {
    return NextResponse.json({ error: 'That subdomain is already taken' }, { status: 409 })
  }

  try {
    const storeData = await createDrupalStore(slug, storeName, ownerEmail)
    await createXProfile(xUsername, storeData.data.id)

    return NextResponse.json({
      success: true,
      storeId: storeData.data.id,
      slug,
      url: `https://${slug}.${process.env.NEXT_PUBLIC_BASE_DOMAIN}`,
    })
  } catch (err: any) {
    return NextResponse.json({ error: err.message }, { status: 500 })
  }
}
```

---

## Environment Variables

```bash
DRUPAL_API_URL=https://api.rareimagery.net
DRUPAL_API_TOKEN=your_drupal_oauth_token
NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
```

---

## Drupal Container Port

Now that `api.rareimagery.net` routes via Cloudflare, Drupal must listen on port 80. Update `docker-compose.yml`:

```yaml
ports:
  - "80:80"   # was "8080:80"
```

```bash
sudo ufw allow 80
docker compose up -d
```
