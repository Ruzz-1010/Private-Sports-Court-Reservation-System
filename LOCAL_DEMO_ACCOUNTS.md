# Local Demo Accounts and Run Guide

Use this guide when running the Private Pickleball Court Reservation System locally.

## Accounts

### Administrator

| Field | Value |
|---|---|
| Email | `admin@court.local` |
| Password | `admin_dev_12345` |
| Role | Administrator |
| Login page | `http://127.0.0.1:8000/login` |

### Test customers

All demo customers use the same password: `customer123`.

| Name | Email | Password |
|---|---|---|
| Juan Dela Cruz | `juan@example.com` | `customer123` |
| Maria Santos | `maria@example.com` | `customer123` |
| Pedro Reyes | `pedro@example.com` | `customer123` |

These accounts are created by `DemoReservationSeeder`. The clean reset seeder preserves existing users but does not recreate a missing user account.

## Start the application

Open a terminal in the project folder:

```bash
cd "/home/ruzz/Documents/Private reservation"
sudo pg_ctlcluster 16 main start
php artisan serve
```

Open `http://127.0.0.1:8000` in the browser.

For email delivery during a demo, use a second terminal:

```bash
cd "/home/ruzz/Documents/Private reservation"
php artisan queue:work
```

## Reset local demo data

This keeps the administrator and customer accounts. It clears reservations, payments, receipts, payment events, audit logs, court blocks, jobs, sessions, cache, and courts, then creates Pickleball Court 1–5 again.

```bash
cd "/home/ruzz/Documents/Private reservation"
sudo pg_ctlcluster 16 main start
php artisan db:seed --class=ResetDemoDataSeeder
php artisan config:clear
```

Do not run the full `DatabaseSeeder` after a clean reset if you want an empty reservation history. The full seeder also creates demo reservations.

## Current booking rules

- Facility timezone: `Asia/Manila`
- Operating hours: `9:00 AM` to `9:00 PM`
- Booking increment: 30 minutes
- Minimum booking: 1 hour
- Maximum booking: 4 hours
- Currency: PHP

## Useful verification commands

```bash
php artisan migrate
php artisan view:cache
npm run build
vendor/bin/pint --test
```

The test suite requires the PostgreSQL service and the configured testing schema.
