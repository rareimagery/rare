# 4. Demo Creator Profiles & Stores Setup – RareImagery X Marketplace

Live local: http://localhost:8080 (login: rare / Beyondcyn1234!)

## Prerequisites
- Docker running (`docker compose up -d`)
- Content types: `commerce_store` (with `field_store_slug`), `creator_x_profile` (fields from `CREATOR_X_PROFILE_CONTENT_TYPE_SETUP.md`)
- JSON:API enabled (Extend → JSON:API Resources)

## Step 1: Fix Docker Mount & Install Drush
```
docker compose down
docker compose up -d --build
docker compose exec drupal composer global require drush/drush:~12
docker compose exec drupal composer global config bin-dir ~/.composer/vendor/bin
```

## Step 2: Verify Content Types
```
docker compose exec drupal ~/.composer/vendor/bin/drush entity:info node creator_x_profile
curl http://localhost:8080/jsonapi/node/creator_x_profile  # Should 200
```

## Step 3: Create 5 Demo Profiles (Grok Mock Data)
Script auto-creates `elonmusk`, `alphafox`, `clownworld`, `doctorclownphd`, `ksjcreative` with scraped X data.
```
docker compose exec drupal ~/.composer/vendor/bin/drush php modules/custom/rareimagery_x_import/test_import.php
```
- View: `/admin/content`
- Test: `http://localhost:8080/node/[ID]`

## Step 4: Create Linked Commerce Stores (Manual Admin)
1. `/admin/commerce/config/store-types/manage/default/edit/fields/add-field` → Add `field_store_slug` (text, unique).
2. `/admin/commerce/stores/add` → Create store, set slug (e.g. `elonmusk`), link `field_linked_x_profile`.
3. Repeat for 5.

## Step 5: Test Frontend
```
cd frontend
npm i
npm run dev
```
- `localhost:3000` → Landing with profiles.
- `localhost:3000/stores/elonmusk` → Branded store (subdomain sim).

## Troubleshooting
- **500 endpoint**: Create content type/fields manually.
- **No custom modules**: `docker compose restart drupal`
- **No images**: Mock PFPs – add later via Grok/X API.
- **Subdomains**: Wildcard ready (`*.rareimagery.net` → Vercel).

**Next**: Link stores, add products, deploy Vercel. Profiles live! 🚀