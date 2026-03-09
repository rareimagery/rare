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

# 2. Create project folder
sudo mkdir -p /var/www/rareimagery-marketplace
sudo chown $USER:$USER /var/www/rareimagery-marketplace
cd /var/www/rareimagery-marketplace

# 3. Clone your code (or create repo first if you don't have one)
git clone https://github.com/YOUR-USERNAME/YOUR-REPO.git .   # ← change to your repo
# If no repo yet: git init && git remote add origin YOUR-URL

# 4. Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.9'

services:
  drupal:
    image: drupal:10.3-apache
    container_name: rareimagery-drupal
    restart: unless-stopped
    ports:
      - "8080:80"                    # change to 80 later for direct access
    volumes:
      - ./drupal:/var/www/html
    environment:
      - XAI_API_KEY=${XAI_API_KEY}
    depends_on:
      - postgres
    networks:
      - marketplace-net

  postgres:
    image: postgres:16-alpine
    container_name: rareimagery-postgres
    restart: unless-stopped
    ports:
      - "5432:5432"
    volumes:
      - postgres-data:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: rareimagery
      POSTGRES_USER: drupal
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    networks:
      - marketplace-net

networks:
  marketplace-net:
    driver: bridge

volumes:
  postgres-data:
EOF

# 5. Create .env (fill your keys)
cat > .env << 'EOF'
XAI_API_KEY=your_xai_key_from_console.x.ai_here
POSTGRES_PASSWORD=SuperStrongPassword123!ChangeThis
EOF

# 6. Firewall & start everything
sudo ufw allow 22
sudo ufw allow 8080
sudo ufw allow 5432
sudo ufw --force enable
docker compose up -d

# 7. Done!
echo "✅ Done! Open http://YOUR_SERVER_IP:8080 in browser and run the Drupal installer"
```

---

## After Browser Installer Finishes (~2 minutes)

Run these inside the container:

```bash
docker compose exec drupal composer require drupal/ai drupal/ai_provider_x drupal/key
docker compose exec drupal drush en ai ai_provider_x key -y
docker compose exec drupal drush cr
```

Grok is now connected. Follow the "Creator Store Owner Guide" to create the store type and set up the X profile auto-import button.

---

## Push Code Going Forward

Every time you edit locally or in GitHub:

```bash
git add .
git commit -m "update marketplace"
git push
```

Then on the server:

```bash
git pull && docker compose up -d --build
```

---

## Stack Overview

| Component | Version | Purpose |
|-----------|---------|---------|
| Drupal | 10.3-apache | CMS / storefront engine |
| PostgreSQL | 16-alpine | Database |
| Grok (xAI) | API via `ai_provider_x` | Auto-import X profile data |
| Docker Compose | v3.9 | Container orchestration |

---

## Next Steps

To continue setup, provide:

- **Server public IP** — for exact browser URLs
- **GitHub repo URL** — for precise clone/push commands
- **Main domain** (e.g. `rareimagery.net`) — for domain pointing + SSL

This unlocks:
- One-click domain pointing + SSL
- Grok auto-import of PFP, banner, top posts & top 8 followers
- Optional: migrate to full Vercel Next.js wildcard setup

---

> Each X creator gets their own store at `creatorname.rareimagery.net`, with Grok automatically pulling their profile data. This backend powers all of them.
