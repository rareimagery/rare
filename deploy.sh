#!/usr/bin/env bash
set -euo pipefail

# Hostinger Ubuntu 24.04 deploy script for rareimagery marketplace
# Usage: ssh into VPS, then:
#   curl -sSL https://raw.githubusercontent.com/rareimagery/rare/main/deploy.sh | bash
#   OR: git clone ... && cd rare && bash deploy.sh

INSTALL_DIR="/var/www/rareimagery-marketplace"
REPO="git@github.com:rareimagery/rare.git"

echo "=== rareimagery marketplace deploy ==="

# 1. System packages
echo "[1/6] Installing system packages..."
sudo apt update && sudo apt upgrade -y
sudo apt install -y docker.io docker-compose-plugin git curl ufw

# Add current user to docker group
if ! groups | grep -q docker; then
  sudo usermod -aG docker "$USER"
  echo "Added $USER to docker group. Script will use sudo for docker commands this run."
  USE_SUDO="sudo"
else
  USE_SUDO=""
fi

# 2. Firewall
echo "[2/6] Configuring firewall..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# 3. Clone repo
echo "[3/6] Setting up project..."
if [ ! -d "$INSTALL_DIR" ]; then
  sudo mkdir -p "$INSTALL_DIR"
  sudo chown "$USER:$USER" "$INSTALL_DIR"
  git clone "$REPO" "$INSTALL_DIR"
else
  echo "Directory exists, pulling latest..."
  cd "$INSTALL_DIR"
  git pull origin main
fi

cd "$INSTALL_DIR"

# 4. Create .env if missing
if [ ! -f .env ]; then
  echo "[4/6] Creating .env from template..."
  cp .env.example .env
  echo ""
  echo "!!! IMPORTANT: Edit .env with your real credentials !!!"
  echo "    nano $INSTALL_DIR/.env"
  echo ""
  echo "    Required: POSTGRES_PASSWORD, XAI_API_KEY"
  echo ""
  read -rp "Press Enter after you've edited .env (or Ctrl+C to do it later)..."
else
  echo "[4/6] .env already exists, skipping..."
fi

# Load env for display values below
set -a
source .env
set +a

# 5. Start containers
echo "[5/6] Starting containers..."
$USE_SUDO docker compose up -d

# 6. Wait and verify
echo "[6/6] Waiting for services to start..."
sleep 15

if $USE_SUDO docker compose ps | grep -q "Up"; then
  SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
  DRUPAL_HTTP_PORT=${DRUPAL_PORT:-80}
  echo ""
  echo "============================================"
  echo "  Deploy complete!"
  echo "============================================"
  echo ""
  echo "  Drupal:   http://$SERVER_IP:$DRUPAL_HTTP_PORT"
  echo "  Postgres: $SERVER_IP:5432"
  echo ""
  echo "  Next steps:"
  echo "  1. Open http://$SERVER_IP:$DRUPAL_HTTP_PORT in browser"
  echo "  2. Run the Drupal installer"
  echo "     - DB type: PostgreSQL"
  echo "     - DB name: rare_drupal"
  echo "     - DB user: rare_user"
  echo "     - DB pass: (from your .env)"
  echo "     - DB host: postgres"
  echo "     - DB port: 5432"
  echo "  3. Install Commerce + AI modules"
  echo ""
else
  echo "Something went wrong. Check logs:"
  echo "  docker compose logs"
  exit 1
fi
