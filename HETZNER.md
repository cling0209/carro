# Desplegar Tienda Rómulo en Hetzner (Docker + CI/CD)

VPS recomendado: **CX23** (2 vCPU, 4 GB RAM). Misma imagen que Render/Koyeb (`Dockerfile` + `docker-compose.prod.yml`).

## Arquitectura

```text
[Internet] → Nginx/Caddy en el host (443) → 127.0.0.1:8000
                                              └── [Docker app] Laravel + Nginx + PHP-FPM (+ queue worker)
                                              └── [Docker postgres] PostgreSQL 18
```

## 1. Bootstrap en el VPS (una vez)

```bash
ssh root@TU_IP

# Docker (si no está instalado)
curl -fsSL https://get.docker.com | sh
usermod -aG docker $USER

git clone https://github.com/cling0209/carro.git /opt/carro
cd /opt/carro
cp .env.hetzner.example .env.prod
nano .env.prod   # APP_KEY, DB_PASSWORD, R2, Transbank, etc.

docker compose -f docker-compose.prod.yml up -d --build
curl -sf http://127.0.0.1:8000/up && echo OK
```

O con el script:

```bash
bash scripts/hetzner-bootstrap.sh https://github.com/cling0209/carro.git
```

### Nginx en el host (ejemplo)

Proxy de `tienda.romulo.cl` → `127.0.0.1:8000` con TLS (Certbot o Caddy).

## 2. CI/CD — GitHub Actions

Cada **push a `main`** ejecuta `.github/workflows/hetzner-deploy.yml`:

1. SSH al VPS
2. `git pull` en `/opt/carro`
3. `docker compose ... up -d --build app`
4. Health check en `/up`

### Secrets en GitHub

Repo → **Settings** → **Secrets and variables** → **Actions**:

| Secret | Valor |
|--------|-------|
| `VPS_HOST` | IP del VPS (ej. `46.224.20.162`) |
| `VPS_USER` | Usuario SSH (ej. `root`) |
| `VPS_SSH_KEY` | Clave privada SSH completa (`-----BEGIN...`) |
| `VPS_PORT` | `22` (opcional) |

### Clave SSH para GitHub Actions

En tu PC:

```bash
ssh-keygen -t ed25519 -C "github-actions-carro" -f hetzner_deploy -N ""
```

En el VPS (`~/.ssh/authorized_keys` del usuario deploy):

```text
# contenido de hetzner_deploy.pub
```

En GitHub secret `VPS_SSH_KEY`: contenido de `hetzner_deploy` (privada), **sin comillas** y con saltos de linea intactos.

En PowerShell (Windows), copiar al portapapeles:

```powershell
Get-Content -Raw hetzner_deploy | Set-Clipboard
```

Luego pegar en GitHub → Settings → Secrets → `VPS_SSH_KEY` → Update.

### Error `ssh: no key found` / `unable to authenticate`

Significa que el secret `VPS_SSH_KEY` **no es una clave privada valida**. Causas habituales:

| Causa | Solucion |
|-------|----------|
| Pegaste `hetzner_deploy.pub` (publica) | Usa `hetzner_deploy` (sin `.pub`) |
| La clave quedo en una sola linea | Vuelve a pegar con `Get-Content -Raw` o copia desde el editor mostrando saltos de linea |
| Tiene passphrase | Genera otra sin passphrase: `ssh-keygen ... -N ""` |
| Clave distinta a la del VPS | La `.pub` correspondiente debe estar en `~/.ssh/authorized_keys` del usuario `VPS_USER` |

Verificar en tu PC que la clave es valida:

```bash
ssh-keygen -y -f hetzner_deploy
```

Si imprime una linea `ssh-ed25519 AAAA...`, la privada esta bien. Probar conexion:

```bash
ssh -i hetzner_deploy VPS_USER@VPS_HOST
```

### Verificar

1. Push a `main`
2. GitHub → **Actions** → *Deploy to Hetzner* → verde
3. En el VPS: `curl -sf http://127.0.0.1:8000/up`

Deploy manual en el servidor:

```bash
/opt/carro/scripts/deploy-prod.sh
```

### Error `dubious ownership in repository at '/opt/carro'`

Ocurre si el repo se clonó con un usuario distinto al que usa GitHub Actions (`VPS_USER`), por ejemplo clonaste como `root` pero el secret apunta a otro usuario.

**Opción A — en el VPS (recomendado, una vez):**

```bash
# Reemplaza DEPLOY_USER por el valor de VPS_USER en GitHub
sudo chown -R DEPLOY_USER:DEPLOY_USER /opt/carro
git config --global --add safe.directory /opt/carro
```

**Opción B:** el workflow ya ejecuta `git config --global --add safe.directory /opt/carro` antes del `git fetch`. Haz push del workflow actualizado o ejecuta ese comando manualmente en el VPS.

## 3. Variables de producción

Plantilla: **`.env.hetzner.example`**. En el servidor: **`/opt/carro/.env.prod`** (no commitear).

| Variable | Notas |
|----------|-------|
| `APP_KEY` | `php artisan key:generate --show` |
| `DB_PASSWORD` / `POSTGRES_PASSWORD` | Misma contraseña; `POSTGRES_USER` = `DB_USERNAME` |
| `RUN_QUEUE_WORKER` | `true` si usas importación en background |
| `PRODUCT_IMPORT_BACKGROUND` | `true` → puede subir CPU durante imports |

## 4. Segundo proyecto en el mismo VPS

Cada app similar suma **2 contenedores** (`postgres` + `app`). Cambia:

- Carpeta: `/opt/otro-proyecto`
- Puertos en `docker-compose.prod.yml`: ej. `8001`, `5433`
- Nuevo workflow con otro `concurrency.group` y ruta/puerto en el script SSH
- Nginx: otro `server_name` → puerto distinto

## 5. Comandos útiles

```bash
cd /opt/carro
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app
docker stats
```

Restaurar dump desde Render/Neon: `scripts/render-dump.sh`.
