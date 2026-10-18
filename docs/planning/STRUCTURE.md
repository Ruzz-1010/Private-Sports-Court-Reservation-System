# Application Folder Structure

```text
reservation-system/
├── app/
│   ├── Actions/
│   │   ├── Payments/
│   │   ├── Reservations/
│   │   └── Receipts/
│   ├── Console/Commands/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Customer/
│   │   │   └── Webhooks/
│   │   ├── Middleware/
│   │   └── Requests/
│   │       ├── Admin/
│   │       ├── Auth/
│   │       └── Reservations/
│   ├── Jobs/
│   ├── Mail/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/
│   ├── Providers/
│   ├── Services/
│   │   ├── Availability/
│   │   ├── Payments/
│   │   ├── Receipts/
│   │   ├── Reports/
│   │   └── Reservations/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       │   ├── courts/
│       │   ├── dashboard/
│       │   ├── payments/
│       │   ├── reports/
│       │   ├── reservations/
│       │   └── schedule-blocks/
│       ├── auth/
│       ├── components/
│       ├── customer/
│       │   ├── availability/
│       │   ├── dashboard/
│       │   ├── payments/
│       │   ├── receipts/
│       │   └── reservations/
│       ├── emails/
│       ├── errors/
│       ├── layouts/
│       └── public/
├── routes/
├── storage/
│   ├── app/private/receipts/
│   ├── framework/
│   └── logs/
└── tests/
    ├── Feature/
    │   ├── Admin/
    │   ├── Auth/
    │   ├── Payments/
    │   ├── Receipts/
    │   ├── Reports/
    │   └── Reservations/
    └── Unit/
        ├── Availability/
        ├── Payments/
        └── Reservations/
```

## Responsibilities

- `Actions`: transaction-oriented use cases such as creating a reservation, confirming a payment, and issuing a receipt.
- `Enums`: centralized reservation, payment, role, and court status values.
- `Controllers`: thin HTTP coordination grouped by administrator, customer, and external webhook entry points.
- `Requests`: server-side validation and request authorization.
- `Jobs`: background email delivery, retries, and other queued work.
- `Models`: Eloquent persistence models and relationships.
- `Policies`: per-resource authorization.
- `Services`: reusable domain calculations and external adapters.
- `Console/Commands`: scheduled reservation expiration and operational commands.
- `resources/views`: Blade pages separated by audience and feature.
- `tests/Feature`: complete HTTP and integration workflows.
- `tests/Unit`: isolated availability, pricing, and state-transition rules.

