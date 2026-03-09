# X Marketplace Architecture Guide – Drupal (Hostinger) → Next.js (Vercel) Wildcard Stores + Grok

This is the complete blueprint so every X creator gets:
- Their own store URL: `creatorname.rareimagery.net`
- Public consumer site (buyers)
- Private seller dashboard (control)
- Full Grok AI power on both sides

## Why This Architecture Wins
- Drupal = single source of truth (stores, products, Creator X Profiles)
- Next.js + Vercel = blazing-fast frontend with wildcard subdomains
- One codebase for both consumer + seller experiences
- Automatic sync via webhooks + On-Demand ISR
- Grok handles everything AI-related (no extra servers)

## Step 1: Vercel Setup (5 minutes)
1. In Vercel dashboard → your Next.js project → Settings → Domains
2. Add `rareimagery.net` and `*.rareimagery.net` (wildcard)
3. Enable “Vercel Edge Network” and “Automatic HTTPS”

## Step 2: Next.js Folder Structure (create these)