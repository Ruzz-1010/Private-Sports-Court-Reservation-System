# Private Sports Court Reservation System

A web-based reservation and payment system for a private sports facility in Barangay San Jose, Puerto Princesa City, Palawan.

The system will allow customers to view court availability, reserve schedules, pay online through PayMongo or on-site, download receipts, receive email confirmation, and review reservation history. Administrators will manage courts, schedule blocks, reservations, payments, and reports.

## Project Status

Planning and architecture are complete. The repository contains a Laravel-ready folder structure, but the Laravel framework has not yet been installed because PHP and Composer are not currently available in the development environment.

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
- PayMongo Hosted Checkout
- Laravel Mail and Mailtrap
- Laravel Queue and Scheduler
- Pest or PHPUnit

Exact installation and development commands will be added after the Laravel scaffold is installed and verified.

## Environment Setup

On Linux Mint or Ubuntu, run:

```bash
bash scripts/setup-environment.sh
```

The command requests the local sudo password through the terminal and installs PHP, Composer, required PHP extensions, PostgreSQL, and supporting utilities. Node.js and npm are already available on the current machine.
