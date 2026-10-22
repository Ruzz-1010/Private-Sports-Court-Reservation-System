# Resend email on Render (optional, not the final project setup)

The final project setup uses Gmail SMTP so confirmation emails can be delivered
to any valid customer email address. Use this Resend configuration only if the
team intentionally chooses a verified Resend sender/domain later.

The `resend` Laravel mailer is registered by AppServiceProvider using
App/Mail/Transport/ResendApiTransport. It uses Laravel's already installed HTTP
client, so no additional Composer SDK or SMTP connection is required. Existing
reservation notifications and PDF attachments are sent through the same mailer.

## Web service environment

```env
MAIL_MAILER=resend
RESEND_API_KEY=YOUR_SECRET_KEY_IN_RENDER_ONLY
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="San Jose Pickleball"
QUEUE_CONNECTION=sync
```

Set APP_URL to the full HTTPS Render address. Save environment changes and deploy
the latest commit. Never commit the API key.

The testing sender onboarding@resend.dev may only send to the email associated
with the Resend account. For this presentation use a customer account whose email
is sanjosepickleballreservation@gmail.com, provided it is also the Resend account
email. Sending to other customers requires a verified domain and a sender address
on that domain. Gmail is not a domain you can verify or use as the Resend sender.

## Test after deployment

1. Make a new online demo booking with that customer account.
2. Open payment and choose Simulate Payment.
3. Confirm the reservation and receipt are available.
4. Open Resend's Emails/Logs to inspect the request and delivery status.
5. Check Gmail Inbox and Spam.

A Resend acceptance ID is not proof that a message reached the inbox. Delivery
must be checked in Resend and the recipient mailbox. The transport throws on
API rejection rather than silently treating a failed email as delivered.

With queue=sync no worker is needed, but failed email is not automatically
retried. With queue=database a running queue worker is required.

## Verification limits

Tests use Http::fake and never send live email. Run:

```sh
php artisan test --filter=ResendApiTransportTest
vendor/bin/pint --dirty
```

PHP/Composer are unavailable in the editing workspace, so these tests were added
but not executed here. Live delivery is not yet verified.

References:
- https://resend.com/docs/api-reference/emails/send-email
- https://resend.com/docs/knowledge-base/403-error-resend-dev-domain
