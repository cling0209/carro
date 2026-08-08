#!/bin/bash
# Ejecutar en el VPS: /opt/carro/scripts/deploy-prod.sh
set -euo pipefail
cd /opt/carro
git fetch origin main
git reset --hard origin/main
docker compose -f docker-compose.prod.yml up -d --build app
docker image prune -f
curl -sf http://127.0.0.1:8000/up > /dev/null
echo "Deploy OK"
