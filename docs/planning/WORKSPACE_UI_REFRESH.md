# Workspace interface refresh

The admin and customer pages share a dark navy and lime interface through `layouts.workspace` and `resources/css/app.css`.

## Updated screens

- Admin overview, court list/detail/create/edit, schedule blocks, reservations list/detail, payment verification, and reports.
- Customer overview, court availability, reservation creation, history, reservation details, payment status, and receipt download actions.
- Shared navigation, page headings, form fields, buttons, empty states, and reservation status badges.

Availability now uses responsive time-slot cards. Booking shows numbered steps and a PHP currency total, including an initial value before JavaScript loads. Tables scroll horizontally on small screens. Desktop navigation uses a sidebar; mobile uses a horizontal navigation bar.

Existing form endpoints, CSRF tokens, HTTP methods, cancellation authorization, and verification confirmations are preserved. The payment verification page links to the admin reservation route.

## Verification

Static review verified balanced Blade loop/section/authorization directives, retained CSRF tokens and HTTP methods, and unchanged form endpoints across the 16 admin/customer templates. Current upstream UI files were compared before publication.

A browser preview, Laravel view compilation, and production asset build were **not run**: PHP/Composer are unavailable in this execution environment and npm dependency downloads were blocked. These checks still need a working Laravel environment:

```sh
npm ci
npm run build
php artisan view:cache
```

Then verify both roles on desktop and mobile: navigation, court forms, availability selection, booking totals, history and details, payment verification/rejection, receipt download, and report filters.
