# Private Pickleball Court Reservation System

## Purpose and source documents

Build a reservation system for a private pickleball facility in Barangay San Jose, Puerto Princesa City, Palawan. Customers check availability, reserve a court, pay online or on-site, and obtain receipts. Administrators manage courts, schedules, bookings, payments, and reports.

This analysis is based on the complete text of `FINAL SYSTEM INTEGRATION PROJECT.docx` and `private-resrvation.docx`. The latter's embedded image is a college logo. The folder currently contains these two source documents and no application code. The paper's external references have not been independently reviewed.

The assignment requires a strictly functional UI using basic native controls. Grading emphasizes system logic, workflow integrity, and backend integration. A visual design framework is unnecessary.

## Confirmed requirements

| Area | Required behavior |
| --- | --- |
| Accounts | Customer registration and login, authentication, and customer/admin authorization |
| Availability | Calendar showing Available, Pending, and Reserved for a court and selected interval |
| Reservations | Select court, date, start time, and duration; validate inputs and prevent overlapping reservations |
| Online payment | Integrate a third-party sandbox or mock API; confirm only after verified payment |
| Webhooks | Secure server-to-server endpoint that updates stored payment and reservation status |
| On-site payment | Temporarily hold the slot; administrator records/verifies payment; automatically cancel unpaid bookings after a defined period |
| Receipts | Download immediately after verified payment and re-download from history; email confirmation and receipt |
| Receipt contents | Reference number, court name/location, reservation date, start time, duration, paid amount, and payment status |
| Administration | Manage court information and rates, schedules, reservations, maintenance/event blocks, and payment records |
| Reports | Reservation activity, court utilization, and collected payments |
| CRUD | Validated creation, summary/list view, individual detail view, prepopulated edit form, and deletion with confirmation |
| Persistence | Actual database storage that survives application restarts |
| Security | Password protection, role enforcement, validated inputs, injection prevention, HTTPS, and no raw card storage |
| Email | External email service triggered by lifecycle events; background delivery recommended |
| Reliability | Consistent records, backup/recovery, maintainable structure, and lightweight downloadable receipts |

## Proposed architecture

These are implementation proposals, not technology choices specified in the documents.

Use one web application with server-rendered pages, a relational database, a payment-provider adapter, an email adapter, and a background worker/scheduler. This keeps the academic project manageable while preserving clear subsystem boundaries. Choose the language and framework based on team familiarity before implementation.

Browser → application → database. The application creates checkout sessions with the payment provider. The provider sends authenticated webhooks to the application. A committed successful payment creates a receipt and a queued email task. The worker sends the email; the scheduler expires unpaid holds.

## Proposed data model

- Users: account identity, password hash, role, and timestamps.
- Courts: name, location, rental rate, active status, and operating rules.
- Court blocks: court, start/end timestamps, reason, and administrator.
- Reservations: unique reference, customer, court, start/end timestamps, quoted amount, lifecycle status, payment deadline, and timestamps.
- Payments: reservation, method, amount/currency, provider identifiers, status, verification details, and timestamps. Allow multiple attempts without counting them as multiple bookings.
- Payment events: unique provider event ID and processing outcome to prevent duplicate processing.
- Receipts: unique receipt number, verified payment association, and an immutable snapshot of receipt details.
- Email outbox: event identity, recipient, delivery status, attempts, and retry schedule.
- Audit records: important administrator changes and payment verification actions.

Availability should be calculated from bookings, active holds, operating hours, and court blocks. A single status field on a court cannot describe availability across dates and times.

## Proposed workflow and integrity rules

1. Customer selects an available interval and payment method.
2. Server validates operating hours, duration, pricing, court blocks, and conflicts.
3. Within a database transaction, create a pending reservation with a payment deadline. Protect the conflict check and insert against simultaneous requests.
4. Online payment creates a provider checkout session. On-site payment displays the deadline and instructions.
5. A verified provider event or authorized on-site verification records payment and confirms the reservation atomically.
6. Generate an immutable receipt and enqueue the confirmation email after successful payment is recorded.
7. Expire unpaid holds automatically and release availability. Availability checks must also ignore expired holds even if the scheduler is delayed.
8. Customer can view reservation history and download authorized receipts.

Proposed reservation states: pending, confirmed, cancelled, expired, and completed. The paper describes automatic cancellation after the deadline; an internal expired state can preserve the reason while displaying cancelled to the customer. Keep payment states separate from reservation states.

Use half-open intervals: a booking ending at 11:00 can be followed by one starting at 11:00. Overlap exists when an existing start is before the proposed end and its end is after the proposed start. Enforce this rule at the database/transaction boundary, including administrative edits and schedule blocks.

Use the facility timezone, Asia/Manila, for display and business rules. Store timestamps consistently and use precise monetary values rather than floating-point arithmetic. Save the booked price so later rate changes do not alter existing reservations.

Never confirm a payment based only on a browser redirect. Authenticate webhooks using the selected provider's documented mechanism, validate amount/currency and booking association, and process repeated events idempotently. A late success after hold expiry must not reclaim an already booked slot; record it for reconciliation under an agreed refund or manual-resolution policy.

Failed email delivery must not undo a successful payment. Retry delivery independently without generating another receipt or payment. Customers must only access their own reservations and receipts.

Meet the assignment's delete requirement through a confirmed delete operation for an unused court or other safe record. Retain referenced booking/payment history and archive courts with existing reservations. Confirm this interpretation with the instructor if physical deletion of transaction records is expected.

## Decisions still needed

- Team's preferred language/framework, database, and deployment environment.
- Number and type of courts, rates, operating hours, booking increments, minimum/maximum duration, and advance-booking limits.
- Online and on-site hold durations, particularly for bookings close to the start time.
- Cancellation, rescheduling, no-show, refund, and late-payment policies.
- Payment sandbox/mock provider, supported methods, currency, and email service.
- Whether account email verification and password reset are in initial scope.
- Whether paid booking edits are prohibited or handled through an adjustment workflow.
- Definitions and date filters for utilization and revenue reports.

## Paper gaps and inconsistencies

- The overview describes online payment, while the problem statement, objectives, and functional requirements support both online and on-site payment. Implement both and align the overview later.
- The main use-case narrative omits the on-site branch, hold expiration, payment failure, and late/duplicate callbacks.
- The required payment deadline is unspecified.
- The paper currently covers the introduction, requirements, use cases in prose, and references. Architecture/design, implementation details, testing results, and conclusion remain to be developed.
- Formal use-case and payment sequence diagrams are absent.
- The assignment says five group members but lists role allocations totaling six; team role allocation needs clarification or an overlapping role.
- A previously downloaded receipt can be available offline; receiving a new email or downloading a receipt still requires connectivity. Clarify this wording in the paper.

## Build sequence and acceptance evidence

1. Agree on business rules and stack; create the schema, migrations, seed data, and environment setup.
2. Implement accounts, role enforcement, court CRUD, list/detail views, and deletion confirmation.
3. Implement availability, schedule blocks, booking transactions, history, and hold expiry.
4. Integrate provider checkout and authenticated, idempotent webhooks; implement on-site verification.
5. Add receipts, an email outbox/worker, retries, and administrator reports.
6. Test complete workflows, document actual results, configure HTTPS and backups, and rehearse the presentation.

Essential acceptance cases: data persists after restart; invalid input is rejected; two concurrent overlapping requests cannot both succeed; adjacent bookings work; maintenance blocks prevent booking; unpaid holds release slots; unauthorized actions and cross-customer receipt access fail; spoofed callbacks fail; duplicate callbacks do not duplicate payments/receipts; late payment does not create a booking conflict; interrupted checkout and missing callbacks can be reconciled; email outages do not undo payment; receipts remain downloadable from history; deletion requires confirmation and preserves referenced records.

Use actual implementation decisions and observed test results when completing the paper. Do not describe planned integrations as completed or fabricate test outcomes.
