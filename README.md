# Carro — Tienda online Chile

Tienda con **Laravel 13**, **PHP 8.4**, **Blade + Bootstrap 5**, **PostgreSQL**, **Redis**, **Swagger** y pagos **Webpay Plus** (Transbank).

## Requisitos

- Docker Desktop

## Inicio rápido

```bash
cd carro
copy .env.example .env
php artisan key:generate
docker compose build
docker compose up -d
docker compose exec app php artisan db:seed --force
```

| Servicio | URL |
|----------|-----|
| Tienda | http://localhost:8081 |
| API Swagger | http://localhost:8081/api/documentation |
| Health | http://localhost:8081/up |
| Mailpit | http://localhost:8026 |

### Credenciales demo

| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | admin@carro.local | Admin123!Secure |
| Cliente | cliente@carro.local | Cliente123! |

Admin adicional tras `db:seed` (solo local): en `.env` define `SEED_EXTRA_ADMIN_EMAIL` y `SEED_EXTRA_ADMIN_PASSWORD` (ver `.env.example`).

## Flujo de compra

1. Catálogo → agregar al carro
2. Checkout → datos de envío
3. Redirect a **Webpay Plus** (tarjetas de prueba Transbank)
4. Return → confirmación de pedido

## API REST

Prefijo: `/api/v1`

- `GET /products`, `GET /cart`, `POST /orders`
- `POST /payments/webpay/create`, `POST /payments/webpay/commit`
- Documentación: `/api/documentation`

## Comandos útiles

```bash
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan l5-swagger:generate
docker compose exec app php artisan test
```

## Despliegue Koyeb + Neon

Ver **[KOYEB.md](KOYEB.md)** — Dockerfile de producción en la raíz.

## Estructura

```
carro/
├── app/Http/Controllers/Web/   # Tienda Blade
├── app/Http/Controllers/Api/   # API + Swagger
├── app/Services/               # Carro, órdenes, Webpay
├── resources/views/shop/       # HTML responsive
├── docker/                     # Nginx, PHP 8.4
├── docker-compose.yml          # Local
└── Dockerfile                  # Koyeb
```
