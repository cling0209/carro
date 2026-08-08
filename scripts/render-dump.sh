#!/bin/bash
set -euo pipefail
source /opt/carro/.env.prod

RENDER_HOST="${RENDER_HOST:-dpg-d9nu4n5bedkc73fvmef0-a.oregon-postgres.render.com}"
RENDER_DB="${RENDER_DB:-carro_r8gi}"

echo "Listing databases on Render..."
docker run --rm -e PGPASSWORD="$DB_PASSWORD" postgres:18-alpine \
  psql -h "$RENDER_HOST" -U carro -d postgres -At \
  -c "SELECT datname FROM pg_database WHERE datistemplate = false;"

echo "Dumping $RENDER_DB from Render..."
docker run --rm -e PGPASSWORD="$DB_PASSWORD" postgres:18-alpine \
  pg_dump -h "$RENDER_HOST" -U carro -d "$RENDER_DB" --no-owner --no-acl --clean --if-exists \
  | docker exec -i carro-postgres-1 psql -U carro -d tienda -v ON_ERROR_STOP=1

echo "Restore complete."
