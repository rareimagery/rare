# Xai3 Theme — rareimagery.net Subscriber Store Page
**Version 1.0 – March 2026**  
**Strategy:** Xai2's center-column layout + cached Drupal data + Xai1's rich design system, fonts, store panel quality, and suggested stores widget. One file. Ships today.

---

## Architecture Decisions (Why Xai3 Wins)

| Feature | Source | Reason |
|---|---|---|
| Layout: single 600px center column | Xai2 | Matches X feed feel, mobile-first, matches your spec |
| Store section: center feed, below tabs | Xai2 | Your exact words: "below in the center" |
| Data: 100% Drupal JSON:API cached | Xai2 | No rate limits, fast, compliant |
| Single file | Xai2 | Ships in minutes |
| Design tokens: CSS custom properties | Xai1 | Consistent, maintainable, themeable |
| Typography: Sora + DM Sans + JetBrains | Xai1 | Premium X-native feel |
| Loading skeletons + shimmer | Xai1 | Professional UX |
| Rich store panel with stats | Xai1 | Better conversion |
| "More from RareImagery" widget | Xai1 | Cross-sells other subscribers |
| Verified badge + follower counts | Xai1 | Social proof |

---

## Drupal JSON:API Data Requirements

The page expects these fields populated during subscriber onboarding:

```
/jsonapi/node/subscriber_store?filter[field_x_username]=@handle

Subscriber Node fields:
  field_x_username        → string  (e.g. "budhound")
  field_x_display_name    → string  (e.g. "Bud Hound 🐾")
  field_x_bio             → string  (plain text, max 160 chars)
  field_x_pfp             → image   (profile photo URL)
  field_x_banner          → image   (1500×500 header image URL)
  field_x_followers       → integer
  field_x_following       → integer
  field_x_verified        → boolean
  field_x_join_date       → date
  field_top_posts         → array   (last 5 cached post objects)
    └── each post:
          text            → string
          likes           → integer
          reposts         → integer
          replies         → integer
          timestamp       → ISO date string
          post_url        → string

/jsonapi/commerce_product/default?filter[field_store_owner]=@subscriber_id

Product fields (standard Drupal Commerce):
  title                   → string
  field_price.number      → decimal
  field_price.currency_code → string
  field_media             → image (via ?include=field_media.field_media_image)
  field_product_description → text
  field_sku               → string
  field_stock             → integer
```

---

## Next.js Component: `pages/store/[handle].jsx`

```jsx
// pages/store/[handle].jsx
// Xai3 Theme — RareImagery Subscriber Store
// One file. Drupal JSON:API cached data. No live X API calls.

import Head from 'next/head'
import { useState } from 'react'

// ─── Drupal fetch helpers ──────────────────────────────────────────────────

const BASE = process.env.DRUPAL_API_URL

async function fetchSubscriber(handle) {
  const url = `${BASE}/jsonapi/node/subscriber_store?filter[field_x_username]=${handle}&include=field_x_pfp,field_x_banner`
  const res = await fetch(url)
  if (!res.ok) return null
  const json = await res.json()
  const node = json.data?.[0]
  if (!node) return null
  const included = json.included ?? []

  const pfpFile = included.find(i => i.id === node.relationships.field_x_pfp?.data?.id)
  const bannerFile = included.find(i => i.id === node.relationships.field_x_banner?.data?.id)

  return {
    handle: node.attributes.field_x_username,
    displayName: node.attributes.field_x_display_name,
    bio: node.attributes.field_x_bio,
    pfp: pfpFile?.attributes?.uri?.url ? `${BASE}${pfpFile.attributes.uri.url}` : '/default-pfp.jpg',
    banner: bannerFile?.attributes?.uri?.url ? `${BASE}${bannerFile.attributes.uri.url}` : null,
    followers: node.attributes.field_x_followers ?? 0,
    following: node.attributes.field_x_following ?? 0,
    verified: node.attributes.field_x_verified ?? false,
    joinDate: node.attributes.field_x_join_date,
    topPosts: node.attributes.field_top_posts ?? [],
    nodeId: node.id,
  }
}

async function fetchProducts(subscriberId) {
  const url = `${BASE}/jsonapi/commerce_product/default?filter[field_store_owner]=${subscriberId}&include=field_media.field_media_image`
  const res = await fetch(url)
  if (!res.ok) return []
  const json = await res.json()
  const included = json.included ?? []

  return (json.data ?? []).map(p => {
    const mediaRel = p.relationships?.field_media?.data
    const mediaNode = included.find(i => i.id === mediaRel?.id)
    const imgRel = mediaNode?.relationships?.field_media_image?.data
    const imgFile = included.find(i => i.id === imgRel?.id)
    return {
      id: p.id,
      title: p.attributes.title,
      price: p.attributes.field_price?.number ?? '0',
      currency: p.attributes.field_price?.currency_code ?? 'USD',
      image: imgFile?.attributes?.uri?.url ? `${BASE}${imgFile.attributes.uri.url}` : null,
      description: p.attributes.field_product_description?.value ?? '',
      sku: p.attributes.field_sku,
    }
  })
}

async function fetchSuggestedStores(excludeHandle) {
  const url = `${BASE}/jsonapi/node/subscriber_store?filter[field_x_username][operator]=<>${excludeHandle}&include=field_x_pfp&page[limit]=4`
  const res = await fetch(url)
  if (!res.ok) return []
  const json = await res.json()
  const included = json.included ?? []
  return (json.data ?? []).map(s => {
    const pfpFile = included.find(i => i.id === s.relationships.field_x_pfp?.data?.id)
    return {
      handle: s.attributes.field_x_username,
      displayName: s.attributes.field_x_display_name,
      pfp: pfpFile?.attributes?.uri?.url ? `${BASE}${pfpFile.attributes.uri.url}` : '/default-pfp.jpg',
      followers: s.attributes.field_x_followers ?? 0,
    }
  })
}

// ─── Static generation ─────────────────────────────────────────────────────

export async function getStaticProps({ params }) {
  const subscriber = await fetchSubscriber(params.handle)
  if (!subscriber) return { notFound: true }

  const [products, suggested] = await Promise.all([
    fetchProducts(subscriber.nodeId),
    fetchSuggestedStores(params.handle),
  ])

  return {
    props: { subscriber, products, suggested },
    revalidate: 60,
  }
}

export async function getStaticPaths() {
  const url = `${BASE}/jsonapi/node/subscriber_store?fields[node--subscriber_store]=field_x_username&page[limit]=100`
  const res = await fetch(url)
  const json = await res.json()
  const paths = (json.data ?? []).map(s => ({
    params: { handle: s.attributes.field_x_username }
  }))
  return { paths, fallback: 'blocking' }
}

// ─── Sub-components ────────────────────────────────────────────────────────

function VerifiedBadge() {
  return (
    <svg viewBox="0 0 24 24" style={{ width: 20, height: 20, display: 'inline', verticalAlign: 'middle' }}>
      <circle cx="12" cy="12" r="12" fill="#1D9BF0" />
      <path d="M9.5 16.5l-4-4 1.4-1.4 2.6 2.6 5.6-5.6 1.4 1.4z" fill="white" />
    </svg>
  )
}

function Skeleton({ width = '100%', height = 16, radius = 6, style = {} }) {
  return (
    <div style={{
      width, height,
      borderRadius: radius,
      background: 'linear-gradient(90deg, var(--skeleton-a) 25%, var(--skeleton-b) 50%, var(--skeleton-a) 75%)',
      backgroundSize: '200% 100%',
      animation: 'shimmer 1.5s infinite',
      ...style
    }} />
  )
}

function ProductCard({ product, onAddToCart }) {
  const [added, setAdded] = useState(false)
  const handleAdd = () => {
    onAddToCart(product)
    setAdded(true)
    setTimeout(() => setAdded(false), 1500)
  }
  return (
    <div className="xai3-product-card">
      <div className="xai3-product-img-wrap">
        {product.image
          ? <img src={product.image} alt={product.title} />
          : <div className="xai3-product-img-placeholder">🛍️</div>
        }
      </div>
      <div className="xai3-product-info">
        <div className="xai3-product-title">{product.title}</div>
        <div className="xai3-product-price">
          {new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: product.currency
          }).format(product.price)}
        </div>
        <button
          className={`xai3-add-btn ${added ? 'xai3-add-btn--added' : ''}`}
          onClick={handleAdd}
        >
          {added ? '✓ Added' : 'Add to cart'}
        </button>
      </div>
    </div>
  )
}

function PostCard({ post }) {
  const ago = (ts) => {
    const diff = (Date.now() - new Date(ts).getTime()) / 1000
    if (diff < 60) return `${Math.floor(diff)}s`
    if (diff < 3600) return `${Math.floor(diff / 60)}m`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h`
    return `${Math.floor(diff / 86400)}d`
  }
  return (
    <a href={post.post_url} target="_blank" rel="noopener noreferrer" className="xai3-post-card">
      <div className="xai3-post-text">{post.text}</div>
      <div className="xai3-post-stats">
        <span>💬 {post.replies?.toLocaleString()}</span>
        <span>🔁 {post.reposts?.toLocaleString()}</span>
        <span>❤️ {post.likes?.toLocaleString()}</span>
        <span className="xai3-post-age">{ago(post.timestamp)}</span>
      </div>
    </a>
  )
}

function SuggestedStore({ store }) {
  return (
    <a href={`/store/${store.handle}`} className="xai3-suggested-card">
      <img src={store.pfp} alt={store.displayName} className="xai3-suggested-pfp" />
      <div className="xai3-suggested-info">
        <div className="xai3-suggested-name">{store.displayName}</div>
        <div className="xai3-suggested-handle">@{store.handle}</div>
      </div>
      <button className="xai3-follow-btn">Visit</button>
    </a>
  )
}

// ─── Main page ─────────────────────────────────────────────────────────────

export default function StorePage({ subscriber, products, suggested }) {
  const [tab, setTab] = useState('store')
  const [cart, setCart] = useState([])
  const [cartOpen, setCartOpen] = useState(false)

  const addToCart = (product) => {
    setCart(c => {
      const existing = c.find(i => i.id === product.id)
      if (existing) return c.map(i => i.id === product.id ? { ...i, qty: i.qty + 1 } : i)
      return [...c, { ...product, qty: 1 }]
    })
  }

  const cartTotal = cart.reduce((sum, i) => sum + (parseFloat(i.price) * i.qty), 0)
  const cartCount = cart.reduce((sum, i) => sum + i.qty, 0)

  const joinYear = subscriber.joinDate
    ? new Date(subscriber.joinDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    : null

  return (
    <>
      <Head>
        <title>{subscriber.displayName} (@{subscriber.handle}) / X Store</title>
        <meta name="description" content={subscriber.bio} />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
      </Head>

      <style>{`
        /* ── Xai3 Design Tokens ── */
        :root {
          --bg:            #000000;
          --bg-elevated:   #0f0f0f;
          --bg-card:       #16181c;
          --bg-hover:      #1d1f23;
          --border:        #2f3336;
          --border-subtle: #1f2123;
          --text-primary:  #e7e9ea;
          --text-secondary:#71767b;
          --text-link:     #1d9bf0;
          --accent-blue:   #1d9bf0;
          --accent-blue-hover: #1a8cd8;
          --accent-green:  #00ba7c;
          --accent-gold:   #D4AF37;
          --accent-purple: #7B2D8E;
          --verified:      #1d9bf0;
          --skeleton-a:    #1a1a1a;
          --skeleton-b:    #2a2a2a;
          --font-display:  'Sora', sans-serif;
          --font-body:     'DM Sans', sans-serif;
          --font-mono:     'JetBrains Mono', monospace;
          --radius-sm:     4px;
          --radius-md:     12px;
          --radius-full:   9999px;
          --col-width:     600px;
          --transition:    0.15s ease;
        }

        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { background: var(--bg); color: var(--text-primary); font-family: var(--font-body); font-size: 15px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        button { cursor: pointer; border: none; background: none; font-family: var(--font-body); }

        /* ── Shimmer keyframe ── */
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Layout ── */
        .xai3-layout {
          min-height: 100vh;
          display: flex;
          justify-content: center;
        }
        .xai3-center {
          width: 100%;
          max-width: var(--col-width);
          border-left: 1px solid var(--border-subtle);
          border-right: 1px solid var(--border-subtle);
          animation: fadeUp 0.3s ease both;
        }

        /* ── Banner ── */
        .xai3-banner {
          width: 100%;
          height: 200px;
          background: linear-gradient(135deg, var(--accent-purple) 0%, #1a0a22 60%, #0d0d0d 100%);
          position: relative;
          overflow: hidden;
        }
        .xai3-banner img {
          width: 100%; height: 100%; object-fit: cover;
        }
        .xai3-banner-overlay {
          position: absolute; inset: 0;
          background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.4) 100%);
        }

        /* ── Profile section ── */
        .xai3-profile-section {
          padding: 0 16px 16px;
          border-bottom: 1px solid var(--border);
        }
        .xai3-avatar-row {
          display: flex;
          justify-content: space-between;
          align-items: flex-end;
          margin-top: -40px;
          margin-bottom: 12px;
        }
        .xai3-avatar {
          width: 80px; height: 80px;
          border-radius: 50%;
          border: 4px solid var(--bg);
          object-fit: cover;
          background: var(--bg-card);
        }
        .xai3-rare-badge {
          display: flex; align-items: center; gap: 6px;
          background: var(--accent-purple);
          color: #fff;
          font-family: var(--font-display);
          font-size: 11px;
          font-weight: 700;
          letter-spacing: 0.08em;
          text-transform: uppercase;
          padding: 6px 14px;
          border-radius: var(--radius-full);
        }
        .xai3-display-name {
          font-family: var(--font-display);
          font-size: 20px;
          font-weight: 700;
          color: var(--text-primary);
          display: flex; align-items: center; gap: 6px;
          margin-bottom: 2px;
        }
        .xai3-handle {
          font-size: 14px;
          color: var(--text-secondary);
          margin-bottom: 10px;
        }
        .xai3-bio {
          font-size: 15px;
          color: var(--text-primary);
          margin-bottom: 12px;
          line-height: 1.6;
        }
        .xai3-meta {
          display: flex; gap: 16px; flex-wrap: wrap;
          font-size: 13px; color: var(--text-secondary);
          margin-bottom: 12px;
        }
        .xai3-meta-item {
          display: flex; align-items: center; gap: 4px;
        }
        .xai3-stats {
          display: flex; gap: 20px;
          font-size: 14px;
        }
        .xai3-stat strong {
          color: var(--text-primary);
          font-family: var(--font-display);
          font-weight: 600;
        }
        .xai3-stat span {
          color: var(--text-secondary);
          margin-left: 4px;
        }

        /* ── Tabs ── */
        .xai3-tabs {
          display: flex;
          border-bottom: 1px solid var(--border);
          position: sticky; top: 0;
          background: rgba(0,0,0,0.85);
          backdrop-filter: blur(12px);
          z-index: 10;
        }
        .xai3-tab {
          flex: 1;
          padding: 16px 0;
          font-size: 14px;
          font-weight: 500;
          color: var(--text-secondary);
          text-align: center;
          border-bottom: 2px solid transparent;
          transition: color var(--transition), border-color var(--transition);
          cursor: pointer;
        }
        .xai3-tab:hover { color: var(--text-primary); background: var(--bg-hover); }
        .xai3-tab.active {
          color: var(--text-primary);
          border-bottom-color: var(--accent-blue);
          font-weight: 600;
        }

        /* ── Store panel ── */
        .xai3-store-header {
          padding: 16px 16px 8px;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .xai3-store-title {
          font-family: var(--font-display);
          font-size: 16px;
          font-weight: 700;
          color: var(--text-primary);
        }
        .xai3-store-stats {
          display: flex; gap: 16px;
          font-size: 12px; color: var(--text-secondary);
        }
        .xai3-store-stats strong {
          color: var(--accent-gold);
          font-family: var(--font-mono);
        }
        .xai3-products-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 1px;
          background: var(--border-subtle);
        }
        @media (max-width: 480px) {
          .xai3-products-grid { grid-template-columns: 1fr; }
        }
        .xai3-product-card {
          background: var(--bg);
          transition: background var(--transition);
        }
        .xai3-product-card:hover { background: var(--bg-hover); }
        .xai3-product-img-wrap {
          aspect-ratio: 1;
          overflow: hidden;
          background: var(--bg-card);
        }
        .xai3-product-img-wrap img {
          width: 100%; height: 100%; object-fit: cover;
          transition: transform 0.3s ease;
        }
        .xai3-product-card:hover .xai3-product-img-wrap img { transform: scale(1.03); }
        .xai3-product-img-placeholder {
          width: 100%; height: 100%;
          display: flex; align-items: center; justify-content: center;
          font-size: 40px; color: var(--text-secondary);
        }
        .xai3-product-info {
          padding: 12px;
        }
        .xai3-product-title {
          font-family: var(--font-display);
          font-size: 14px; font-weight: 600;
          color: var(--text-primary);
          margin-bottom: 4px;
          white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .xai3-product-price {
          font-family: var(--font-mono);
          font-size: 15px;
          color: var(--accent-gold);
          font-weight: 500;
          margin-bottom: 10px;
        }
        .xai3-add-btn {
          width: 100%;
          padding: 8px;
          border-radius: var(--radius-full);
          background: var(--accent-blue);
          color: #fff;
          font-size: 13px;
          font-weight: 600;
          transition: background var(--transition), transform var(--transition);
        }
        .xai3-add-btn:hover { background: var(--accent-blue-hover); transform: scale(0.98); }
        .xai3-add-btn--added { background: var(--accent-green); }
        .xai3-empty-store {
          padding: 48px 16px;
          text-align: center;
          color: var(--text-secondary);
          font-size: 15px;
        }

        /* ── Posts panel ── */
        .xai3-posts {
          border-top: 1px solid var(--border-subtle);
        }
        .xai3-posts-header {
          padding: 16px;
          font-family: var(--font-display);
          font-size: 14px;
          font-weight: 600;
          color: var(--text-secondary);
          text-transform: uppercase;
          letter-spacing: 0.06em;
        }
        .xai3-post-card {
          display: block;
          padding: 14px 16px;
          border-bottom: 1px solid var(--border-subtle);
          transition: background var(--transition);
        }
        .xai3-post-card:hover { background: var(--bg-hover); }
        .xai3-post-text {
          font-size: 15px;
          color: var(--text-primary);
          line-height: 1.6;
          margin-bottom: 10px;
          white-space: pre-wrap;
        }
        .xai3-post-stats {
          display: flex; gap: 16px;
          font-size: 13px; color: var(--text-secondary);
        }
        .xai3-post-age { margin-left: auto; }

        /* ── Suggested stores ── */
        .xai3-suggested-section {
          border-top: 1px solid var(--border);
          padding: 16px;
        }
        .xai3-suggested-title {
          font-family: var(--font-display);
          font-size: 16px; font-weight: 700;
          color: var(--text-primary);
          margin-bottom: 4px;
        }
        .xai3-suggested-subtitle {
          font-size: 13px; color: var(--text-secondary);
          margin-bottom: 16px;
        }
        .xai3-suggested-card {
          display: flex; align-items: center; gap: 12px;
          padding: 10px 0;
          border-bottom: 1px solid var(--border-subtle);
          transition: background var(--transition);
        }
        .xai3-suggested-card:last-child { border-bottom: none; }
        .xai3-suggested-card:hover { opacity: 0.85; }
        .xai3-suggested-pfp {
          width: 40px; height: 40px;
          border-radius: 50%; object-fit: cover;
          background: var(--bg-card); flex-shrink: 0;
        }
        .xai3-suggested-info { flex: 1; min-width: 0; }
        .xai3-suggested-name {
          font-weight: 600; font-size: 14px;
          color: var(--text-primary);
          white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .xai3-suggested-handle {
          font-size: 13px; color: var(--text-secondary);
        }
        .xai3-follow-btn {
          padding: 6px 16px;
          border-radius: var(--radius-full);
          border: 1px solid var(--border);
          font-size: 13px; font-weight: 600;
          color: var(--text-primary);
          transition: background var(--transition);
          flex-shrink: 0;
        }
        .xai3-follow-btn:hover { background: var(--bg-hover); }

        /* ── Cart FAB ── */
        .xai3-cart-fab {
          position: fixed;
          bottom: 24px; right: 24px;
          width: 56px; height: 56px;
          border-radius: 50%;
          background: var(--accent-blue);
          color: #fff;
          font-size: 22px;
          display: flex; align-items: center; justify-content: center;
          box-shadow: 0 4px 20px rgba(29,155,240,0.35);
          z-index: 100;
          transition: transform var(--transition), background var(--transition);
        }
        .xai3-cart-fab:hover { transform: scale(1.05); background: var(--accent-blue-hover); }
        .xai3-cart-count {
          position: absolute;
          top: -4px; right: -4px;
          background: var(--accent-purple);
          color: #fff;
          font-family: var(--font-mono);
          font-size: 11px; font-weight: 700;
          width: 20px; height: 20px;
          border-radius: 50%;
          display: flex; align-items: center; justify-content: center;
        }

        /* ── Cart drawer ── */
        .xai3-cart-overlay {
          position: fixed; inset: 0;
          background: rgba(0,0,0,0.6);
          z-index: 200;
          backdrop-filter: blur(4px);
        }
        .xai3-cart-drawer {
          position: fixed;
          bottom: 0; left: 50%; transform: translateX(-50%);
          width: 100%; max-width: var(--col-width);
          background: var(--bg-elevated);
          border-radius: var(--radius-md) var(--radius-md) 0 0;
          border: 1px solid var(--border);
          border-bottom: none;
          padding: 20px;
          z-index: 201;
          max-height: 70vh;
          overflow-y: auto;
        }
        .xai3-cart-title {
          font-family: var(--font-display);
          font-size: 18px; font-weight: 700;
          margin-bottom: 16px;
        }
        .xai3-cart-item {
          display: flex; justify-content: space-between; align-items: center;
          padding: 10px 0;
          border-bottom: 1px solid var(--border-subtle);
          font-size: 14px;
        }
        .xai3-cart-item-title { font-weight: 500; }
        .xai3-cart-item-price {
          font-family: var(--font-mono);
          color: var(--accent-gold);
        }
        .xai3-cart-total {
          display: flex; justify-content: space-between;
          padding: 14px 0 4px;
          font-family: var(--font-display);
          font-weight: 700; font-size: 16px;
        }
        .xai3-checkout-btn {
          width: 100%;
          padding: 14px;
          background: var(--accent-blue);
          color: #fff;
          border-radius: var(--radius-full);
          font-size: 15px; font-weight: 700;
          margin-top: 12px;
          transition: background var(--transition);
        }
        .xai3-checkout-btn:hover { background: var(--accent-blue-hover); }

        /* ── RareImagery footer brand ── */
        .xai3-rare-footer {
          padding: 24px 16px;
          text-align: center;
          border-top: 1px solid var(--border-subtle);
        }
        .xai3-rare-footer a {
          font-family: var(--font-display);
          font-size: 13px;
          color: var(--text-secondary);
          transition: color var(--transition);
        }
        .xai3-rare-footer a:hover { color: var(--accent-gold); }
        .xai3-rare-footer span {
          color: var(--accent-purple);
          font-weight: 700;
        }
      `}</style>

      <div className="xai3-layout">
        <div className="xai3-center">

          {/* Banner */}
          <div className="xai3-banner">
            {subscriber.banner && <img src={subscriber.banner} alt="banner" />}
            <div className="xai3-banner-overlay" />
          </div>

          {/* Profile */}
          <div className="xai3-profile-section">
            <div className="xai3-avatar-row">
              <img src={subscriber.pfp} alt={subscriber.displayName} className="xai3-avatar" />
              <div className="xai3-rare-badge">
                <span>✦</span> Be Rare
              </div>
            </div>

            <div className="xai3-display-name">
              {subscriber.displayName}
              {subscriber.verified && <VerifiedBadge />}
            </div>
            <div className="xai3-handle">@{subscriber.handle}</div>
            {subscriber.bio && <div className="xai3-bio">{subscriber.bio}</div>}

            <div className="xai3-meta">
              {joinYear && <div className="xai3-meta-item">📅 Joined {joinYear}</div>}
              <div className="xai3-meta-item">🛍️ Powered by <strong style={{color:'var(--accent-purple)',marginLeft:4}}>RareImagery</strong></div>
            </div>

            <div className="xai3-stats">
              <div className="xai3-stat">
                <strong>{subscriber.following?.toLocaleString()}</strong>
                <span>Following</span>
              </div>
              <div className="xai3-stat">
                <strong>{subscriber.followers?.toLocaleString()}</strong>
                <span>Followers</span>
              </div>
              <div className="xai3-stat">
                <strong>{products.length}</strong>
                <span>Products</span>
              </div>
            </div>
          </div>

          {/* Tabs */}
          <div className="xai3-tabs">
            {['store', 'posts'].map(t => (
              <button
                key={t}
                className={`xai3-tab ${tab === t ? 'active' : ''}`}
                onClick={() => setTab(t)}
              >
                {t === 'store' ? `🛍️ Store (${products.length})` : '📝 Posts'}
              </button>
            ))}
          </div>

          {/* Store tab */}
          {tab === 'store' && (
            <>
              <div className="xai3-store-header">
                <div className="xai3-store-title">{subscriber.displayName}'s Store</div>
                {products.length > 0 && (
                  <div className="xai3-store-stats">
                    <span><strong>{products.length}</strong> items</span>
                  </div>
                )}
              </div>

              {products.length > 0 ? (
                <div className="xai3-products-grid">
                  {products.map(p => (
                    <ProductCard key={p.id} product={p} onAddToCart={addToCart} />
                  ))}
                </div>
              ) : (
                <div className="xai3-empty-store">
                  No products listed yet. Check back soon.
                </div>
              )}
            </>
          )}

          {/* Posts tab */}
          {tab === 'posts' && (
            <div className="xai3-posts">
              <div className="xai3-posts-header">Recent Posts</div>
              {subscriber.topPosts.length > 0
                ? subscriber.topPosts.map((post, i) => <PostCard key={i} post={post} />)
                : <div style={{padding:'32px 16px',textAlign:'center',color:'var(--text-secondary)'}}>No cached posts yet.</div>
              }
            </div>
          )}

          {/* Suggested Stores */}
          {suggested.length > 0 && (
            <div className="xai3-suggested-section">
              <div className="xai3-suggested-title">More from RareImagery</div>
              <div className="xai3-suggested-subtitle">Other stores you might like</div>
              {suggested.map(s => <SuggestedStore key={s.handle} store={s} />)}
            </div>
          )}

          {/* Footer */}
          <div className="xai3-rare-footer">
            <a href="https://rareimagery.net">
              Powered by <span>RareImagery</span> · Be Rare
            </a>
          </div>

        </div>
      </div>

      {/* Cart FAB */}
      {cartCount > 0 && (
        <button className="xai3-cart-fab" onClick={() => setCartOpen(true)}>
          🛒
          <span className="xai3-cart-count">{cartCount}</span>
        </button>
      )}

      {/* Cart drawer */}
      {cartOpen && (
        <>
          <div className="xai3-cart-overlay" onClick={() => setCartOpen(false)} />
          <div className="xai3-cart-drawer">
            <div className="xai3-cart-title">Your Cart</div>
            {cart.map(item => (
              <div key={item.id} className="xai3-cart-item">
                <span className="xai3-cart-item-title">{item.title} × {item.qty}</span>
                <span className="xai3-cart-item-price">
                  {new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: item.currency
                  }).format(parseFloat(item.price) * item.qty)}
                </span>
              </div>
            ))}
            <div className="xai3-cart-total">
              <span>Total</span>
              <span style={{color:'var(--accent-gold)',fontFamily:'var(--font-mono)'}}>
                ${cartTotal.toFixed(2)}
              </span>
            </div>
            <button className="xai3-checkout-btn">Proceed to Checkout →</button>
          </div>
        </>
      )}
    </>
  )
}
```

---

## File Location

```
frontend/
└── pages/
    └── store/
        └── [handle].jsx    ← copy the component above here
```

---

## What's Included (Xai3 Feature Checklist)

### From Xai2 ✓
- [x] Single 600px center column — exact X feed feel
- [x] Store section in center feed, below profile tabs
- [x] 100% Drupal JSON:API cached data (no live X API)
- [x] One file, ships today
- [x] Mobile-first, perfect on any screen size
- [x] ISR with `revalidate: 60`

### From Xai1 ✓
- [x] CSS custom property design tokens (full system)
- [x] Sora + DM Sans + JetBrains Mono font stack
- [x] Shimmer skeleton animation system
- [x] Rich store panel with product count stats
- [x] Verified badge (SVG, official X style)
- [x] "More from RareImagery" suggested stores widget
- [x] Follower / following / product count stats row

### New in Xai3 ✓
- [x] Cart FAB with purple badge counter
- [x] Cart drawer with total + checkout CTA
- [x] Add-to-cart confirmation state per product
- [x] Tab system: Store | Posts (no page navigation)
- [x] "Be Rare" badge on profile (RareImagery branding)
- [x] Banner gradient fallback (purple → black, on-brand)

---

## Ship Path

1. **Today:** Copy `[handle].jsx` into `frontend/pages/store/`
2. **Populate:** Make sure subscriber onboarding writes all `field_top_posts` and `field_x_*` fields to Drupal
3. **Test:** Visit `/store/[your-handle]` — products and posts should load instantly
4. **This week:** Wire `xai3-checkout-btn` to Drupal Commerce cart endpoint
5. **Next milestone:** Add `xai3-right-sidebar` (always-visible store panel) for desktop — this becomes Xai4
