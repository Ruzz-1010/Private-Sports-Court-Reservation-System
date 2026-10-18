<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Reports whether the payment and email integrations are actually configured.
 *
 * Run this before a presentation. It never prints a secret, only whether each
 * value is present.
 */
class CheckPaymentConfig extends Command
{
    protected $signature = 'config:check-integrations';

    protected $description = 'Report payment gateway and email readiness without revealing secrets';

    public function handle(PaymentGateway $gateway): int
    {
        $this->line('Payment gateway');
        $this->line('  active driver .... '.$this->fmt($gateway->name()));

        if ($gateway->name() === 'paymongo') {
            $this->line('  secret key ....... '.$this->stateOf('payments.paymongo.secret_key'));
            $this->line('  public key ....... '.$this->stateOf('payments.paymongo.public_key'));
            $this->line('  webhook secret ... '.$this->stateOf('payments.paymongo.webhook_secret'));
            $this->line('  webhook path ..... /'.config('payments.webhook.path'));
        } else {
            $this->line('  simulator ........ enabled, no credentials required');
            $this->line('  webhook secret ... '.$this->stateOf('payments.paymongo.webhook_secret'));
        }

        $this->newLine();
        $this->line('Email');
        $this->line('  active mailer .... '.config('mail.default'));
        $this->line('  mailtrap user .... '.$this->stateOf('mail.mailers.mailtrap.username'));
        $this->line('  mailtrap pass .... '.$this->stateOf('mail.mailers.mailtrap.password'));
        $this->line('  fallback ........ log driver, so a mail outage cannot fail a job');
        $this->line('  queue ........... '.config('queue.default'));

        $this->newLine();

        $ready = true;

        if (config('payments.gateway') === 'paymongo') {
            $ready = $this->require('payments.paymongo.secret_key', 'PayMongo secret key')
                && $this->require('payments.paymongo.webhook_secret', 'PayMongo webhook secret');
        } elseif (! config('payments.paymongo.webhook_secret')) {
            $this->warn('  Webhook secret is empty. Signed callbacks will be rejected.');
        }

        if (Mail::mailer() && ! $this->has('mail.mailers.mailtrap.username')) {
            $this->warn('  Mailtrap is not configured. Emails will fall back to the log driver.');
        }

        if (config('queue.default') === 'sync') {
            $this->warn('  QUEUE_CONNECTION is sync. Run "php artisan queue:work" in a second terminal.');
        }

        $this->newLine();

        if ($ready) {
            $this->info('Ready.');
        } else {
            $this->error('Not ready. Fill in the values above in .env, then run php artisan config:clear.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function stateOf(string $key): string
    {
        $value = config($key);

        return $this->has($key) ? 'set' : 'MISSING';
    }

    private function has(string $key): bool
    {
        $value = config($key);

        return is_string($value) && $value !== '';
    }

    private function require(string $key, string $label): bool
    {
        if ($this->has($key)) {
            return true;
        }

        $this->error("  {$label} is missing.");

        return false;
    }

    private function fmt(mixed $value): string
    {
        return is_string($value) ? $value : (string) $value;
    }
}
