# Folder Structure Guide

This is a Laravel monolith. The folders below are deliberately kept in Laravel's standard layout so Composer, Artisan, routing, testing, and deployment continue to work correctly.

```text
Private reservation/
├── app/          Backend application code
├── config/       Backend configuration
├── database/     Database schema and sample data
├── routes/       Backend HTTP route definitions
├── resources/    Frontend source files
├── public/       Browser-accessible compiled assets and entry point
├── tests/        Automated backend tests
├── docs/         Project, paper, planning, and diagram files
├── scripts/      Local development helper scripts
├── storage/      Generated logs, cache, and uploaded/generated files
├── bootstrap/    Laravel application startup and cached configuration
├── .env          Local secrets and machine settings — never commit
├── composer.json PHP backend dependencies
└── package.json  Frontend build dependencies
```

## Backend — `app/`, `config/`, `routes/`

| Folder | Purpose | Examples in this system |
|---|---|---|
| `app/Actions/` | Business workflows that change data safely | Create reservation, start checkout, confirm payment |
| `app/Console/` | Artisan commands and scheduled tasks | Payment configuration checker, expiration sweeper |
| `app/Contracts/` | Interfaces used by services | `PaymentGateway` |
| `app/Enums/` | Central allowed values and statuses | Reservation and payment statuses, user roles |
| `app/Http/Controllers/` | Receives browser/webhook requests and selects the action | Customer booking, admin management, provider webhook |
| `app/Http/Middleware/` | Request protection and role checks | Customer/admin role middleware |
| `app/Http/Requests/` | Server-side validation for submitted forms | Court and reservation validation |
| `app/Jobs/` | Background tasks | Confirmation email delivery |
| `app/Mail/` | Email message definitions | Reservation confirmation email |
| `app/Models/` | Database records and relationships | User, Court, Reservation, Payment, Receipt |
| `app/Policies/` | Ownership and permission rules | Customer can access only their own reservation/receipt |
| `app/Services/` | Provider adapters and reusable domain services | Availability calculation and payment gateway adapter |
| `app/Providers/` | Dependency bindings and application startup | Bind the selected payment gateway |
| `config/` | Reads `.env` values into safe application settings | Reservation rules, payment gateway, mail, database |
| `routes/web.php` | Web pages and form endpoints | Landing page, customer, admin, payment webhook routes |

## Frontend — `resources/`, `public/`

| Folder | Purpose | Examples in this system |
|---|---|---|
| `resources/views/` | Blade page templates shown in the browser | Home, login, customer dashboard, admin dashboard, reservation pages |
| `resources/views/layouts/` | Shared page shells | Customer navigation and admin sidebar |
| `resources/css/` | Tailwind/CSS source | Application design styles |
| `resources/js/` | Browser JavaScript source | Alpine or page interactions |
| `public/` | Files the browser can request directly | `index.php`, built CSS/JS, uploaded court images |
| `public/build/` | Generated Vite frontend bundle | Do not edit manually; run `npm run build` |

**Rule:** edit source in `resources/`, then run `npm run build`. Do not edit files inside `public/build/` directly.

## Database — `database/`

| Folder | Purpose |
|---|---|
| `database/migrations/` | Versioned PostgreSQL schema changes: tables, indexes, constraints |
| `database/seeders/` | Demo/admin accounts and sample data |
| `database/factories/` | Reusable test data generators |

**Rule:** change the database using a new migration. Never manually edit a deployed database schema.

## Tests — `tests/`

| Folder | Purpose |
|---|---|
| `tests/Feature/` | Tests full Laravel behavior: routes, authorization, reservations, payment webhooks |
| `tests/Unit/` | Tests one focused class or rule at a time |

## Project Documents — `docs/`

| Folder | Purpose |
|---|---|
| `docs/papers/` | Instructor requirements and the project paper |
| `docs/planning/` | Analysis, architecture notes, workflow documentation |
| `docs/diagrams/` | Flowchart and architecture images |

## Support and Generated Folders

| Folder | Purpose | Edit directly? |
|---|---|---|
| `scripts/` | Local setup and development helper commands | Yes, when needed |
| `storage/` | Logs, session/cache files, generated receipts/uploads | Usually no |
| `bootstrap/cache/` | Laravel-generated cache | No |
| `vendor/` | PHP packages installed by Composer | No |
| `node_modules/` | JavaScript packages installed by npm | No |
| `.agents/`, `.codex/`, `.pi/` | AI/developer helper configuration | Only when maintaining agent tooling |

## Where New Work Goes

| If you are adding… | Put it in… |
|---|---|
| A new customer/admin page | `resources/views/customer/` or `resources/views/admin/` plus a controller in `app/Http/Controllers/` |
| New business workflow | `app/Actions/` |
| New external gateway/API | `app/Services/` and `app/Contracts/` |
| A database table or column | a new file in `database/migrations/` |
| A validation rule for a form | `app/Http/Requests/` |
| A new route | `routes/web.php` |
| A test | `tests/Feature/` or `tests/Unit/` |
| A project paper/diagram | `docs/papers/`, `docs/planning/`, or `docs/diagrams/` |
