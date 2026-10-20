# Private Pickleball Court Reservation System

A web-based reservation and payment system for a private pickleball facility in Barangay San Jose, Puerto Princesa City, Palawan.

The system will allow customers to view court availability, reserve schedules, pay online with a PayMongo Hosted Checkout QRPh payment or on-site, download receipts, receive email confirmation, and review reservation history. Administrators will manage courts, schedule blocks, reservations, payments, and reports.

## Project Status

Planning and architecture are complete. Laravel 13 is installed with PHP 8.3, Composer dependencies, Node.js dependencies, and compiled frontend assets. The default framework migrations and tests pass using the temporary SQLite development configuration. PostgreSQL 16 is installed and will become the primary database after its local service and project database are configured.

Read [AGENTS.md](AGENTS.md) before implementing features.

## Repository Structure

```text
.
├── app/                 Laravel application and domain logic
├── bootstrap/           Laravel bootstrap files
├── config/              Application configuration
├── database/            Migrations, factories, and seeders
├── docs/
│   ├── diagrams/        Flowcharts and architecture images
│   ├── papers/          Instructor brief and academic paper
│   └── planning/        Requirements and design documentation
├── public/              Web entry point and public assets
├── resources/           Blade views, JavaScript, and CSS
├── routes/              Web, API, console, and webhook routes
├── storage/             Private receipts, logs, and framework files
├── tests/               Feature and unit tests
├── AGENTS.md            Complete guide for developers and AI agents
└── README.md            Project overview
```

## Planned Stack

- Laravel and PHP
- PostgreSQL
- Blade, Tailwind CSS, and Alpine.js
- PayMongo Hosted Checkout QRPh
- Laravel Mail and Mailtrap
- Laravel Queue and Scheduler
- Pest or PHPUnit

## Development Commands

Install dependencies and prepare the application:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Run the local development processes:

```bash
composer run dev
```

Run validation:

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
```

## Environment Setup

On Linux Mint or Ubuntu, run:

```bash
bash scripts/setup-environment.sh
```

The command requests the local sudo password through the terminal and installs PHP, Composer, required PHP extensions, PostgreSQL, and supporting utilities. Node.js and npm are already available on the current machine.

Start the PostgreSQL 16 cluster when needed:

```bash
sudo pg_ctlcluster 16 main start
```

## Email Delivery for the Demo

The payment confirmation job sends the verified amount, payment method, payment
status, reservation reference, and receipt number to the customer's registered
email address. Start the queue worker during the demo:

```bash
php artisan queue:work
```

For delivery to real inboxes without purchasing a domain, configure Gmail SMTP
in the local `.env` using a Google App Password. Never use a normal Gmail
password and never commit the `.env` file.
