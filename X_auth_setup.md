# Sign in with X – Official Authentication for RareImagery X Marketplace
Live site: http://72.62.80.155
**Why this is perfect:** Every X creator signs in with their real account → we auto-create their Creator Store + linked X Profile. Grok does the rest.

## X Authentication Method (Confirmed 2026)
X provides two production-ready methods:
1. **OAuth 2.0 Authorization Code Flow with PKCE** ← Recommended (modern, secure, “Sign in with X” button)
2. Legacy user-context auth exists, but this platform uses OAuth 2.0 only

Scopes we’ll use: `users.read tweet.read follows.read offline.access` (so Grok can pull PFP, posts, followers).

## Step 1: Create Your X Developer App (5 minutes)
1. Go to https://developer.x.com → sign in with your X account  
2. Developer Portal → Projects & Apps → Create Project  
3. App name: “RareImagery Marketplace”  
4. Set Callback URI: `https://rareimagery.net/api/auth/callback/twitter`  
5. Enable OAuth 2.0 (PKCE)  
6. Copy: Client ID, Client Secret, and set “Sign in with X” permission to YES  
7. Save.

## Step 2: Install Drupal Modules (2 minutes via terminal)
SSH into your Hostinger Ubuntu and run:
```bash
docker compose exec drupal composer require drupal/social_auth drupal/social_auth_x
docker compose exec drupal drush en social_auth social_auth_x -y
docker compose exec drupal drush cr