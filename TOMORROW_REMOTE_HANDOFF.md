# Remote Cloud Handoff — San Jose Pickleball Reservation System

## Live application

- URL: https://private-sports-court-reservation-system.onrender.com
- Hosting: Render Web Service
- Repository: https://github.com/Ruzz-1010/Private-Sports-Court-Reservation-System
- Branch: `main`
- Current deployed commit: `2ab4b2a` (`Fix demo payment view layout`)

The Render service is currently live. Free instances may sleep after inactivity, so the first request can take longer than usual.

## Render access and deployment

1. Open the Render dashboard and select the **Court Reservation** project.
2. Open the web service **Private-Sports-Court-Reservation-System**.
3. Use **Deploys** to check build logs and deployment status.
4. For a new GitHub commit, use **Manual Deploy → Deploy latest commit**.
5. A successful deploy must show **Deploy succeeded | Live**.
6. If it fails, inspect the last application log lines first. Blade compile errors usually appear before the Apache startup message.

Do not put API keys, SMTP passwords, or webhook secrets in GitHub or this file. Keep them only in Render Environment Variables.

## Test accounts

### Administrator

- Email: `admin@court.local`
- Password: `admin_dev_12345`

### Demo customers

- `juan@example.com`
- `maria@example.com`
- `pedro@example.com`
- Password for the demo customers: `customer123`

The admin account and demo courts/reservations are maintained by the database seeders. Do not register an admin role through the public registration form.

## Demo payment flow

Render is configured for the presentation/demo payment mode:

1. Log in as a customer.
2. Open **Book pickleball**.
3. Choose an active court, date, start time, and duration.
4. Submit the reservation.
5. Open the payment page and click **Simulate Payment**.
6. The system confirms the payment through the same confirmation action used by the real payment workflow.
7. Open the reservation details to download the PDF receipt.

The demo QR is presentation-only. It does not deduct real money. Real PayMongo QRPh can be enabled later by changing the Render `PAYMENT_GATEWAY` environment variable and supplying valid sandbox credentials.

## Receipt and email behavior

- The PDF receipt is generated after a payment is confirmed and is downloadable from the reservation details page.
- The email notification is queued through Laravel.
- For a simple presentation setup without a separate Render worker, set:

```text
QUEUE_CONNECTION=sync
```

- With `QUEUE_CONNECTION=database`, a separate process must continuously run:

```bash
php artisan queue:work
```

- Email delivery still depends on the SMTP environment variables being correct. Never commit the SMTP app password.

## Render environment variables to verify

Check these in Render → service → **Environment**:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://private-sports-court-reservation-system.onrender.com
PAYMENT_GATEWAY=demo
DB_URL=<Render PostgreSQL internal connection string>
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<host Gmail address>
MAIL_PASSWORD=<Gmail app password>
MAIL_FROM_ADDRESS=<host Gmail address>
MAIL_FROM_NAME=San Jose Pickleball
```

Keep the PayMongo variables configured if the project will switch back to sandbox checkout. Do not share their values in chat or commit them to the repository.

## Database and seed behavior

The Render entrypoint runs migrations. If `SEED_DATABASE_ON_BOOT=true`, it also runs the configured seeders on boot. The seeders are designed to keep the admin, courts, and demo data available for presentation.

Do not run `migrate:fresh` on the production Render database. It deletes the shared presentation data.

## Quick verification checklist for tomorrow

- [ ] Open the live URL.
- [ ] Login as admin and verify the admin dashboard loads.
- [ ] Login as a demo customer and verify customer dashboard loads.
- [ ] Check that courts are available from 9:00 AM to 9:00 PM.
- [ ] Create a test reservation.
- [ ] Complete **Simulate Payment**.
- [ ] Verify the reservation changes to confirmed.
- [ ] Download and open the PDF receipt.
- [ ] Check the customer email inbox for the receipt email.
- [ ] Check Render deploy logs if any page returns a server error.

## Important safety notes

- Use sandbox/demo payment mode for the presentation.
- Never use real card details in the application.
- Rotate SMTP and PayMongo credentials if they were pasted into a public chat, screenshot, commit, or shared document.
- If a code change is made, commit and push it to `main`, then deploy the latest commit in Render.
