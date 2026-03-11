# Drupal Store Owner — User Management & Permissions

## Overview

This document defines the strategy for creating and managing Drupal user accounts for store owners, including role design, permissions, store assignment, and recommended modules. The goal is to give each store owner full control over their own store while preventing access to other stores or site-wide administration.

---

## Recommended Architecture

### Multi-Store Model in Drupal Commerce

Drupal Commerce natively supports multiple stores under a single Drupal installation. Each **Store** entity is owned by a user, and products, orders, and payment gateways can be scoped to a specific store. The recommended approach is:

1. Create a dedicated **`Store Owner`** role with scoped permissions.
2. Create a Drupal user account per store owner.
3. Create (or assign) a **Commerce Store** entity linked to that user.
4. Use the **Commerce Store** ownership field + **Entity Access** rules to enforce isolation between stores.

This keeps all stores on one Drupal + Commerce installation while keeping each owner's data separate.

---

## Role Design

### Roles to Create

| Role | Machine Name | Purpose |
|---|---|---|
| Administrator | `administrator` | Full site access — platform team only |
| Store Owner | `store_owner` | Manage own store, products, and orders |
| Store Manager | `store_manager` | Assistant to a store owner, limited access |
| Customer | `authenticated` | Default role for shoppers |

> For most deployments, **Store Owner** is the primary role to configure. Store Manager is optional for owners who want to delegate.

---

## Store Owner Role — Permissions

Configure these at `/admin/people/permissions`.

### Commerce — Store
| Permission | Grant? | Notes |
|---|---|---|
| View own store | ✅ | |
| Edit own store | ✅ | |
| Delete own store | ❌ | Platform admin only |
| Administer stores | ❌ | Admin only |
| View any store | ❌ | Prevents cross-store snooping |

### Commerce — Products
| Permission | Grant? | Notes |
|---|---|---|
| Create new products | ✅ | |
| Edit own products | ✅ | |
| Delete own products | ✅ | |
| View own unpublished products | ✅ | |
| Edit any product | ❌ | Scoped to own store only |
| Administer products | ❌ | Admin only |

### Commerce — Product Variations
| Permission | Grant? | Notes |
|---|---|---|
| Manage own product variations | ✅ | Via product edit form |
| Administer product variations | ❌ | Admin only |

### Commerce — Orders
| Permission | Grant? | Notes |
|---|---|---|
| View own store orders | ✅ | |
| Update own store orders | ✅ | e.g. mark as shipped |
| Manage order items | ✅ | |
| Administer orders | ❌ | Admin only |
| View any order | ❌ | Prevents cross-store access |

### Commerce — Promotions & Coupons
| Permission | Grant? | Notes |
|---|---|---|
| Manage own promotions | ✅ | Scoped to own store |
| Administer promotions | ❌ | Admin only |

### Taxonomy (Product attributes)
| Permission | Grant? | Notes |
|---|---|---|
| View taxonomy terms | ✅ | See categories/tags |
| Edit own terms | ⚠️ | Optional — if owners manage their own tags |
| Administer taxonomy | ❌ | Admin only |

### Media / Files
| Permission | Grant? | Notes |
|---|---|---|
| Upload files | ✅ | Product images, downloads |
| View own files | ✅ | |
| Delete own files | ✅ | |
| Administer files | ❌ | Admin only |

### Content (Nodes — if used for pages)
| Permission | Grant? | Notes |
|---|---|---|
| Create store page content | ⚠️ | Optional — for About/FAQ pages |
| Edit own content | ✅ | |
| Delete own content | ✅ | |
| Edit any content | ❌ | Admin only |

### User Account
| Permission | Grant? | Notes |
|---|---|---|
| Edit own user profile | ✅ | Name, email, password |
| View own user profile | ✅ | |
| Administer users | ❌ | Admin only |
| Cancel own account | ⚠️ | Decide by policy |

---

## Store Manager Role — Permissions (Optional)

A reduced version of Store Owner for delegated management. Does **not** include billing, payment, or store settings access.

| Area | Grant? |
|---|---|
| View store | ✅ |
| Edit store | ❌ |
| Create / edit / delete products | ✅ |
| View orders | ✅ |
| Update order status | ✅ |
| Manage promotions | ✅ |
| Edit user profile | ✅ own only |
| Payment gateway access | ❌ |

---

## User Account Setup Per Store Owner

### Step-by-Step: Creating a Store Owner Account

#### 1. Create the Drupal User
Navigate to `/admin/people/create`

| Field | Value |
|---|---|
| Username | `firstname_storename` (e.g. `jane_woolcraft`) |
| Email | Store owner's business email |
| Password | Auto-generate and email to owner |
| Status | Active |
| Roles | ✅ Store Owner |
| Notify user | ✅ Send welcome email with login link |

#### 2. Create the Commerce Store
Navigate to `/admin/commerce/config/stores/add`

| Field | Value |
|---|---|
| Store Name | Owner's shop name |
| Email | Store contact email |
| Default Currency | USD (or appropriate) |
| Address | Store/business address |
| Owner | Set to the user created in Step 1 |
| Store Type | `online` (or custom type if configured) |
| Billing Countries | Restrict as needed |
| Default | ❌ (only one store can be global default) |

#### 3. Assign Store to User Profile (optional field)
If a `field_store` entity reference field is added to the user profile, link the store here for easy lookup.

#### 4. Communicate Credentials
Send the store owner:
- Login URL
- Their username
- Temporary password (force reset on first login)
- Link to their store dashboard

---

## Enforcing Store Isolation (Multi-Store Access Control)

By default, Drupal Commerce's permissions are broad. Additional modules and configuration are needed to strictly isolate owners from each other's data.

### Recommended Modules for Access Control

| Module | Purpose |
|---|---|
| **Commerce Store Extrafield** | Adds store ownership UI helpers |
| **Entity Access Policies** (or **Domain Access**) | Enforce entity-level ownership rules |
| **Views + Contextual Filters** | Show only own-store data in admin views |
| **Rules** | Automate store assignment on product create |
| **Masquerade** | Allows admins to log in as store owners for support |
| **Commerce Marketplace** | Full multi-vendor suite (if needed) |

### Views Configuration for Store Owners
Override the default Commerce admin Views so store owners only see their own data:

- `/admin/commerce/products` → Add contextual filter: `Store / Owner UID = Current logged-in user`
- `/admin/commerce/orders` → Add contextual filter: `Store ID = Stores owned by current user`
- `/admin/commerce/promotions` → Same pattern

### Auto-Assign Store on Product Creation
Use the **Rules** module or a custom `hook_entity_presave()` to automatically set the `stores` field on a new product to the current user's store — preventing owners from accidentally or intentionally listing products in another store.

```php
// Example: hook_entity_presave() in a custom module
function mymodule_entity_presave(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'commerce_product') {
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasRole('administrator')) {
      $store = mymodule_get_store_for_user($current_user->id());
      if ($store) {
        $entity->set('stores', [$store->id()]);
      }
    }
  }
}
```

---

## User Profile Fields (Store Owner)

Add these fields to the **User** entity type at `/admin/config/people/accounts/fields`:

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Store | `field_store` | Entity reference (Commerce Store) | Links user ↔ store |
| Display / Shop Name | `field_shop_name` | Text (plain) | Public-facing seller name |
| Shop Logo | `field_shop_logo` | Image | Used on store pages |
| Bio / About | `field_bio` | Text (formatted, long) | Seller about section |
| Contact Phone | `field_phone` | Telephone | Optional |
| Social Links | `field_social_links` | Link (multi) | Instagram, Etsy, etc. |
| Payout / Payment Info | `field_payout_info` | Text (plain, encrypted) | For platform commission payouts |
| Onboarding Complete | `field_onboarding_complete` | Boolean | Tracks setup progress |
| Account Notes (admin only) | `field_admin_notes` | Text (long) | Internal notes, hidden from owner |

---

## Onboarding Workflow

### Recommended Flow for New Store Owners

```
1. Admin creates Drupal user (or owner self-registers if open)
        ↓
2. Admin assigns "Store Owner" role
        ↓
3. Admin creates Commerce Store, assigns owner
        ↓
4. Owner receives welcome email with credentials
        ↓
5. Owner logs in → forced password reset
        ↓
6. Owner completes profile (logo, bio, payout info)
        ↓
7. Owner adds products
        ↓
8. Admin reviews & publishes store (if approval workflow in place)
        ↓
9. Store goes live
```

### Optional: Self-Registration
If store owners can apply and self-register:
- Configure `/admin/config/people/accounts` → "Visitors can create accounts but admin approval is required"
- Use **Profile** module to collect store information during registration
- Use **Commerce Signup** or custom form to capture store intent
- Admin reviews application and assigns Store Owner role + creates store

---

## Security Considerations

| Concern | Mitigation |
|---|---|
| Owner sees other stores' orders | Restrict via Views contextual filters + entity access |
| Owner edits another user's products | `edit own products` only (not `edit any`) |
| Owner accesses admin paths | Do not grant `access administration pages` broadly |
| Brute force / account takeover | Enable **Flood Control** + two-factor auth (TFA module) |
| Owner deletes their store | Revoke delete store permission; require admin |
| Sensitive payout data exposure | Encrypt field; admin-only field visibility |
| Password reset abuse | Configure secure token expiry in account settings |

---

## Recommended Modules Summary

| Module | Drupal.org Path | Priority |
|---|---|---|
| Drupal Commerce | `drupal.org/project/commerce` | Required |
| Commerce Store Extrafield | `drupal.org/project/commerce_store_extrafield` | Recommended |
| Commerce Marketplace | `drupal.org/project/commerce_marketplace` | If full multi-vendor |
| Profile | `drupal.org/project/profile` | Recommended |
| Rules | `drupal.org/project/rules` | Recommended |
| Masquerade | `drupal.org/project/masquerade` | Recommended |
| TFA (Two Factor Auth) | `drupal.org/project/tfa` | Recommended |
| Flood Control | `drupal.org/project/flood_control` | Recommended |
| Views Bulk Operations | `drupal.org/project/views_bulk_operations` | Helpful |
| Content Access | `drupal.org/project/content_access` | Optional |
