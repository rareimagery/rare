# Hostinger Ubuntu 24.04 → Live Drupal + Postgres + Grok (X Marketplace Backend)

Run this on your fresh Ubuntu VPS. Gets everything live so X creators can start selling.

---

## One-Time Setup

Copy-paste ALL of this after you SSH in:

```bash
# 1. Update system & install Docker
sudo apt update && sudo apt upgrade -y
sudo apt install docker.io docker-compose-plugin git curl ufw -y
sudo usermod -aG docker $USER
newgrp docker

# 2. Create project folder & clone repo
sudo mkdir -p /var/www/rareimagery-marketplace
sudo chown $USER:$USER /var/www/rareimagery-marketplace
git clone https://github.com/rareimagery/rare.git /var/www/rareimagery-marketplace
cd /var/www/rareimagery-marketplace

# 3. Create .env from template (edit with your real keys)
cp .env.example .env
nano .env
# ↑ Set POSTGRES_PASSWORD, XAI_API_KEY, X_CONSUMER_KEY, X_CONSUMER_SECRET
#   Save: Ctrl+O, Enter, Ctrl+X

# 4. Firewall & start everything
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable
docker compose up -d

# 5. Done!
echo "Done! Open http://YOUR_SERVER_IP in browser and run the Drupal installer"
```

---

## Drupal Installer Settings (in browser)

When you open `http://YOUR_SERVER_IP`, use these DB settings:

| Field | Value |
|-------|-------|
| Database type | PostgreSQL |
| Database name | rare_drupal |
| Database user | rare_user |
| Database password | *(from your .env)* |
| Host | postgres |
| Port | 5432 |

---

## After Browser Installer Finishes (~2 minutes)

Run these inside the container:

```bash
cd /var/www/rareimagery-marketplace
docker compose exec drupal composer require drupal/ai drupal/ai_provider_x drupal/key
docker compose exec drupal drush en ai ai_provider_x key -y
docker compose exec drupal drush cr
```

Grok is now connected. Follow the "Creator Store Owner Guide" to create the store type and set up the X profile auto-import button.

---

## Push Code Going Forward

Every time you edit locally:

```bash
git add .
git commit -m "update marketplace"
git push
```

Then on the server:

```bash
cd /var/www/rareimagery-marketplace
git pull && docker compose up -d --build
```

---

## Stack Overview

| Component | Version | Purpose |
|-----------|---------|---------|
| Drupal | 10.3-php8.3-apache | CMS / storefront engine |
| PostgreSQL | 16-alpine | Database |
| Grok (xAI) | API via `ai_provider_x` | Auto-import X profile data |
| Docker Compose | v2 | Container orchestration |

---

## Next Steps

To continue setup, provide:

- **Server public IP** — for exact browser URLs
- **Main domain** (e.g. `rareimagery.net`) — for domain pointing + SSL

This unlocks:
- One-click domain pointing + SSL
- Grok auto-import of PFP, banner, top posts & top 8 followers
- Optional: migrate to full Vercel Next.js wildcard setup

---

> Each X creator gets their own store at `creatorname.rareimagery.net`, with Grok automatically pulling their profile data. This backend powers all of them.
