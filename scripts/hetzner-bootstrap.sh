#!/bin/bash
# Primera instalación en el VPS Hetzner (ejecutar como usuario con sudo/docker).
# Uso: bash scripts/hetzner-bootstrap.sh https://github.com/tu-org/carro.git
set -euo pipefail

REPO_URL="${1:-}"
APP_DIR="${APP_DIR:-/opt/carro}"
BRANCH="${BRANCH:-main}"

if [ -z "$REPO_URL" ]; then
  echo "Uso: bash scripts/hetzner-bootstrap.sh <repo-git-url>" >&2
  exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker no encontrado. Instálalo antes de continuar." >&2
  exit 1
fi

if [ ! -d "$APP_DIR/.git" ]; then
  sudo mkdir -p "$APP_DIR"
  sudo chown "$USER:$USER" "$APP_DIR"
  git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH" || git reset --hard "origin/$BRANCH"

if [ ! -f .env.prod ]; then
  cp .env.hetzner.example .env.prod
  echo ""
  echo "Edita $APP_DIR/.env.prod (APP_KEY, contraseñas, R2, Transbank, etc.) y vuelve a ejecutar:"
  echo "  docker compose -f docker-compose.prod.yml up -d --build"
  exit 0
fi

docker compose -f docker-compose.prod.yml up -d --build
curl -sf http://127.0.0.1:8000/up >/dev/null
echo "Bootstrap OK — app en http://127.0.0.1:8000/up"
