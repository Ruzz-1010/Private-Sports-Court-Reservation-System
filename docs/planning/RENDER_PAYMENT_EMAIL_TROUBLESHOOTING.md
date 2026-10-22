# Payment and email on Render

## Confirmed code issue

The demo payment controller previously confirmed a payment without dispatching
SendConfirmationEmail. It now dispatches after the confirmation transaction
returns, rejects expired holds and real-provider payments, and keeps a successful
payment/receipt accessible even if synchronous email delivery throws.

The QR on the demo screen is decorative. It cannot be scanned to transfer
money. Use Simulate Payment for the presentation, or configure the real PayMongo
gateway and webhook for actual sandbox checkout.

## Email delivery requirements

Render Free web services block outbound SMTP ports 25, 465, and 587.
Gmail SMTP on port 587 therefore cannot deliver from that service tier.
Changing a Gmail password or Laravel queue setting does not remove the block.

Source: https://render.com/docs/free#other-limitations

For this hosting tier, configure an email provider's HTTPS API, using a verified
sender and its supported Laravel transport. The repository currently has mailer
configuration for Resend, Postmark, and SES, but their required SDK dependencies
are not installed in composer.json. Setting MAIL_MAILER=resend alone is not
sufficient; install the matching SDK and update composer.lock before deployment.

Alternatively use a paid web service that supports the chosen SMTP port.
Keep provider keys and SMTP passwords in Render's environment settings.

QUEUE_CONNECTION=sync processes email in the request (no separate worker);
a failure is reported and the demo confirmation remains available, but sync
delivery is not retried automatically. With QUEUE_CONNECTION=database run a
separate queue worker, otherwise email jobs will wait in the jobs table.

## Verification and next evidence

The added DemoPaymentTest covers confirmation/email dispatch, repeated clicks,
expired holds, isolation, real-provider rejection, and sync email failure.
These tests were authored but not run in this workspace, which lacks PHP and
Composer. Run against the isolated testing schema:

    php artisan test --filter=DemoPaymentTest
    vendor/bin/pint --dirty

After deploying, make a new demo booking, open checkout, and simulate payment.
Check that its status is confirmed and the PDF receipt is available. Inspect
Render Logs for the email error and the active mail/queue configuration.
Do not share credentials or entire database URLs.
