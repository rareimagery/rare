# Drupal Product Types & Attributes

## Overview

This document defines the three product types for the Drupal Commerce site, their shared base fields, and type-specific attributes. Each product type should be created as a separate **Product Type** in Drupal Commerce, with fields added via the Field UI.

---

## Base Fields (All Product Types)

These fields apply to every product type.

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Title | `title` | Text (plain) | Default Drupal Commerce field |
| SKU | `sku` | Text (plain) | Unique per variation |
| Price | `price` | Price | Drupal Commerce price field |
| Status | `status` | Boolean | Published / Unpublished |
| Description | `body` | Text (formatted, long) | Full product description |
| Short Description | `field_short_description` | Text (plain, long) | Used for cards/previews |
| Product Images | `field_images` | Image | Multiple values allowed |
| Categories | `field_categories` | Entity reference (Taxonomy) | Shared vocabulary: Product Categories |
| Tags | `field_tags` | Entity reference (Taxonomy) | Shared vocabulary: Tags |
| SEO Title | `field_seo_title` | Text (plain) | Meta title override |
| SEO Description | `field_seo_description` | Text (plain, long) | Meta description override |
| Featured | `field_featured` | Boolean | Highlight on homepage/listings |
| Related Products | `field_related_products` | Entity reference (Product) | Cross-sell / upsell |

---

## Product Type 1: Clothing

**Machine name:** `clothing`
**Description:** Physical apparel items that require size, color, and shipping configuration.

### Clothing — Product-Level Fields

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Brand | `field_brand` | Text (plain) | e.g. "Handmade by Jane" |
| Gender | `field_gender` | List (text) | Unisex, Men's, Women's, Kids' |
| Care Instructions | `field_care_instructions` | Text (formatted, long) | Wash/dry/iron guidance |
| Material / Fabric | `field_material` | Text (plain, long) | e.g. "100% organic cotton" |
| Country of Origin | `field_country_of_origin` | Text (plain) | e.g. "Made in USA" |
| Sustainability Info | `field_sustainability` | Text (formatted, long) | Eco/ethical certifications |
| Size Guide | `field_size_guide` | Text (formatted, long) | Or link to a size guide node |
| Weight (for shipping) | `field_shipping_weight` | Decimal (Number) | In oz or grams |
| Shipping Class | `field_shipping_class` | List (text) | Standard, Expedited, etc. |

### Clothing — Product Variation Fields

Variations represent each purchasable combination (e.g. Blue / Large).

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Size | `field_size` | List (text) | XS, S, M, L, XL, XXL, or custom |
| Color | `field_color` | List (text) | Color name options |
| Color Swatch | `field_color_swatch` | Image | Small swatch image per color |
| Variation Image | `field_variation_image` | Image | Photo specific to this variant |
| Stock / Inventory | `field_stock` | Integer (Number) | Units in stock |
| On Sale | `field_on_sale` | Boolean | Triggers sale badge |
| Sale Price | `field_sale_price` | Price | Discounted price if on sale |
| Dimensions | `field_dimensions` | Text (plain) | e.g. "Chest: 20in, Length: 28in" |

### Clothing — Recommended Taxonomies

- `clothing_size` — XS, S, M, L, XL, XXL, One Size
- `clothing_color` — standard color list
- `clothing_type` — T-Shirt, Hoodie, Dress, Pants, Accessory, etc.
- `clothing_gender` — Men's, Women's, Unisex, Kids'

---

## Product Type 2: Digital Downloads

**Machine name:** `digital_download`
**Description:** Intangible products delivered digitally. No shipping required. License and file management are key.

### Digital Downloads — Product-Level Fields

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| File Format(s) | `field_file_formats` | List (text), multi | PDF, PNG, SVG, MP3, ZIP, etc. |
| File Size | `field_file_size` | Text (plain) | e.g. "24 MB" — informational |
| License Type | `field_license_type` | List (text) | Personal, Commercial, Extended |
| License Details | `field_license_details` | Text (formatted, long) | Full license terms |
| Instant Download | `field_instant_download` | Boolean | Always true — for display badge |
| Preview Image(s) | `field_preview_images` | Image | Mockup/preview, multiple |
| Preview File | `field_preview_file` | File | Low-res or watermarked sample |
| Software Required | `field_software_required` | Text (plain, long) | e.g. "Adobe Illustrator CC 2020+" |
| Dimensions / Resolution | `field_dimensions_resolution` | Text (plain) | e.g. "3000x3000px at 300dpi" |
| Page Count | `field_page_count` | Integer (Number) | For PDFs/eBooks |
| Language | `field_language` | List (text) | English, Spanish, French, etc. |
| Version | `field_version` | Text (plain) | e.g. "v2.1" for updated files |
| Changelog | `field_changelog` | Text (formatted, long) | Update history |

### Digital Downloads — Product Variation Fields

Variations represent different tiers, bundles, or license levels.

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| License Tier | `field_license_tier` | List (text) | Personal Use, Commercial Use |
| Downloadable File | `field_downloadable_file` | File | Protected; delivered post-purchase |
| Download Limit | `field_download_limit` | Integer | Max allowed downloads (0 = unlimited) |
| Download Expiry (days) | `field_download_expiry` | Integer | Days before link expires |

### Digital Downloads — Recommended Taxonomies

- `download_type` — Pattern, Template, Printable, eBook, Music, Font, Graphic, Course
- `download_format` — PDF, PNG, SVG, ZIP, MP3, MP4, EPUB
- `download_license` — Personal Use, Commercial Use, Extended License
- `download_software` — Procreate, Illustrator, Photoshop, Canva, etc.

---

## Product Type 3: Crafts

**Machine name:** `crafts`
**Description:** Handmade physical items. One-of-a-kind or limited quantity, requiring production time and shipping configuration.

### Crafts — Product-Level Fields

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Handmade | `field_handmade` | Boolean | Badge: "Handmade" |
| Made to Order | `field_made_to_order` | Boolean | Triggers production time notice |
| Production Time | `field_production_time` | Text (plain) | e.g. "3–5 business days" |
| Materials Used | `field_materials_used` | Text (formatted, long) | Full list of materials/supplies |
| Dimensions | `field_dimensions` | Text (plain) | e.g. "5in x 7in x 2in" |
| Weight (for shipping) | `field_shipping_weight` | Decimal (Number) | In oz or grams |
| Shipping Class | `field_shipping_class` | List (text) | Standard, Fragile, Oversized |
| Gift Wrapping Available | `field_gift_wrap` | Boolean | Offer gift wrap add-on |
| Customizable | `field_customizable` | Boolean | Can buyer request personalization |
| Customization Details | `field_customization_details` | Text (formatted, long) | What can be customized & how |
| Care Instructions | `field_care_instructions` | Text (formatted, long) | Cleaning/storage guidance |
| Artist / Maker | `field_maker` | Text (plain) | Name of the artisan |
| Craft Technique | `field_craft_technique` | Entity reference (Taxonomy) | e.g. Macramé, Embroidery |
| Occasion | `field_occasion` | Entity reference (Taxonomy) | Wedding, Birthday, Holiday, etc. |
| Safety Information | `field_safety_info` | Text (formatted, long) | Choking hazard warnings, etc. |

### Crafts — Product Variation Fields

Variations represent different sizes, colors, or material choices.

| Field Label | Machine Name | Field Type | Notes |
|---|---|---|---|
| Color / Finish | `field_color_finish` | List (text) | Color or finish option |
| Size / Dimensions Option | `field_size_option` | List (text) | Small, Medium, Large, Custom |
| Material Option | `field_material_option` | List (text) | Wood, Ceramic, Fabric, etc. |
| Variation Image | `field_variation_image` | Image | Photo of this specific variant |
| Stock / Inventory | `field_stock` | Integer (Number) | Units available |
| On Sale | `field_on_sale` | Boolean | Triggers sale badge |
| Sale Price | `field_sale_price` | Price | Discounted price if on sale |

### Crafts — Recommended Taxonomies

- `craft_type` — Jewelry, Home Décor, Candles, Pottery, Fiber Arts, Paper Goods, Woodworking, etc.
- `craft_technique` — Macramé, Embroidery, Crochet, Resin, Hand-poured, Wheel-thrown, etc.
- `craft_material` — Wood, Ceramic, Glass, Fabric, Metal, Resin, Paper
- `craft_occasion` — Birthday, Wedding, Holiday, Everyday, Baby Shower, Housewarming
- `craft_style` — Boho, Minimalist, Rustic, Modern, Cottagecore, Traditional

---

## Drupal Commerce Configuration Notes

### Shipping
- Clothing and Crafts should have **Shipment Types** configured with appropriate weight and dimension fields.
- Digital Downloads should be set to **"No shipping required"** at the product type level.

### Taxes
- Digital Downloads may require different tax rates depending on jurisdiction — configure separately in the Tax module.
- Physical products (Clothing, Crafts) use standard physical goods tax rates.

### Stock Management
- Use the **Commerce Stock** module for Clothing and Crafts variations.
- Digital Downloads do not require stock management (unlimited by nature) — use download limit fields instead.

### File Delivery (Digital Downloads)
- Use the **Commerce File** module to handle secure file delivery after purchase.
- Files should be stored in a protected directory outside the public file system.

### Suggested Modules
| Module | Purpose |
|---|---|
| Drupal Commerce | Core e-commerce |
| Commerce Stock | Inventory management |
| Commerce File | Digital file delivery |
| Commerce Shipping | Shipping rate calculation |
| Commerce Tax | Tax handling |
| Metatag | SEO fields |
| Pathauto | Auto URL aliases |
| Search API + Facets | Filterable product listings |
| Color Field | Swatch color pickers |
