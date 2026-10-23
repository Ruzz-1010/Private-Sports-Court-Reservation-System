# Private Pickleball Court Reservation System

## Read This First

This file is the primary implementation guide for every AI agent and developer working in this repository. Read it completely before planning, editing, or generating code. Keep it updated whenever the project scope, business rules, architecture, or development commands change.

The system is an academic final system integration project for a private pickleball facility located in Barangay San Jose, Puerto Princesa City, Palawan. It must demonstrate persistent CRUD operations, authentication and role authorization, secure payment integration with asynchronous webhooks, transactional email, reports, validation, and protection against conflicting reservations.

The Laravel 13 application is scaffolded and Phases 1 through 4 of the
implementation order are complete: PostgreSQL schema, the database-level overlap
guard, authentication, roles, the admin seeder, full court CRUD with authorization,
availability calculation, transaction-safe reservation creation with slot holds,
customer history, the expiration sweeper, payment confirmation through a signed
idempotent webhook, and administrator on-site payment verification. Receipt PDF
downloads, the email view, and reports are not yet implemented. Do not claim that
a feature is implemented until the code and its verification exist.

## Source Documents and Project Files

- `docs/papers/FINAL SYSTEM INTEGRATION PROJECT.docx`: instructor's official requirements and paper structure.
- `docs/papers/private-resrvation.docx`: current project paper and system requirements.
- `docs/planning/PROJECT_ANALYSIS.md`: detailed analysis, proposed business rules, risks, and implementation sequence.
- `docs/planning/SYSTEM_FLOWCHARTS.md`: editable Mermaid versions of the flowcharts.
- `docs/planning/ARCHITECTURE_DIAGRAMS.md`: architecture and sequence-diagram source.
- `docs/diagrams/customer-flow.png`: customer workflow image.
- `docs/diagrams/admin-flow.png`: administrator workflow image.
- `docs/diagrams/system-flow.png`: internal system workflow image.
- `docs/diagrams/`: use-case, architecture, and payment sequence images.
- `app/Enums/`: `Role`, `ReservationStatus`, `PaymentMethod`, `PaymentStatus`.
- `database/migrations/2026_10_18_132036_add_overlap_guard_to_reservations_table.php`:
  the `reservations_no_overlapping_holds` exclusion constraint.
- `config/reservation.php`: all booking business rules (timezone, operating hours,
  increments, duration limits, advance window, lead time, hold durations).
- `app/Services/Availability/AvailabilityService.php`: single source of truth for
  slot availability. Never duplicate its rules in controllers or views.
- `app/Actions/Reservations/CreateReservation.php`: transaction-safe booking write.
- `app/Contracts/PaymentGateway.php`: provider-independent payment interface.
  `PayMongoGateway` is the only implementation and talks to PayMongo Hosted Checkout QRPh. There
  is no payment simulator: automated tests stub PayMongo HTTP responses with
  `Http::fake()` instead, so the real request and response shapes stay covered.
- `app/Actions/Payments/ConfirmPayment.php`: the single path that marks a payment
  paid, confirms the reservation, and issues the receipt. Both the webhook and
  on-site verification use it so they cannot diverge.
- `app/Http/Controllers/Webhooks/PayMongoWebhookController.php`: looks up the payment
  through PayMongo using the secret API key, validates reference, amount, and currency,
  then processes it idempotently.

When a requirement in this file conflicts with an explicit instruction from the user, follow the user's latest instruction and update this file. When this file conflicts with the instructor's source document, identify the conflict before changing behavior.

## Product Goal

Build a reliable web application where customers can:

1. Register and log in.
2. View court availability by date and time.
3. Choose a court, start time, and duration.
4. Create a reservation without allowing schedule overlap.
5. Pay through an online payment provider or choose on-site payment.
6. Receive confirmation only after verified payment.
7. Download and re-download a lightweight receipt.
8. Receive reservation confirmation and receipt by email.
9. View their reservation status and history.

Administrators must be able to:

1. Manage court names, locations, rental rates, and active status.
2. Add schedule blocks for maintenance, repairs, private events, or other closures.
3. Review reservations and update permitted reservation states.
4. Verify on-site payments.
5. Review payment attempts and verified payment records.
6. Generate reservation, court-utilization, and revenue reports.
7. Review important administrative actions through an audit log.

The instructor later requested a polished user interface. Logic and workflow integrity remain the first implementation priority, but UI work must eventually be clean, responsive, accessible, and presentation-ready.

## Chosen Technical Stack

Use this stack unless the user explicitly changes it:

- Backend: PHP with Laravel, using the latest stable version that is compatible with the development environment. Lock exact versions in dependency files.
- Frontend: Laravel Blade templates, Tailwind CSS, and Alpine.js.
- Calendar: FullCalendar.js or an equivalent calendar component integrated into Blade.
- Database: PostgreSQL.
- Online payment: PayMongo Hosted Checkout QRPh in sandbox mode during development and presentation.
- Email: Laravel Mail. Use Mailtrap during development; final demo email provider may be changed through environment configuration.
- Receipt: server-generated PDF using a maintained Laravel-compatible PDF package.
- Queue: Laravel queue with the database driver initially. Do not require Redis for the first working version.
- Scheduler: Laravel Scheduler for reservation expiration and retry-related jobs.
- Testing: Pest or PHPUnit, following whichever the scaffold selects.
- Local environment: Docker Compose if it can be introduced without delaying the first working version.
- Version control: Git.

Do not introduce React, Vue, a separate API frontend, microservices, Redis, or other infrastructure unless a confirmed requirement needs them. Keep the architecture understandable for an academic team and presentation.

## Architecture

Start with a modular Laravel monolith:

- Browser and Blade UI
- Laravel routes, controllers, requests, policies, services, jobs, and commands
- PostgreSQL database
- PayMongo Hosted Checkout QRPh adapter and signed webhook endpoint
- Email adapter and queued notification jobs
- PDF receipt generator
- Scheduled expiration command or job

Keep external integrations behind application services so they can be replaced or faked during testing. Controllers should coordinate requests; they should not contain large business workflows.

Recommended application boundaries:

- `Auth`: registration, login, account security, and role routing.
- `Courts`: court CRUD, rates, operating state, and schedule blocks.
- `Reservations`: availability, pricing, holds, confirmation, cancellation, and history.
- `Payments`: checkout creation, payment attempts, webhook verification, and on-site verification.
- `Receipts`: immutable receipt records and PDF downloads.
- `Notifications`: queued confirmation email and retries.
- `Reports`: filtered reservation, utilization, and collected-payment reports.
- `Audit`: administrator and security-sensitive actions.

## User Roles and Authorization

Initial roles:

- `customer`
- `admin`

Rules:

- Customers may only view and act on their own profile, reservations, payments, and receipts.
- Administrators may access management screens and reports.
- Authorization must be enforced on the server through middleware, policies, or gates. Hiding a button is not authorization.
- The first admin account should be created through a database seeder or a documented command, never through a publicly selectable registration role.
- Store passwords only through Laravel's supported password hashing system.

## Core Data Model

Use migrations, foreign keys, indexes, and database constraints. Suggested tables follow; adjust naming to Laravel conventions while preserving the concepts.

### users

- `id`
- `name`
- `email` unique
- `password`
- `role` with allowed values `customer` and `admin`
- email verification fields only if email verification is included in the first release
- timestamps

### courts

- `id`
- `name`
- `location`
- `description` nullable
- `hourly_rate` decimal or integer minor units; never floating point
- `is_active`
- timestamps and optional soft deletion

### court_blocks

- `id`
- `court_id`
- `starts_at`
- `ends_at`
- `reason`
- `created_by` administrator
- timestamps

### reservations

- `id`
- unique human-readable `reference_number`
- `user_id`
- `court_id`
- `starts_at`
- `ends_at`
- locked `hourly_rate`
- locked `total_amount`
- `status`
- `payment_method`
- `payment_deadline`
- cancellation or expiration reason where applicable
- timestamps

Initial reservation statuses:

- `pending`: slot is held while awaiting payment.
- `confirmed`: payment was verified and the booking is active.
- `cancelled`: deliberately cancelled by an authorized action.
- `expired`: payment deadline passed before confirmation.
- `completed`: reservation time passed and the booking was fulfilled.

Keep payment status separate from reservation status.

### payments

- `id`
- `reservation_id`
- `method`: `online` or `on_site`
- `provider`
- provider checkout/session/payment identifiers as applicable
- `amount`
- `currency`, initially `PHP`
- `status`
- `paid_at` nullable
- `verified_by` nullable for on-site verification
- provider response metadata stored only when safe and useful
- timestamps

Suggested payment statuses: `pending`, `paid`, `failed`, `cancelled`, `refunded`, and `requires_review`.

Allow more than one payment attempt for a reservation. Never count multiple attempts as multiple reservations or revenue entries.

### payment_events

- unique provider event identifier
- event type
- processing status
- received timestamp
- safe payload or payload hash according to project needs
- processing notes

This table is required for webhook idempotency.

### receipts

- unique receipt number
- `reservation_id`
- `payment_id`
- immutable snapshot of the displayed court, schedule, amount, and payment information
- generated timestamp

### email_outbox or jobs

Track queued email delivery, attempts, successful delivery, and retry state if the selected Laravel queue implementation does not provide sufficient visible history for the project demonstration.

### audit_logs

Track the actor, action, affected entity, safe before/after details, IP address where appropriate, and timestamp. Never store passwords, card information, secrets, or authorization headers.

## Reservation and Availability Rules

These rules are critical and must be covered by tests:

1. Use the facility timezone `Asia/Manila` for displayed schedules and business rules.
2. Store timestamps consistently. Prefer UTC storage with explicit conversion at application boundaries unless the selected database design documents another safe approach.
3. Use half-open time intervals: `[start, end)`. A reservation ending at 11:00 does not conflict with one beginning at 11:00.
4. Two intervals overlap when `existing.start < requested.end` and `existing.end > requested.start`.
5. Pending reservations with a future deadline and confirmed reservations block availability.
6. Expired, cancelled, or failed reservations do not block availability.
7. Court blocks also prevent reservations during their active intervals.
8. Validate that the court is active, the schedule follows operating rules, the start is before the end, and duration follows allowed increments.
9. Availability shown in the UI is advisory. Recheck availability inside the final database transaction before inserting or updating a reservation.
10. Protect against two simultaneous requests booking the same court and time. Prefer a PostgreSQL exclusion constraint or another database-backed concurrency mechanism in addition to application validation.
11. Store the booked rate and calculated total on the reservation. Later court-rate changes must not modify previous reservations.
12. Administrative schedule edits must use the same conflict rules as customer bookings.

Exact operating hours, booking increments, minimum and maximum duration, advance-booking limits, and hold durations remain business decisions. Keep these configurable rather than scattering literals across controllers and views.

## Payment Workflow

### Online payment

1. Validate availability and create a pending reservation with a deadline inside a transaction.
2. Create a PayMongo Hosted Checkout QRPh payment and save its payment identifier on a payment attempt.
3. Redirect the customer to PayMongo's hosted QR page. Wallet credentials must never touch this application.
4. Treat browser success and cancel redirects as user-interface signals only. A redirect must not confirm a reservation.
5. Receive the PayMongo webhook at a dedicated public HTTPS endpoint.
6. Retrieve the checkout session through PayMongo's secret-key endpoint; do not trust the webhook payload alone.
7. Reject or log invalid signatures without updating payment or reservation status.
8. Enforce idempotency using the provider event identifier.
9. Validate the expected reservation association, amount, currency, test/live mode, and payment status.
10. In one database transaction, mark the payment paid, mark the reservation confirmed, create one receipt, and record the processed event.
11. Dispatch email only after the database transaction commits.

If a payment succeeds after the hold has expired and another customer already owns the slot, do not reclaim the slot. Mark the payment for reconciliation and follow the eventual refund/manual-resolution policy.

### On-site payment

1. Create a pending reservation with a configurable deadline.
2. Display the deadline and payment instructions to the customer.
3. Only an administrator may mark the payment verified.
4. Recheck that the reservation is still valid and the slot remains protected.
5. Record the payment, confirm the reservation, and create the receipt atomically.
6. Record the verifying administrator in the payment and audit log.

### Expiration

- A scheduled task expires unpaid reservations after their deadline.
- Availability queries must ignore already-expired holds even if the scheduled task is delayed.
- Expiration releases the slot but retains the historical reservation record.

## Receipt and Email Rules

`ConfirmPayment` issues the receipt inside the same transaction that marks the
payment paid, so a receipt never exists without a verified payment and exactly
one exists per payment.

`app/Services/Receipts/GenerateReceiptPdf.php` renders the receipt row to PDF
with `barryvdh/laravel-dompdf`, which is pure PHP and needs no system binary.
The template is `resources/views/receipts/pdf.blade.php` and uses plain CSS,
because dompdf has no Tailwind. Only values snapshotted onto the receipt row are
printed, so a later court rename or rate change cannot rewrite a receipt a
customer already downloaded.

`ReceiptPolicy::download` is the authorization control, not the route. The
download route sits in the shared authenticated group rather than inside
`role:customer`, because administrators also need it for reconciliation, but
the policy still resolves ownership from the reservation, so one customer can
never open another customer's receipt. `config('reservation.facility_name')`
and `facility_address` are printed on the document.

The confirmation email has a single action button pointing at the online
receipt, and it also attaches the rendered PDF through
`MailMessage::attachData()`. `attachData()` writes to `rawAttachments`, not
`attachments`. `MailMessage::action()` overwrites rather than appending, so
calling it twice silently drops the first button.

`GenerateReceiptPdf::render()` returns the PDF object and `handle()` streams
the download response, so the email attachment and the download route serve
the identical document. The notification is sent from `SendConfirmationEmail`,
which runs after the payment transaction commits, so `toMail()` renders the
PDF at send time from committed data instead of freezing bytes into the queued
payload. Attaching matters here: a link alone requires a login and a stable
`APP_URL`, and during development `APP_URL` points at a tunnel whose hostname
changes whenever cloudflared restarts.

Three emails exist, and the first two must both arrive for the demo to be
believable:

| When | Notification | Sent how |
| --- | --- | --- |
| Reservation created | `ReservationReceived` (reservation slip PDF attached, stamped `PENDING PAYMENT`) | Synchronously inside `Customer\ReservationController@store` |
| Payment verified | `ReservationConfirmed` (PDF receipt attached, stamped `PAID`) | `SendConfirmationEmail` job, `QUEUE_CONNECTION=sync` |
| About 10 minutes before a confirmed slot starts | `ReservationReminder` (reference number, court, exact time) | `reservations:remind` command, run every minute by the scheduler |

The reminder command sends at most one email per reservation: each send is
recorded in `audit_logs` with action `reservation.reminder_sent`, and the
command skips any booking that already has that row. It only looks at
`confirmed` bookings that have not started yet, so cancelled, expired, and
finished slots never trigger a reminder. The lead time comes from
`config('reservation.reminder.minutes_before')` (default 10, `0` switches
reminders off). Like the other emails it needs a running scheduler
(`php artisan schedule:work`) on the facility's computer, because nothing is
delivered while the scheduler is stopped.

Both emails carry the same document, rendered by
`GenerateReceiptPdf` from the same `receipts/pdf` Blade template, so the booking
email and the confirmation email look alike. The only differences are the
document title (`RESERVATION` vs `RECEIPT`), the stamp (`PENDING PAYMENT` vs
`PAID`), and the amount label (`Amount to pay` vs `Amount paid`).
`renderPending()` never saves a `Receipt` row: a receipt number is issued only
when a payment is verified, so the pending slip stays a reservation document.

`ReservationReceived` is the customer's proof at the court: it repeats the
reference number and, for on-site bookings, tells them to present the email at
the court management desk. It is sent inside the request because the facility's
own computer has no queue worker; a queued-only email is silently never
delivered there. Both send paths catch mail failures and report them, so a mail
problem never loses a committed reservation or payment.

After verified payment:

- Generate exactly one logical receipt for the successful payment.
- The receipt must contain reservation reference number, receipt number, customer, court name, court location, reservation date, start time, end time or duration, payment method, amount paid, payment status, and transaction date.
- Customers can download the receipt immediately and later from reservation history.
- Receipt authorization must prevent users from downloading another customer's receipt.
- Send a confirmation email in a queued job after transaction commit.
- Email failure must not reverse a successful payment or confirmed reservation.
- Retry temporary delivery failures without creating duplicate receipts or duplicate payments.
- A verified on-site payment sends the same confirmation email and attached
  receipt as an online payment, so the customer can present it at the court.
- An administrator can resend that confirmation email from the reservation
  record when delivery failed or the address was corrected.

## Terms and Conditions

The instructor requires a terms and conditions page. It is public, so a guest
can read it before signing up: `Route::view('terms-and-conditions', 'public.terms')`
under the name `terms`, rendered by `resources/views/public/terms.blade.php`.
Never put it inside a `auth` or `role:` group.

- The wording repeats the real rules from `config/reservation.php` (operating
  hours, Sunday closure, increment, min/max duration, advance window, lead
  time, online and on-site hold) instead of hardcoding numbers, so a config
  change cannot make the page lie.
- It links from the home page footer and the customer workspace footer.
- `RegisteredUserController@store` validates `terms` with the `accepted` rule,
  and the register form has a required checkbox pointing at the page. Agreeing
  is enforced on the server, not only by the checkbox.

### Email verification

A new self-registered account is not usable until the customer enters a code
sent to their email address. Nothing is stored in the database for this: no
migration, no extra column.

- `App\Services\Auth\EmailVerificationCodes` issues the code and stores only its
  SHA-256 hash, plus the number of wrong tries, in the cache. Codes expire with
  the cache entry, so a stale code disappears on its own.
- `App\Notifications\EmailVerificationCode` delivers it through
  `resources/views/emails/verification-code.blade.php`.
- `EmailVerificationController` serves three routes that sit **outside** the
  verified group: `verification.notice` (the code screen), `verification.verify`,
  and `verification.resend`. Putting them inside the group would make the screen
  redirect to itself.
- `App\Http\Middleware\EnsureEmailIsVerified` (alias `verified.email`) wraps every
  real route, so an unverified account cannot open the dashboard, book a court,
  or reach the admin area.
- `RegisteredUserController@store` sends the first code, and
  `AuthenticatedSessionController@store` sends an unverified customer back to the
  code screen after login instead of the dashboard.
- All limits are in `config/reservation.php` under `email_verification`:
  `expires_minutes`, `max_attempts`, `resend_seconds`, `code_length`. Never
  hardcode them in a view.
- The administrator account is created by `AdminUserSeeder` with
  `email_verified_at` already set, so the gate never locks staff out.

### Refund and no-show policy

Two facility policies are stated in the terms and shown again on the customer
dashboard. Their numbers live in `config/reservation.php` under `refund` and
`no_show`, never hardcoded in a view:

- `refund.admin_fee_percent` (default 15): the administration fee kept from a
  refunded online payment. The customer gets the paid amount minus this cut.
- `refund.request_deadline_hours` (default 24): how late before the slot a
  refund may still be requested.
- `no_show.limit_before_suspension` (default 3): recorded no-shows allowed
  before management may suspend the account.
- `no_show.counting_window_days` (default 180): how long a no-show counts.

`resources/views/customer/dashboard.blade.php` renders a **Policies and rules**
card with three panels (refund, no-show/suspension, payment and arrival) built
from those config values. The admin reservation page also shows the exact
refund breakdown for the receipt, so staff do not compute the cut by hand.

A no-show is not yet stored in the database: there is no no-show column or
status. Staff apply the policy manually. Adding automatic counting needs a
migration and a staff action to record a no-show — do not claim it exists until
both do.

Refunds are released outside the system; the payment is then marked `refunded`
through `PaymentStatus::Refunded`.

## CRUD Requirements

The instructor requires visible Create, Read, Update, and Delete behavior:

- Create: validated court creation and reservation creation.
- Read: summary/list pages and individual detail pages.
- Update: prepopulated edit forms with server-side validation.
- Delete: a confirmation step is mandatory.

For records connected to transactions, preserve history. Archive or soft-delete a court that already has reservations instead of physically deleting it. Physical deletion may be allowed only for safe, unused records. Document this behavior in the paper because it protects referential integrity.

## Security Requirements

- Use Laravel CSRF protection for browser forms.
- Use validated request objects for all write operations.
- Use Eloquent or parameterized queries; never concatenate untrusted SQL.
- Enforce server-side authorization on every protected route.
- Rate-limit login and public webhook endpoints appropriately.
- Verify webhook signatures and idempotency.
- Never store raw card numbers, CVV, PayMongo API keys, SMTP credentials, or other secrets in the database or repository.
- Keep secrets in environment variables and provide safe placeholder names in `.env.example`.
- Escape output by default. Sanitize only where rich text is intentionally supported.
- Use HTTPS in the deployed environment.
- Avoid exposing exception traces or secrets in production responses and logs.
- Validate uploaded files if uploads are later introduced.

## UI Expectations

Logic comes first, but the final UI must be polished. Use a consistent Tailwind design system rather than one-off page styling.

Minimum screens:

- Public landing page
- Registration and login
- Customer dashboard
- Availability calendar
- Reservation form and review
- Payment status page
- Reservation history
- Reservation detail and receipt download
- Admin dashboard
- Court list, detail, create, and edit pages
- Schedule-block management
- Reservation management
- On-site payment verification
- Payment list and detail
- Filtered reports

The UI must be responsive, keyboard usable, clearly label form inputs, show field-level validation, display reservation/payment status consistently, and avoid relying on color alone. Do not spend time on animations before the complete booking workflow works.

## Reports

Implement filtered reports only from verified stored data:

- Reservation report by date range, court, and status.
- Court utilization based on booked time versus configured available time.
- Collected-payment report based only on verified paid payments.

Pending, failed, cancelled, refunded, or duplicate attempts must not inflate collected revenue. Define report formulas in code comments and the academic paper.

## Testing Priorities

Tests must cover meaningful business behavior, especially:

1. Registration, login, logout, role routing, and protected-route access.
2. Admin-only court CRUD and customer isolation.
3. Valid reservation creation.
4. Rejection of invalid dates, times, durations, and inactive courts.
5. Rejection of overlapping pending or confirmed reservations.
6. Acceptance of adjacent reservations.
7. Conflict protection under concurrent or near-concurrent booking attempts.
8. Court-block conflicts.
9. Pending reservation expiration and slot release.
10. Valid, invalid, and duplicate PayMongo webhook events.
11. Amount, currency, reference, or mode mismatch handling.
12. Late successful payment reconciliation.
13. Authorized on-site verification and rejection of unauthorized verification.
14. Exactly one confirmed reservation and receipt after repeated events.
15. Email dispatch after commit and independent retry behavior.
16. Customer receipt ownership checks.
17. Correct report totals and exclusion of unpaid attempts.
18. Administrator resend of the confirmation email for a confirmed booking.

Do not fabricate actual test results in the paper. Record results only after running the implemented tests or a documented manual procedure.

## Implementation Order

Work in small, verifiable vertical slices:

### Phase 1: Foundation

- Scaffold Laravel and frontend assets.
- Configure PostgreSQL.
- Add authentication.
- Add `customer` and `admin` roles.
- Add admin seeder.
- Establish shared layout, navigation, and status components.

### Phase 2: Court CRUD

- Court migrations and models.
- Admin court list, detail, create, edit, and delete/archive confirmation.
- Authorization policies and tests.

### Phase 3: Availability and Reservations

- Court blocks.
- Availability query/service.
- Reservation form, pricing, and transaction-safe creation.
- Customer history and detail pages.
- Expiration scheduler.

### Phase 4: Payments

- Payment-attempt records.
- PayMongo client abstraction.
- Dynamic QRPh payment creation.
- Server-verified, idempotent webhook handler.
- On-site verification.
- Failure and late-payment reconciliation states.

### Phase 5: Receipts and Email

- Immutable receipt records.
- PDF generation and authorized downloads.
- Queued confirmation email and retry behavior.

### Phase 6: Reports and UI Polish

- Reservation, utilization, and payment reports.
- Dashboard summaries based on real data.
- Responsive and accessible visual polish.

### Phase 7: Documentation and Presentation

- Update architecture diagram and sequence diagrams to match actual implementation.
- Complete implementation, security, testing, conclusion, and recommendation sections of the paper.
- Capture real screenshots and test evidence.
- Rehearse online payment, webhook, email, receipt, expiration, and admin workflows.

## Coding Standards for AI Agents

- Inspect the repository and this file before editing.
- Preserve user changes and avoid unrelated rewrites.
- Keep controllers thin and move complex workflows into named services or actions.
- Use form request validation for nontrivial forms.
- Use policies or gates for resource authorization.
- Wrap multi-record state transitions in database transactions.
- Dispatch jobs and notifications after commit when they depend on committed data.
- Use enums or centralized constants for statuses; do not scatter status strings.
- Use decimal values or integer minor units for money; never use floating-point calculations.
- Use explicit timezone handling.
- Create migrations rather than manually modifying a shared database schema.
- Add database indexes for foreign keys and frequent date/status queries.
- Do not log secrets or raw sensitive provider payloads.
- Do not mark placeholder or mocked behavior as production-ready.
- Write tests for reservation, payment, authorization, and reporting rules. Avoid tests that merely duplicate framework behavior.
- Run the relevant formatter and tests after each meaningful implementation slice.
- Update this file if a major architectural or business-rule decision changes.

## Commands

The Laravel 13 scaffold is installed and runs on PHP 8.3 with PostgreSQL 16. All
commands below were executed successfully against this repository.

```bash
# Install
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Run
npm run build
php artisan serve                                   # http://127.0.0.1:8000
php artisan queue:work                              # background email delivery
php artisan schedule:work                           # hold expiration sweeper

# Verify
php artisan test
vendor/bin/pint                                     # format changed files
composer audit
```

`composer run dev` starts the server, queue worker, log viewer, and Vite dev server
together. Run the individual processes above when debugging one in isolation.

### Email delivery is visible in the worker terminal

`SendConfirmationEmail` writes a line to the `stderr` log channel on success and
on permanent failure, so the queue worker terminal shows:

```
CONFIRMATION EMAIL SENT | RSRV-261023-ABC123 | to user@example.com | receipt RCPT-0001
```

This only appears when the queue is **not** `sync`. The development machine uses
`QUEUE_CONNECTION=database` in `.env` for that reason; `QUEUE_CONNECTION=sync`
in `.env.example` stays the default because a groupmate laptop has no worker
running, and a queued-only email would be silently never delivered there. With
`sync` the message is written to `laravel.log` instead of a terminal.

### Asset builds and Node version

Vite 7 (with rolldown) needs Node 20.19+ or 22+. Node 18 fails with
`node:util does not provide an export named 'styleText'`. This machine has
Node 22 under nvm, so build with:

```bash
export PATH="$HOME/.nvm/versions/node/v22.23.2/bin:$PATH"
npm run build
```

Blade edits that add new Tailwind classes do nothing on screen until `npm run build`
runs again, because `public/build/assets/app-*.css` is what the browser loads. If a
new utility class (for example `h-3.5` or `sm:gap-6`) looks unstyled or an icon
renders oversized, the built CSS is stale: rebuild, then `php artisan view:clear`.

```bash
php artisan serve
php artisan queue:work               # required to deliver confirmation emails
```

## Payment Integration

PayMongo Hosted Checkout QRPh is the online provider. `PayMongoGateway` creates a
checkout session and `PayMongoWebhookController` confirms it. The customer is redirected
to PayMongo's hosted page, where QRPh is shown; the app never receives wallet credentials.

PayMongo signs every callback with the endpoint signing secret. The webhook first verifies
the raw request signature, then retrieves the checkout session using the PayMongo secret
API key and compares its paid amount, currency, reference number, and payment ID before
confirming a reservation. Repeated `evt_` deliveries are idempotent.

Use `PAYMONGO_SECRET_KEY=sk_test_...` in development. Register the public endpoint URL
`/webhooks/paymongo` in PayMongo Dashboard → Developers → Webhooks, subscribe to
`checkout_session.payment.paid`, and copy the returned signing secret to
`PAYMONGO_WEBHOOK_SECRET`. The webhook route is excluded from CSRF protection in
`bootstrap/app.php`, using `PAYMONGO_WEBHOOK_PATH`.

## Receiving PayMongo Webhooks Locally

PayMongo cannot call localhost. Start a temporary HTTPS tunnel:

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

Set the returned address plus `/webhooks/paymongo` as `PAYMONGO_WEBHOOK_URL`, run
`php artisan config:clear`, then register that exact address in the PayMongo Test Mode
dashboard. The tunnel URL changes after a restart, so update the dashboard endpoint too.

For a full local demo, run the app server, queue worker, and tunnel in separate terminals:

```bash
php artisan serve
php artisan queue:work
cloudflared tunnel --url http://127.0.0.1:8000
```

## Testing Environment

`phpunit.xml` points tests at the same PostgreSQL server but an isolated `testing`
schema (`DB_SCHEMA=testing`) rather than SQLite. This is required: the
`reservations_no_overlapping_holds` exclusion constraint is PostgreSQL-only, so an
in-memory SQLite database would silently skip the concurrency tests. Create that
schema once with:

```sql
CREATE SCHEMA IF NOT EXISTS testing;
```

Never point tests at the `public` schema. `RefreshDatabase` runs `migrate:fresh`,
which would wipe development data.

## Open Decisions

Do not silently choose permanent business policy for these items. Reasonable configurable defaults may be used to unblock implementation, but record the assumption and make it easy to change:

- Pickleball court names and court count
- Operating days and hours
- Rental rates
- Booking time increments
- Minimum and maximum reservation duration
- Maximum advance-booking period
- Online-payment hold duration
- On-site-payment deadline
- Cancellation and rescheduling policy
- Refund and no-show policy
- Late successful payment resolution
- Whether customers must verify email addresses
- Whether password reset is required for the first presentation
- Final email provider
- Final deployment platform
- Exact report definitions and export formats

Suggested temporary development defaults, subject to confirmation:

- Currency: PHP
- Facility timezone: Asia/Manila
- Booking increment: 30 minutes
- Minimum duration: 1 hour
- Maximum duration: 4 hours
- Online payment hold: 15 minutes
- On-site payment hold: configurable and longer than online payment, initially 24 hours but never beyond the reservation start

## Definition of Done

A feature is done only when:

1. Its server-side validation and authorization are implemented.
2. Its persistence and state transitions are correct.
3. Relevant failure paths are handled.
4. Meaningful automated tests pass, or a documented manual verification exists when automation is impractical.
5. The UI communicates success, failure, and current status clearly.
6. No secrets or sensitive payment data are committed.
7. Documentation reflects the implemented behavior.

The project is ready for final presentation only when a customer can complete an end-to-end booking, a verified webhook or authorized on-site action confirms it, a receipt is downloadable, an email is queued and delivered, overlapping bookings are rejected, expired holds release their slots, and administrators can manage and report on the resulting records.

## Render email adapter

The final Render presentation setup uses Gmail SMTP so confirmation emails can
be delivered to any valid customer email address. Configure `MAIL_MAILER=smtp`,
`MAIL_HOST=smtp.gmail.com`, port `587`, the host Gmail address, and a Google App
Password. Keep the SMTP app password only in Render Environment Variables.
Use `QUEUE_CONNECTION=sync` for a single free web service, or run a queue worker
when the database queue is selected. The optional Resend adapter remains in the
repository for future use, but it is not the final project mail provider.
