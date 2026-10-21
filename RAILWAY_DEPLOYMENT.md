# Railway Deployment Guide

This application needs four Railway services:

1. **Web** — Laravel HTTP application
2. **PostgreSQL** — reservations, users, payments, sessions, cache, and queued jobs
3. **Worker** — sends confirmation emails and processes background jobs
4. **Scheduler** — expires overdue reservation holds every minute

All three application services use the same GitHub repository and Dockerfile. Their `SERVICE_MODE` variable selects the process to run.

## 1. Create the Railway project

1. Open Railway and choose **New Project**.
2. Select **Deploy from GitHub repo**.
3. Choose `Ruzz-1010/Private-Sports-Court-Reservation-System`.
4. Railway will detect the root `Dockerfile`.
5. Add a **PostgreSQL** database to the same project.

Do not expose the PostgreSQL service publicly. Use Railway's private variable references.

## 2. Configure the web service

Add these variables to the web service:

```env
SERVICE_MODE=web
APP_NAME=Private Pickleball Court Reservation System
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_A_REAL_KEY
APP_TIMEZONE=Asia/Manila
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=REFERENCE_POSTGRES_PGHOST
DB_PORT=REFERENCE_POSTGRES_PGPORT
DB_DATABASE=REFERENCE_POSTGRES_PGDATABASE
DB_USERNAME=REFERENCE_POSTGRES_PGUSER
DB_PASSWORD=REFERENCE_POSTGRES_PGPASSWORD
DB_SSLMODE=prefer

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

PAYMENT_GATEWAY=paymongo
PAYMONGO_BASE_URL=https://api.paymongo.com
PAYMONGO_WEBHOOK_PATH=webhooks/paymongo
PAYMONGO_WEBHOOK_RATE_LIMIT=60
```

Use Railway's **Add Reference** interface for the five PostgreSQL values. Do not copy database credentials into GitHub.

Generate `APP_KEY` locally:

```bash
php artisan key:generate --show
```

Copy only the generated `base64:...` value into Railway Variables.

## 3. Create the first administrator and demo data

For the first deployment only, add:

```env
SEED_DATABASE_ON_BOOT=true
ADMIN_NAME=Court Administrator
ADMIN_EMAIL=your-admin-email@example.com
ADMIN_PASSWORD=USE_A_LONG_UNIQUE_PASSWORD
```

The web service will run migrations, then the existing database seeders. After the first successful deployment, change:

```env
SEED_DATABASE_ON_BOOT=false
```

The seeded admin, courts, and demo records remain in PostgreSQL. Keeping this flag disabled prevents deployment restarts from resetting demo presentation data.

## 4. Generate the public domain

1. Open the web service.
2. Go to **Settings → Networking**.
3. Generate a Railway domain.
4. Copy the full HTTPS address.
5. Set:

```env
APP_URL=https://your-generated-domain
PAYMONGO_WEBHOOK_URL=https://your-generated-domain/webhooks/paymongo
```

Redeploy after updating these values.

The health check is already configured at `/up`.

## 5. Configure PayMongo

Add the following web-service secrets:

```env
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_WEBHOOK_SECRET=...
```

In the PayMongo Test Mode dashboard:

1. Create a webhook endpoint using `https://your-domain/webhooks/paymongo`.
2. Subscribe to `checkout_session.payment.paid`.
3. Copy the endpoint signing secret to `PAYMONGO_WEBHOOK_SECRET`.
4. Keep test keys for the school demonstration.

Never expose either secret in GitHub, screenshots, chat messages, or browser JavaScript.

## 6. Configure email

For Gmail SMTP with an App Password:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-demo-email@gmail.com
MAIL_PASSWORD=YOUR_GOOGLE_APP_PASSWORD
MAIL_FROM_ADDRESS=your-demo-email@gmail.com
MAIL_FROM_NAME=San Jose Pickleball
```

Use a Google App Password rather than the normal Google account password.

## 7. Add the queue worker

Create another Railway service from the same GitHub repository.

Use the same shared application, database, PayMongo, and mail variables. Change only:

```env
SERVICE_MODE=worker
```

Do not assign a public domain to the worker.

The container runs:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600
```

Railway restarts the worker after its maximum runtime.

## 8. Add the scheduler

Create a third Railway service from the same repository.

Use the same application and database variables. It does not need a public domain. Set:

```env
SERVICE_MODE=scheduler
```

The container runs `php artisan schedule:work`, which triggers the existing reservation-expiration command every minute.

## 9. Uploaded court images

The container filesystem is temporary. Mount a Railway Volume on the **web service** at:

```text
/var/www/html/storage/app/public
```

Without a volume, newly uploaded court images may disappear after a redeployment.

Existing images committed in `public/images/` remain part of every deployment.

## 10. Deployment verification

After all services are healthy:

1. Open `https://your-domain/up`; it should return a successful health response.
2. Log in with the seeded administrator.
3. Register a new customer and confirm the account appears.
4. Create a reservation and verify overlap protection.
5. Test an on-site payment and download the receipt.
6. Confirm the worker delivers the email with its PDF receipt.
7. Test PayMongo Test Mode checkout and the signed webhook.
8. Confirm an overdue pending reservation is expired by the scheduler.
9. Inspect Railway logs for the web, worker, and scheduler services.

## Files used by Railway

| File | Purpose |
|---|---|
| `Dockerfile` | Builds PHP, Apache, Composer dependencies, and Vite assets |
| `docker/apache-site.conf` | Serves Laravel from the secure `public/` document root |
| `docker/entrypoint.sh` | Selects web, worker, or scheduler runtime |
| `railway.toml` | Enables the Docker build, health check, and restart policy |
| `.dockerignore` | Keeps secrets, tests, docs, and local artifacts out of the image |

## Rollback

Railway keeps earlier deployments. If a new release fails, select the last working deployment and choose **Redeploy**. PostgreSQL data is separate from the application container and is not removed by an application rollback.
