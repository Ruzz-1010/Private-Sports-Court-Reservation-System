# Paper and System Gap Analysis

Audit date: 2026-10-25

This report compares `docs/papers/SYSIN_Laravel_System_Paper.docx` with the Laravel source currently in the repository. It identifies what the paper can claim, what must be corrected, and what still needs implementation or live verification.

## Overall result

The implemented system covers the core reservation, payment, receipt, authorization, court management, schedule block, reporting, email, and reminder workflows. The paper is generally aligned with the architecture, but it is not yet safe to submit unchanged because several statements describe an earlier implementation or claim verification that has not been reproduced in the current workspace.

The most important documentation corrections are:

1. Update the email section to describe the current three-email behavior: reservation received, payment confirmed with PDF receipt, and pre-booking reminder.
2. Remove the claim that the confirmation email has a `View receipt online` button. The current notification attaches the PDF and no longer renders that button.
3. Replace the hard-coded test result of 131 passed tests with the latest verified result. The current PHPUnit discovery lists 158 tests, but the suite cannot currently be reported as passed because the local PostgreSQL server is unavailable on `127.0.0.1:5432`.
4. Reconcile the email-provider description. The paper says Mailtrap plus a log fallback, while the application supports SMTP and a custom Resend HTTPS transport and the deployed environment has been switched between Resend and Gmail SMTP during testing.
5. Add the administrator confirmation-email resend workflow, which exists in the application but is missing from the paper's functional requirements and test matrix.
6. Update the availability workflow to mention that the selected `court_id` is honored by the availability page. This was recently fixed after a bug caused the court selector to continue displaying the previous court's slots.

## Feature comparison

| Area | Paper claim | Current source evidence | Result | Required action |
|---|---|---|---|---|
| Registration and roles | Customer registration; admin role is not selectable | `RegisteredUserController`, role middleware, `AdminUserSeeder` | Implemented | Keep claim; add email-verification code flow to the paper |
| Email verification | Not described in the main requirements | `EmailVerificationCodes`, `EmailVerificationController`, `EnsureEmailIsVerified`, verification notification and tests | Implemented but undocumented | Add FR/SUC, security details, and test cases |
| Availability | Court/date slot states and expired-hold handling | `AvailabilityService` | Implemented | Keep claim; add a selected-court regression test and workflow note |
| Court selector | Implied by the calendar | `AvailabilityController` now resolves the query `court_id` for both HTML and JSON slot feeds | Recently fixed | Update paper only after the deployed build includes this fix |
| Reservation creation | Transactional hold, price snapshot, overlap protection | `CreateReservation`, PostgreSQL exclusion constraint, request validation | Implemented | Keep claim |
| Court CRUD | Create, read, update, archive/delete, image, active state | `Admin/CourtController`, `DeleteCourt`, image migration, image tests | Implemented | Keep claim; mention archive behavior explicitly |
| Schedule blocks | Admin block and removal of court intervals | `Admin/CourtBlockController`, `CourtBlock` model, tests | Implemented | Keep claim |
| Online payment | PayMongo hosted checkout, signed webhook, independent retrieval | `PayMongoGateway`, `PayMongoWebhookController`, `ConfirmPayment` | Implemented in code | Keep claim; record the exact sandbox/live manual verification separately |
| On-site payment | Admin verifies/rejects with a reason | `PaymentVerificationController`, `ConfirmPayment`, audit log | Implemented | Keep claim; add resend-after-verification path |
| Payment simulation | Paper says PayMongo is the provider and no simulator is intended | `DemoPaymentController` and `PAYMENT_SIMULATION_ENABLED` still exist | Inconsistent | Decide whether demo simulation is presentation-only; if retained, label it clearly as a test-only path and document that it is not production payment |
| Receipt PDF | One immutable receipt per verified payment; authorized download | `Receipt`, `GenerateReceiptPdf`, `ReceiptController`, `ReceiptPolicy`, tests | Implemented | Keep claim; verify PDF response and attachment against the current build |
| Pending reservation email | Not fully described in the paper | `ReservationReceived` sends synchronously during reservation creation and attaches a pending-payment slip | Implemented but undocumented | Add to the email workflow and test matrix |
| Payment confirmation email | Queued email with receipt PDF | `SendConfirmationEmail`, `ReservationConfirmed`, `attachData()` | Implemented | Correct wording; no online receipt action button in current code |
| Reminder email | Not fully described in the paper | `SendReservationReminders`, `ReservationReminder`, scheduler entry, audit idempotency | Implemented but undocumented | Add requirements, scheduler setup, and one-send rule |
| Email resend | Missing from paper | `Admin/ReservationController@resendConfirmation`, admin route and audit entry | Implemented but undocumented | Add FR, use case, admin screenshot, and test |
| Reports | Filtered reservations, utilization, paid revenue | `Admin/ReportController` and report tests | Implemented | Keep claim; document utilization formula and date/timezone assumptions |
| Audit logs | Security-sensitive and admin actions are audited | `AuditLog` writes in payment, court, reminder, resend flows | Partially documented | Add a concrete audit-log subsection and fields stored |
| Terms and conditions | Not present in the earlier requirements | public terms route/view, registration `accepted` validation, tests | Implemented but undocumented | Add a public terms requirement and registration acceptance evidence |
| Password policy | Paper says password validation but does not list current centralized policy | `AppServiceProvider`, `config/reservation.php`, registration view | Implemented but under-described | Add exact configurable rules without claiming uncompromised-password checks |
| Refund/no-show policy | Paper lists future recommendations only | Terms, dashboard, config display policy; no no-show database state or automatic suspension | Policy-only | State clearly that enforcement is manual and not implemented as an automated feature |
| Deployment email | Mail provider is described as Mailtrap/log fallback | Render configuration and custom Resend transport support SMTP/Resend | Outdated | Replace with environment-configurable SMTP/Resend wording and record the provider used for the demo |
| Queue | Paper says database queue and asynchronous confirmation email | `SendConfirmationEmail` is queueable; Render was configured with `sync` during delivery troubleshooting | Environment-dependent | State that database queue is the default local design, while `sync` is acceptable for a no-worker demo only if documented |
| Scheduler | Expiration every minute | `routes/console.php` schedules expiration and reminders | Implemented | Add the required `schedule:work` command to the deployment/demo checklist |

## Claims that must not be submitted without fresh evidence

### Test totals

The paper reports 131 tests passed. Current test discovery lists 158 tests. Running the availability suite in this workspace produced database connection errors because PostgreSQL was not available at `127.0.0.1:5432`; therefore the paper must not continue to state that 131 tests currently pass. Start PostgreSQL, create the isolated testing schema, run the full suite, and replace Appendix B with the exact PHPUnit summary from that run.

### Live email delivery

The source code can send to the customer's stored `users.email` and attach the PDF. That proves routing and attachment behavior, not delivery to every provider. The paper should report a live inbox result only after a successful SMTP or Resend send is observed. For Resend, arbitrary customer addresses require a verified sender domain; for Gmail SMTP, the sender account needs a Google App Password.

### PayMongo completion

The code verifies the webhook signature and retrieves the checkout session independently. A test-mode successful checkout and webhook should be recorded with the actual event, amount, currency, reservation reference, receipt number, and resulting email. A browser redirect alone is not proof of payment.

## Missing evidence to add to the paper package

- Customer registration and email-verification screenshots.
- Availability screenshots showing Court 1 occupied while the same time remains available on Court 2.
- Pending reservation email with the pending-payment PDF slip.
- PayMongo hosted checkout/QRPh screen and webhook result.
- Confirmed reservation email with the attached paid PDF receipt.
- Admin resend-confirmation screen and audit-log entry.
- Reminder command/scheduler evidence.
- Admin report filters and the collected-payment total excluding unpaid attempts.
- PostgreSQL exclusion-constraint migration and the passing concurrency test output.
- A deployment configuration table that names only variable names, never real API keys or passwords.

## Recommended paper edits by chapter

### Chapter 1

Keep the pickleball-only scope. Add that customers receive a pending reservation slip immediately, a paid receipt after verification, and a reminder shortly before the slot. Do not imply that a payment success redirect confirms a booking.

### Chapter 2

Add requirements for email verification, pending reservation email, reminder email, administrator resend, terms acceptance, and the selected-court availability behavior. Change the receipt use case from “links to the PDF receipt” to “attaches the PDF receipt”; the online download remains available from reservation history.

### Chapter 3

Update the email row to “Laravel Mail with environment-configurable SMTP or Resend transport.” Add the reminder scheduler and the email-verification cache flow to the architecture diagram. Ensure the diagrams show the selected court flowing into the availability query.

### Chapter 4

Replace the old confirmation-email paragraph that describes an action button. Describe the actual PDF attachment, concise message body, queue behavior, retries, and admin resend. Add the current `ReservationReceived` synchronous path and the `reservations:remind` scheduled command.

### Chapter 5

Rebuild the test table from the current suite. Do not copy the old 131 count. Add cases for email verification, pending email, reminder idempotency, admin resend, terms acceptance, selected-court availability, and attachment contents.

### Chapter 6 and Appendix B

Rewrite the achievement summary from the latest passing run. Separate “implemented in source” from “verified live.” Include the local PostgreSQL/testing-schema command and the exact date, commit, PHP/Laravel/PostgreSQL versions used for the recorded run.

## Current review conclusion

The system is ahead of the paper in several areas, but the paper currently overstates verification and contains stale descriptions. The next safe step is to update the paper from this gap list, run the complete PostgreSQL-backed test suite, and capture live PayMongo/email evidence. Do not submit the existing DOCX until those three items are complete.
