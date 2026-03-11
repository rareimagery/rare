# Next.js Product Detail Pages

## Overview

Each product in Drupal Commerce has a dedicated detail page on the Next.js frontend. Pages are statically generated at build time via `getStaticProps` / `generateStaticParams` (App Router) and revalidated on demand when products are updated in Drupal. The page layout adapts based on product type — **Clothing**, **Digital Download**, or **Craft** — while sharing a common core structure.

---

## Routing

### URL Structure
```
/products/[slug]
```
Example paths:
- `/products/indigo-linen-wrap-dress`
- `/products/watercolor-floral-procreate-brush-set`
- `/products/hand-thrown-ceramic-mug-sage`

### Data Fetching Strategy

| Method | Use Case |
|---|---|
| `generateStaticParams` | Pre-render all product pages at build |
| `revalidateTag` / On-Demand ISR | Re-build a product page when updated in Drupal |
| `fetch` with `cache: 'no-store'` | Preview / draft mode in Drupal |

```ts
// app/products/[slug]/page.tsx
export async function generateStaticParams() {
  const products = await fetchAllProductSlugs(); // from Drupal JSON:API
  return products.map((p) => ({ slug: p.slug }));
}

export default async function ProductPage({ params }) {
  const product = await fetchProductBySlug(params.slug);
  if (!product) notFound();
  return <ProductDetail product={product} />;
}
```

---

## Page Layout — Core Structure

Every product detail page is composed of the following sections, in order:

```
┌─────────────────────────────────────────────────────┐
│  Breadcrumb Navigation                              │
├──────────────────────┬──────────────────────────────┤
│                      │  Product Title               │
│   Image Gallery      │  Store / Seller Info         │
│   (Primary + Thumbs) │  Price Block                 │
│                      │  Variant Selector            │
│                      │  Add to Cart / Buy Now       │
│                      │  Trust Badges                │
│                      │  Short Description           │
│                      │  Delivery / Shipping Info    │
├──────────────────────┴──────────────────────────────┤
│  Tab Panel: Details | Specifications | Shipping     │
├─────────────────────────────────────────────────────┤
│  Product Reviews Section                            │
├─────────────────────────────────────────────────────┤
│  Related Products Carousel                          │
└─────────────────────────────────────────────────────┘
```

---

## Section Breakdown

### 1. Breadcrumb Navigation
```
Home > [Category] > [Subcategory] > Product Name
```
- Generated from product's taxonomy terms.
- Schema.org `BreadcrumbList` structured data injected via `<script type="application/ld+json">`.

---

### 2. Image Gallery

| Feature | Details |
|---|---|
| Primary image | Large display image, 800×800px minimum |
| Thumbnail strip | Horizontal scroll on mobile, vertical on desktop |
| Zoom | Click/tap to zoom (react-medium-image-zoom or similar) |
| Lightbox | Full-screen gallery on click |
| Video support | Embed product video if provided (YouTube / Vimeo / mp4) |
| Alt text | Pulled from Drupal image alt field for accessibility |
| Variation swap | Switching color/variant updates the primary image |
| Lazy loading | All images below-the-fold use `loading="lazy"` |
| Formats | Serve WebP with JPEG fallback via Next.js `<Image>` |

**Component:** `<ProductGallery images={product.images} activeVariantImage={selected.image} />`

---

### 3. Product Header Block

| Element | Details |
|---|---|
| Product Title | `h1`, pulled from `title` field |
| Store / Seller Name | Linked to store page `/stores/[store-slug]` |
| Seller Avatar | Small avatar + verified badge if applicable |
| Star Rating Summary | Average stars + total review count (links to reviews) |
| SKU | Displayed for reference, updates per variant |
| Availability Badge | "In Stock", "Low Stock (3 left)", "Made to Order", "Sold Out" |

---

### 4. Price Block

| State | Display |
|---|---|
| Regular price | `$24.00` |
| On sale | ~~`$24.00`~~ → `$18.00` + "SAVE 25%" badge |
| Price range (variants differ) | `From $18.00` |
| Digital / no shipping | "Instant Download" badge beside price |

---

### 5. Variant Selector

Rendered differently per product type:

#### Clothing
- **Color picker:** Swatch circles (image or CSS color), with label tooltip on hover
- **Size selector:** Button grid (XS / S / M / L / XL) with out-of-stock sizes crossed out
- **Size guide link:** Modal or drawer with measurement table
- Selecting a variant updates: price, stock status, SKU, primary image

#### Digital Downloads
- **License tier selector:** Radio cards (e.g. "Personal Use — $12" / "Commercial Use — $29")
- Each card shows what is/isn't included per license

#### Crafts
- **Color / Finish:** Swatch or button selector
- **Size option:** Button grid if applicable
- **Material:** Dropdown or button selector
- **Customization field:** If `field_customizable = true`, show a free-text input: *"Add your personalization note"*

---

### 6. Add to Cart / Purchase Block

| Element | Details |
|---|---|
| Quantity selector | `−` / input / `+`, max capped at stock |
| Add to Cart button | Primary CTA, triggers cart drawer |
| Buy Now button | Secondary CTA, skips to checkout |
| Save / Wishlist | Heart icon, saves to user's wishlist |
| Share button | Copy link, native share API |
| Digital: no quantity | Quantity selector hidden for downloads |

---

### 7. Trust Badges

Small icon + text row beneath the CTA. Shown contextually by product type:

| Badge | Clothing | Digital | Crafts |
|---|---|---|---|
| Secure Checkout | ✅ | ✅ | ✅ |
| Free Returns (if policy) | ✅ | ❌ | ✅ |
| Instant Download | ❌ | ✅ | ❌ |
| Handmade | ❌ | ❌ | ✅ |
| Made to Order | ❌ | ❌ | Conditional |
| Satisfaction Guarantee | ✅ | ✅ | ✅ |

---

### 8. Short Description
- Plain text summary, 1–3 sentences.
- Displayed above the fold in the buy block.
- Full description lives in the Details tab below.

---

### 9. Delivery / Shipping Info Block

Contextual snippet shown before tabs:

- **Clothing / Crafts:** Estimated delivery window, shipping method, "Ships from [location]"
- **Digital Downloads:** "Available immediately after purchase. Download link sent to your email."
- **Crafts (Made to Order):** "Production time: 3–5 business days before shipping"

---

## Tab Panel — Detailed Information

A tabbed interface below the buy block with the following tabs. Tabs shown/hidden based on product type.

### Tab: Details (All Types)

Full formatted description from Drupal's `body` field. May include:
- Bullet point feature list
- Embedded images or diagrams
- Craft story / maker's note

---

### Tab: Specifications

#### Clothing Specifications
| Label | Value |
|---|---|
| Material | 100% Organic Cotton |
| Fit | Relaxed / Slim / Oversized |
| Care Instructions | Machine wash cold, tumble dry low |
| Country of Origin | Made in Portugal |
| Sustainability | GOTS Certified Organic |
| Available Sizes | XS–XXL |
| Weight | 180 GSM |

#### Digital Download Specifications
| Label | Value |
|---|---|
| File Format | PDF, SVG, PNG |
| File Size | 24 MB |
| Dimensions | 3000×3000px at 300 DPI |
| Color Mode | CMYK + RGB versions included |
| Software Required | Adobe Illustrator CC 2020+ |
| Language | English |
| Version | v2.1 (updated March 2025) |
| License | Commercial Use |
| Download Limit | Unlimited |
| Link Expiry | Never |

#### Crafts Specifications
| Label | Value |
|---|---|
| Dimensions | 4in × 4in × 3in |
| Materials | Stoneware clay, food-safe glaze |
| Technique | Wheel-thrown, kiln-fired |
| Finish | Matte glaze |
| Care | Dishwasher safe, not microwave safe |
| Handmade | Yes — each piece is unique |
| Production Time | 4–6 business days |
| Made to Order | Yes |
| Safety Info | Food safe, lead-free glaze |

---

### Tab: Shipping & Returns

**Physical Products (Clothing & Crafts):**
- Shipping methods, carriers, estimated times
- Shipping cost (free threshold if applicable)
- International shipping availability
- Returns policy and window (e.g. "30-day returns on unworn items")
- Exchange process
- Non-returnable items notice (custom/personalized items)

**Digital Downloads:**
- No shipping — instant delivery
- Refund policy (e.g. "Due to the digital nature, all sales are final")
- Support contact for access issues
- Re-download instructions

---

### Tab: License *(Digital Downloads only)*

Full license terms rendered from `field_license_details`:
- What you can do (personal use, print runs, resale limits)
- What you cannot do (redistribute, resell as-is, claim as own)
- Attribution requirements if any
- License tier comparison table (Personal vs Commercial vs Extended)

---

### Tab: Size Guide *(Clothing only)*

Measurement table with:
- Size → Chest / Waist / Hip / Length columns
- Switch between inches and centimeters
- "How to measure" illustration or diagram

---

### Tab: Customization *(Crafts — if applicable)*

- What can be personalized
- How to provide instructions (text field or order note)
- Turnaround implications for custom work
- Preview limitations (custom items are final sale)

---

## Product Reviews Section

### Review Summary Block

```
┌────────────────────────────────────────────────────┐
│  ★★★★☆  4.3 out of 5                              │
│  Based on 48 reviews                               │
│  ─────────────────────────────────────────────    │
│  5 ★  ████████████████░░  38                      │
│  4 ★  ████████░░░░░░░░░░  7                       │
│  3 ★  ██░░░░░░░░░░░░░░░░  2                       │
│  2 ★  ░░░░░░░░░░░░░░░░░░  1                       │
│  1 ★  ░░░░░░░░░░░░░░░░░░  0                       │
│                           [Write a Review]         │
└────────────────────────────────────────────────────┘
```

### Review Filter & Sort Bar

| Filter | Options |
|---|---|
| Star filter | All, 5★, 4★, 3★, 2★, 1★ |
| Sort | Most Recent, Most Helpful, Highest Rated, Lowest Rated |
| Media filter | "With Photos" toggle |
| Verified only | Toggle: Verified Purchases only |

### Individual Review Card

| Element | Details |
|---|---|
| Reviewer name | First name + last initial (e.g. "Sarah M.") |
| Verified badge | "Verified Purchase" if order confirmed |
| Star rating | 1–5 stars |
| Review title | Bold headline |
| Review body | Full text, expandable if long |
| Review photos | Thumbnail grid, lightbox on click |
| Variant purchased | e.g. "Color: Navy / Size: M" |
| Helpful votes | "Was this helpful? 👍 12" |
| Date | Relative (e.g. "2 months ago") |
| Seller response | Collapsible reply from store owner |

### Write a Review Form

Shown to logged-in users who have purchased the product. Fields:

| Field | Type | Required |
|---|---|---|
| Overall Rating | Star selector (1–5) | ✅ |
| Review Title | Text input | ✅ |
| Review Body | Textarea (min 20 chars) | ✅ |
| Photos | File upload (up to 6 images) | ❌ |
| Recommend this product? | Yes / No toggle | ❌ |
| Submit | Button — posts to Drupal via REST | |

> Non-logged-in users see: *"Log in or create an account to leave a review."*

### Review Schema
Inject `schema.org/Review` and `schema.org/AggregateRating` structured data for Google rich results.

---

## Related Products Carousel

- Heading: *"You might also like"* or *"More from this shop"*
- Source: `field_related_products` from Drupal, or fallback to same-category products via API query
- Display: Horizontal scroll carousel, 4 cards on desktop / 2 on mobile
- Each card: image, title, price, star rating, Add to Cart button
- Lazy-loaded below the fold

---

## SEO & Structured Data

### Meta Tags (per product)

| Tag | Source |
|---|---|
| `<title>` | `field_seo_title` or product title + store name |
| `meta description` | `field_seo_description` or short description |
| `og:title` | Product title |
| `og:description` | Short description |
| `og:image` | First product image |
| `og:type` | `product` |
| `canonical` | `/products/[slug]` |
| `robots` | `index, follow` (noindex if draft) |

### Structured Data Schemas

- **`schema.org/Product`** — name, description, image, brand, SKU, offers
- **`schema.org/Offer`** — price, currency, availability, seller
- **`schema.org/AggregateRating`** — ratingValue, reviewCount
- **`schema.org/Review`** — per review, author, datePublished, reviewRating
- **`schema.org/BreadcrumbList`** — navigation path

---

## Accessibility Requirements

| Requirement | Implementation |
|---|---|
| Keyboard navigation | All interactive elements focusable, tab order logical |
| Screen reader labels | `aria-label` on icon-only buttons (wishlist, share) |
| Image alt text | All images have descriptive alt from Drupal |
| Color contrast | WCAG AA minimum (4.5:1) on all text |
| Focus indicators | Visible focus rings, not suppressed |
| Zoom support | Page usable at 200% browser zoom |
| ARIA live regions | Stock status and cart confirmation announced |

---

## Performance Targets

| Metric | Target |
|---|---|
| LCP (Largest Contentful Paint) | < 2.5s |
| CLS (Cumulative Layout Shift) | < 0.1 |
| INP (Interaction to Next Paint) | < 200ms |
| Image optimization | Next.js `<Image>` with WebP, correct `sizes` |
| Above-the-fold CSS | Inlined or critical CSS extracted |
| Font loading | `font-display: swap`, preload key weights |
| Review pagination | Load 10 reviews initially, paginate or infinite scroll |
| Tab content | Lazy-load tab content not in initial viewport |

---

## Data Flow: Drupal → Next.js

```
Drupal Commerce (JSON:API)
        │
        ▼
  /jsonapi/commerce_product/[type]/[uuid]
        │
        ├── product fields (title, body, price, images...)
        ├── product variations (SKU, price, attributes...)
        ├── store reference → store name, logo, slug
        ├── taxonomy terms → breadcrumbs, related products
        └── reviews (custom REST endpoint or Drupal module)
        │
        ▼
  Next.js generateStaticParams / fetch
        │
        ├── Transform → normalize product shape
        ├── Pass to <ProductDetail /> component tree
        └── Inject structured data in <head>
```

### Key API Endpoints

| Data | Endpoint |
|---|---|
| Product by slug | `GET /jsonapi/commerce_product/clothing?filter[path.alias]=/products/[slug]` |
| Product variations | `GET /jsonapi/commerce_product_variation/clothing?filter[product_id.id]=[uuid]` |
| Product reviews | `GET /api/reviews?product=[uuid]&page=[n]` |
| Related products | `GET /jsonapi/commerce_product/clothing?filter[field_categories.id]=[tid]&page[limit]=8` |
| Submit review | `POST /api/reviews` (authenticated) |

---

## Component Tree

```
<ProductPage>
  ├── <Breadcrumb />
  ├── <ProductLayout>
  │     ├── <ProductGallery />
  │     └── <ProductBuyBlock>
  │           ├── <ProductHeader />        ← title, seller, rating
  │           ├── <PriceBlock />
  │           ├── <VariantSelector />      ← type-specific
  │           ├── <QuantitySelector />
  │           ├── <AddToCartButton />
  │           ├── <TrustBadges />
  │           ├── <ShortDescription />
  │           └── <DeliveryInfo />
  ├── <ProductTabs>
  │     ├── <TabDetails />
  │     ├── <TabSpecifications />         ← type-specific fields
  │     ├── <TabShippingReturns />
  │     ├── <TabLicense />               ← digital only
  │     ├── <TabSizeGuide />             ← clothing only
  │     └── <TabCustomization />         ← crafts only
  ├── <ReviewsSection>
  │     ├── <ReviewSummary />
  │     ├── <ReviewFilters />
  │     ├── <ReviewList />
  │     │     └── <ReviewCard /> × n
  │     └── <WriteReviewForm />
  └── <RelatedProductsCarousel />
```
