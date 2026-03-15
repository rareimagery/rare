# Store Management Console – RareImagery X Marketplace
### Next.js Admin Console at `rareimagery.net/console`

---

## Overview

The console is the internal tool for creating and managing all creator stores. It lives under `rareimagery.net/console`, is protected by authentication, and is the only place stores can be provisioned.

```
rareimagery.net
├── /login                    ← Auth
├── /console/stores           ← All stores dashboard
├── /console/stores/new       ← Create new store
└── /console/stores/[id]      ← Manage individual store
```

---

## Step 1: Auth Setup (NextAuth)

```bash
npm install next-auth
```

### `app/api/auth/[...nextauth]/route.ts`

```typescript
import NextAuth from 'next-auth'
import CredentialsProvider from 'next-auth/providers/credentials'

const handler = NextAuth({
  providers: [
    CredentialsProvider({
      name: 'credentials',
      credentials: {
        email: { label: 'Email', type: 'email' },
        password: { label: 'Password', type: 'password' },
      },
      async authorize(credentials) {
        // Replace with your actual admin check (env var, DB, etc.)
        if (
          credentials?.email === process.env.CONSOLE_ADMIN_EMAIL &&
          credentials?.password === process.env.CONSOLE_ADMIN_PASSWORD
        ) {
          return { id: '1', name: 'Admin', email: credentials.email }
        }
        return null
      },
    }),
  ],
  pages: {
    signIn: '/login',
  },
  session: {
    strategy: 'jwt',
    maxAge: 8 * 60 * 60, // 8 hours
  },
})

export { handler as GET, handler as POST }
```

---

## Step 2: Login Page

### `app/login/page.tsx`

```typescript
'use client'
import { useState } from 'react'
import { signIn } from 'next-auth/react'
import { useRouter } from 'next/navigation'

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')

  const handleLogin = async () => {
    const result = await signIn('credentials', {
      email,
      password,
      redirect: false,
    })
    if (result?.error) {
      setError('Invalid credentials')
    } else {
      router.push('/console/stores')
    }
  }

  return (
    <div>
      <h1>RareImagery Console</h1>
      <input
        type="email"
        placeholder="Admin email"
        value={email}
        onChange={e => setEmail(e.target.value)}
      />
      <input
        type="password"
        placeholder="Password"
        value={password}
        onChange={e => setPassword(e.target.value)}
      />
      {error && <p>{error}</p>}
      <button onClick={handleLogin}>Sign In</button>
    </div>
  )
}
```

---

## Step 3: Console Layout with Auth Guard

### `app/console/layout.tsx`

```typescript
import { getServerSession } from 'next-auth'
import { redirect } from 'next/navigation'

export default async function ConsoleLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const session = await getServerSession()
  if (!session) redirect('/login')

  return (
    <div>
      <nav>
        <a href="/console/stores">All Stores</a>
        <a href="/console/stores/new">+ New Store</a>
      </nav>
      <main>{children}</main>
    </div>
  )
}
```

---

## Step 4: Stores Dashboard

### `app/console/stores/page.tsx`

```typescript
async function getAllStores() {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/commerce_store/online` +
    `?sort=-created&include=field_linked_x_profile`,
    {
      headers: { Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}` },
      next: { revalidate: 30 },
    }
  )
  return res.json()
}

export default async function StoresDashboard() {
  const data = await getAllStores()
  const stores = data?.data || []
  const base = process.env.NEXT_PUBLIC_BASE_DOMAIN

  return (
    <div>
      <h1>Creator Stores ({stores.length})</h1>
      <a href="/console/stores/new">+ Create New Store</a>

      <table>
        <thead>
          <tr>
            <th>Store Name</th>
            <th>Slug</th>
            <th>Live URL</th>
            <th>X Username</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {stores.map((store: any) => {
            const slug = store.attributes.field_store_slug
            const xProfile = store.relationships?.field_linked_x_profile?.data

            return (
              <tr key={store.id}>
                <td>{store.attributes.name}</td>
                <td>{slug}</td>
                <td>
                  <a
                    href={`https://${slug}.${base}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {slug}.{base} ↗
                  </a>
                </td>
                <td>{xProfile?.attributes?.field_x_username || '—'}</td>
                <td>{new Date(store.attributes.created).toLocaleDateString()}</td>
                <td>
                  <a href={`/console/stores/${store.id}`}>Manage</a>
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
```

---

## Step 5: Create New Store Form

### `app/console/stores/new/page.tsx`

```typescript
'use client'
import { useState } from 'react'
import { useRouter } from 'next/navigation'

const BASE_DOMAIN = process.env.NEXT_PUBLIC_BASE_DOMAIN || 'rareimagery.net'

function autoSlug(name: string): string {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 30)
}

export default function NewStorePage() {
  const router = useRouter()
  const [form, setForm] = useState({
    storeName: '',
    slug: '',
    xUsername: '',
    ownerEmail: '',
  })
  const [slugEdited, setSlugEdited] = useState(false)
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle')
  const [result, setResult] = useState<{ url: string; slug: string } | null>(null)
  const [error, setError] = useState('')

  const handleNameChange = (name: string) => {
    setForm(f => ({
      ...f,
      storeName: name,
      slug: slugEdited ? f.slug : autoSlug(name),
    }))
  }

  const handleSlugChange = (slug: string) => {
    setSlugEdited(true)
    setForm(f => ({ ...f, slug: slug.toLowerCase() }))
  }

  const handleSubmit = async () => {
    setStatus('loading')
    setError('')

    const res = await fetch('/api/stores/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })

    const data = await res.json()

    if (!res.ok) {
      setStatus('error')
      setError(data.error || 'Something went wrong')
      return
    }

    setResult(data)
    setStatus('success')
  }

  if (status === 'success' && result) {
    return (
      <div>
        <h1>✅ Store Created</h1>
        <p>
          <strong>{result.slug}.{BASE_DOMAIN}</strong> is live.
        </p>
        <a href={result.url} target="_blank" rel="noopener noreferrer">
          Open Store ↗
        </a>
        <br />
        <button onClick={() => router.push('/console/stores')}>
          Back to Dashboard
        </button>
        <button onClick={() => { setStatus('idle'); setResult(null); setForm({ storeName: '', slug: '', xUsername: '', ownerEmail: '' }); setSlugEdited(false) }}>
          Create Another
        </button>
      </div>
    )
  }

  return (
    <div>
      <h1>Create New Creator Store</h1>

      <label>Store Name</label>
      <input
        value={form.storeName}
        onChange={e => handleNameChange(e.target.value)}
        placeholder="Rare Imagery"
      />

      <label>Subdomain</label>
      <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
        <input
          value={form.slug}
          onChange={e => handleSlugChange(e.target.value)}
          placeholder="rareimagery"
          style={{ maxWidth: 200 }}
        />
        <span style={{ color: '#888' }}>.{BASE_DOMAIN}</span>
      </div>
      <small>3–30 chars · lowercase letters, numbers, hyphens only</small>

      <label>X Username</label>
      <input
        value={form.xUsername}
        onChange={e => setForm(f => ({ ...f, xUsername: e.target.value }))}
        placeholder="@rareimagery"
      />

      <label>Owner Email</label>
      <input
        type="email"
        value={form.ownerEmail}
        onChange={e => setForm(f => ({ ...f, ownerEmail: e.target.value }))}
        placeholder="owner@example.com"
      />

      {error && <p style={{ color: 'red' }}>⚠ {error}</p>}

      <button
        onClick={handleSubmit}
        disabled={status === 'loading' || !form.storeName || !form.slug}
      >
        {status === 'loading' ? 'Creating store...' : 'Create Store'}
      </button>
    </div>
  )
}
```

---

## Step 6: Individual Store Management Page

### `app/console/stores/[id]/page.tsx`

```typescript
async function getStore(id: string) {
  const res = await fetch(
    `${process.env.DRUPAL_API_URL}/jsonapi/commerce_store/online/${id}` +
    `?include=field_linked_x_profile`,
    {
      headers: { Authorization: `Bearer ${process.env.DRUPAL_API_TOKEN}` },
      next: { revalidate: 0 },
    }
  )
  return res.json()
}

export default async function StoreDetailPage({
  params,
}: {
  params: { id: string }
}) {
  const data = await getStore(params.id)
  const store = data?.data
  if (!store) return <div>Store not found</div>

  const slug = store.attributes.field_store_slug
  const base = process.env.NEXT_PUBLIC_BASE_DOMAIN

  return (
    <div>
      <h1>{store.attributes.name}</h1>
      <p>
        Live at:{' '}
        <a href={`https://${slug}.${base}`} target="_blank">
          {slug}.{base} ↗
        </a>
      </p>

      <section>
        <h2>Store Details</h2>
        <p>Slug: {slug}</p>
        <p>Email: {store.attributes.mail}</p>
        <p>Currency: {store.attributes.default_currency}</p>
      </section>

      <section>
        <h2>X Profile</h2>
        {/* X profile fields + Grok import button will go here */}
        <p>X Username: {store.relationships?.field_linked_x_profile?.data?.attributes?.field_x_username || 'Not linked'}</p>
      </section>
    </div>
  )
}
```

---

## Step 7: Environment Variables

```bash
# Vercel — Console-specific
CONSOLE_ADMIN_EMAIL=admin@rareimagery.net
CONSOLE_ADMIN_PASSWORD=your_secure_password_here
NEXTAUTH_SECRET=generate_with_openssl_rand_base64_32
NEXTAUTH_URL=https://rareimagery.net

# Shared with storefront
DRUPAL_API_URL=https://api.rareimagery.net
DRUPAL_API_TOKEN=your_drupal_oauth_token
NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net
```

Generate `NEXTAUTH_SECRET`:

```bash
openssl rand -base64 32
```

---

## Console Route Summary

| Route | Type | Purpose |
|-------|------|---------|
| `/login` | Public | Admin sign-in |
| `/console/stores` | Protected | All stores dashboard |
| `/console/stores/new` | Protected | Create a new store |
| `/console/stores/[id]` | Protected | Manage individual store |

---

## Next Steps

- **Add Grok import button** to `/console/stores/[id]` — one click pulls all X data into the Creator X Profile
- **Add store owner roles** — so individual creators can log in and manage only their own store
- **On-demand ISR revalidation** — when store data is updated in console, trigger `revalidatePath` so the live storefront reflects changes immediately
- **Add Stripe Connect** — link each store to a Stripe account at creation time
