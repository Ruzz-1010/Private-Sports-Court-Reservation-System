# Gabay sa Folder Structure

## Private Sports Court Reservation System

Ang project na ito ay isang **Laravel monolith**. Ibig sabihin, nasa iisang application ang website, backend logic, database operations, payment integration, email, at reports.

Maraming folders dahil magkakahiwalay ang responsibilidad ng bawat bahagi. Nakakatulong ito para madaling hanapin, baguhin, i-test, at i-maintain ang code.

## Mabilis na overview

| Folder | Simpleng kahulugan |
|---|---|
| `app/` | Utak at backend logic ng system |
| `routes/` | Mapa ng URLs at requests |
| `resources/` | User interface at frontend source |
| `database/` | Database structure at sample data |
| `config/` | Application settings |
| `public/` | Files na direktang naa-access ng browser |
| `storage/` | Logs, cache, sessions, uploads, at generated files |
| `bootstrap/` | Laravel startup configuration |
| `tests/` | Automated verification ng system |
| `docs/` | Paper, planning files, at diagrams |
| `scripts/` | Development at setup helpers |

---

## 1. `app/` — utak ng system

Dito nakalagay ang pangunahing backend logic. Kasama rito ang paggawa ng reservation, pag-check ng availability, pag-confirm ng payment, paggawa ng receipt, at pag-control ng access.

| Folder | Gamit sa system |
|---|---|
| `app/Actions/` | Mga kumpletong business workflow na nagbabago ng data |
| `app/Console/` | Artisan commands at scheduled operations |
| `app/Contracts/` | Interfaces o kasunduan na sinusunod ng services |
| `app/Enums/` | Central list ng allowed roles, methods, at statuses |
| `app/Exceptions/` | Mga espesyal na application error |
| `app/Http/` | Humahawak sa browser at webhook requests |
| `app/Jobs/` | Background tasks |
| `app/Mail/` | Lugar para sa email classes; placeholder ito sa kasalukuyan |
| `app/Models/` | PHP representation ng database records |
| `app/Notifications/` | Nilalaman at delivery setup ng notifications |
| `app/Policies/` | Permission at ownership rules |
| `app/Providers/` | Application startup at dependency bindings |
| `app/Services/` | Reusable domain logic at external integrations |
| `app/Support/` | Lugar para sa shared helpers; placeholder ito sa kasalukuyan |

### `app/Actions/`

Ang actions ay mga prosesong may malinaw na business purpose.

- `Reservations/CreateReservation.php` — gumagawa ng reservation nang transaction-safe at chine-check ang schedule conflict.
- `Payments/CreateCheckoutSession.php` — nagsisimula ng PayMongo hosted checkout.
- `Payments/ConfirmPayment.php` — nagmamarka ng payment bilang paid, nagko-confirm ng reservation, at gumagawa ng receipt.

### `app/Http/`

| Folder | Gamit |
|---|---|
| `Controllers/Admin/` | Admin dashboard, courts, blocks, payments, reservations, at reports |
| `Controllers/Auth/` | Registration, login, at logout |
| `Controllers/Customer/` | Customer dashboard, availability, booking, checkout, at receipt |
| `Controllers/Webhooks/` | PayMongo server-to-server callback handling |
| `Middleware/` | Nagche-check kung authenticated at tama ang role ng user |
| `Requests/` | Server-side validation ng submitted forms |

Ang controller ang tumatanggap ng request at pumipili kung anong action o service ang tatawagin. Hindi dapat dito inilalagay ang lahat ng complex business logic.

### `app/Models/`

Ang models ang kumakatawan sa database records at relationships:

- `User` — customer o administrator
- `Court` — pickleball court
- `CourtBlock` — maintenance o unavailable schedule
- `Reservation` — booking record
- `Payment` — payment attempt at status
- `PaymentEvent` — provider webhook event para sa idempotency
- `Receipt` — immutable proof of payment
- `AuditLog` — history ng importanteng admin o system actions

### `app/Services/`

| Folder | Gamit |
|---|---|
| `Availability/` | Kinakalkula kung available, pending, o reserved ang slot |
| `Courts/` | Court image upload at safe court deletion |
| `Payments/` | Pakikipag-usap sa PayMongo API |
| `Receipts/` | Pag-render ng receipt bilang PDF |
| `Reports/` | Nakahandang folder para sa report services |
| `Reservations/` | Nakahandang folder para sa reservation services |

### `app/Jobs/` at `app/Notifications/`

- `SendConfirmationEmail.php` — background job para hindi paghintayin ang customer habang nagpapadala ng email.
- `ReservationConfirmed.php` — gumagawa ng confirmation message at nag-a-attach ng PDF receipt.

### `app/Policies/`

Ang policies ang nagdedesisyon kung puwedeng makita o baguhin ng isang user ang specific record. Halimbawa, sariling receipt lang ang puwedeng i-download ng customer, habang puwedeng buksan ng administrator ang records para sa reconciliation.

---

## 2. `routes/` — mapa ng URLs

Dito tinutukoy kung anong controller ang hahawak sa bawat URL o form request.

- `web.php` — homepage, registration, login, customer pages, admin pages, receipt download, at PayMongo webhook.
- `console.php` — scheduled task na nagpapatakbo ng reservation expiration command bawat minuto.

Halimbawa, kapag nagsumite ang customer ng reservation form, ang route ang nagtuturo sa tamang customer controller.

---

## 3. `resources/` — user interface

Dito ine-edit ang frontend source ng application.

| Folder | Gamit |
|---|---|
| `resources/css/` | Styles, colors, spacing, typography, at responsive design |
| `resources/js/` | Browser-side JavaScript at interactions |
| `resources/views/` | Blade templates na nagiging HTML pages |

### Mahahalagang view folders

| Folder | Nilalaman |
|---|---|
| `views/admin/courts/` | Court list, details, creation, at editing |
| `views/admin/court-blocks/` | Maintenance at unavailable schedules |
| `views/admin/payments/` | On-site payment verification |
| `views/admin/reports/` | Reservation at payment reports |
| `views/admin/reservations/` | Admin reservation list at details |
| `views/auth/` | Login at registration |
| `views/customer/availability/` | Court availability |
| `views/customer/reservations/` | Booking form, history, at details |
| `views/components/` | Reusable UI pieces |
| `views/layouts/` | Shared navigation, sidebar, at page shell |
| `views/receipts/` | PDF receipt template |
| `views/paper/` | Generated technical paper layout |
| `views/welcome.blade.php` | Public landing page |

Ang `resources/` ang editable source. Pagkatapos ng frontend build, ang browser-ready assets ay inilalagay sa `public/build/`.

---

## 4. `database/` — database structure at sample data

| Folder | Gamit |
|---|---|
| `migrations/` | Versioned instructions para gumawa o magbago ng tables, columns, indexes, at constraints |
| `seeders/` | Initial admin, courts, at demo data |
| `factories/` | Gumagawa ng sample records para sa automated tests |

May PostgreSQL exclusion constraint ang project para pigilan ang overlapping reservations kahit sabay dumating ang dalawang request.

Hindi naka-save bilang files sa folder na ito ang actual customer bookings. Ang live records ay nasa PostgreSQL database. Ang migrations at seeders ay instructions lamang para ihanda o punan ang database.

---

## 5. `config/` — application settings

| File | Gamit |
|---|---|
| `reservation.php` | Operating hours, duration, timezone, at hold deadlines |
| `payments.php` | Payment provider at webhook settings |
| `database.php` | Database connection |
| `mail.php` | Email delivery |
| `queue.php` | Background jobs |
| `filesystems.php` | File storage |
| `dompdf.php` | PDF generation |
| `auth.php` | Authentication |
| `session.php` | User sessions |
| `cache.php` | Application cache |
| `logging.php` | Logs |

Ang secrets at machine-specific values ay dapat nasa `.env`, hindi direktang naka-hard-code sa config files o naka-commit sa GitHub. Ang `.env.example` ay safe template lamang.

---

## 6. `public/` — browser-accessible files

Ito ang folder na dapat naka-expose sa web server.

- `index.php` — unang entry point ng lahat ng web requests.
- `images/` — logo at static images.
- `favicon.ico` — icon sa browser tab.
- `robots.txt` — instructions para sa search-engine crawlers.
- `build/` — compiled CSS at JavaScript kapag nag-run ng frontend build.

Rule: i-edit ang source sa `resources/`; huwag direktang baguhin ang generated files sa `public/build/`.

---

## 7. `storage/` — runtime files

| Folder | Gamit |
|---|---|
| `storage/app/private/` | Files na hindi dapat direktang public |
| `storage/app/private/receipts/` | Nakahandang private receipt storage |
| `storage/app/public/` | Publicly linkable uploaded files |
| `storage/app/public/courts/` | Uploaded court images |
| `storage/framework/cache/` | Cached data |
| `storage/framework/sessions/` | Session files kapag file driver ang gamit |
| `storage/framework/views/` | Compiled Blade templates |
| `storage/framework/testing/` | Temporary testing files |
| `storage/logs/` | Application at error logs |

Ang actual na paggamit ng ilan sa folders na ito ay nakadepende sa configured cache, session, queue, at storage drivers.

---

## 8. `bootstrap/` — pagsisimula ng Laravel

- `app.php` — application initialization, middleware, routes, at exception configuration.
- `providers.php` — listahan ng application service providers.
- `cache/` — Laravel-generated configuration at service cache.

Sa madaling salita, inihahanda nito ang Laravel bago humawak ng request.

---

## 9. `tests/` — automated verification

| Folder | Chine-check |
|---|---|
| `Feature/Admin/` | Court management, images, blocks, at reports |
| `Feature/Auth/` | Login roles at protected-page access |
| `Feature/Payments/` | PayMongo webhook at on-site verification |
| `Feature/Receipts/` | Receipt PDF, permissions, at email attachment |
| `Feature/Reservations/` | Availability at reservation rules |
| `Feature/Reports/` | Nakahandang folder para sa report-specific tests |
| `Unit/` | Maliit at isolated na logic tests |

Ang feature test ay sumusubok ng magkakaugnay na application behavior. Ang unit test ay nakatuon sa isang maliit na class o rule.

---

## 10. `docs/` — project documentation

| Folder | Gamit |
|---|---|
| `docs/diagrams/` | Exported flowcharts, architecture, at sequence diagrams |
| `docs/papers/` | Project paper, generated PDF, at reference documents |
| `docs/papers/assets/` | School seal at ibang paper assets |
| `docs/planning/` | Project analysis at editable Mermaid diagram sources |

---

## 11. `scripts/` — development helpers

May helper scripts para sa project bootstrap at environment setup. Ginagamit ang mga ito para gawing mas mabilis at consistent ang local installation.

---

## 12. Ibang folders na makikita pagkatapos mag-install

| Folder | Gamit |
|---|---|
| `vendor/` | PHP at Laravel packages na ini-install ng Composer |
| `node_modules/` | Frontend development packages na ini-install ng npm |
| `.git/` | Git history, branches, at repository metadata |

Third-party dependencies ang `vendor/` at `node_modules/`, kaya hindi kailangang isa-isahin ang laman nila sa presentation.

## Ano ang `.gitkeep`?

Ang `.gitkeep` ay placeholder file para maisama sa Git ang empty folder. Hindi ibig sabihin na implemented na ang feature dahil mayroon nang folder. May ilang folders sa project na nakahanda pa lamang para sa future code.

---

## Daloy ng isang reservation

```text
Customer submits a booking
          ↓
routes/web.php
Tinutukoy ang controller na hahawak sa request
          ↓
Middleware + Form Request + Policy
Chine-check ang login, role, permission, at submitted data
          ↓
Controller
Tinatawag ang tamang action o service
          ↓
CreateReservation + AvailabilityService
Chine-check ang schedule at ginagawa ang reservation
          ↓
Model → PostgreSQL
Sine-save ang records at pinipigilan ng database ang overlap
          ↓
Blade View
Ipinapakita ang reservation details at status
```

## Daloy ng online payment

```text
Customer starts checkout
          ↓
CreateCheckoutSession
Gumagawa ng PayMongo hosted checkout
          ↓
Customer pays on PayMongo
Hindi dumadaan sa application ang wallet credentials
          ↓
PayMongo webhook
Pinapadala ang signed payment event sa application
          ↓
PayMongoWebhookController
Vine-verify ang signature at nire-retrieve ang transaction
          ↓
ConfirmPayment
Payment = Paid, Reservation = Confirmed, Receipt created
          ↓
SendConfirmationEmail
Queued email na may attached PDF receipt
```

---

## Maikling presentation script

> Ang project natin ay isang Laravel monolith. Ang `routes` ang mapa ng requests, ang `app` ang backend rules at processes, ang `resources` ang user interface, at ang `database` ang schema at setup data. Ang `storage` ay para sa runtime files, ang `tests` para sa automated verification, at ang `docs` para sa paper at diagrams. Marami ang folders dahil pinaghiwalay ang responsibilities para madaling hanapin, i-test, baguhin, at i-maintain ang bawat bahagi.

## Tatlong bagay na dapat tandaan sa discussion

1. Ang **controller** ang tumatanggap ng request, pero ang complex rules ay nasa actions at services.
2. Ang **models at migrations** ay magkaiba: models ang ginagamit ng PHP code, habang migrations ang nagde-define ng database structure.
3. Ang availability na nakikita sa page ay nire-recheck bago mag-save, at may database constraint pa para maiwasan ang double booking.
