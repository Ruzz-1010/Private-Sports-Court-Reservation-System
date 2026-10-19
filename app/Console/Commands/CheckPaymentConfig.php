<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use Illuminate\Console\Command;

/**
 * Reports whether the payment and email integrations are actually configured.
 *
 * Run this before a presentation. It never prints a secret, only whether each
 * value is present, because this command's output is often shown on screen.
 */
class CheckPaymentConfig extends Command
{
    protected $signature = 'config:check-integrations {--json : Emit machine readable output}';

    protected $description = 'Report payment gateway and email readiness without revealing secrets';

    public function handle(PaymentGateway $gateway): int
    {
        $checks = [
            'maya_public_key' => $this->has('payments.maya.public_key'),
            'maya_secret_key' => $this->has('payments.maya.secret_key'),
            'mailtrap_username' => $this->has('mail.mailers.mailtrap.username'),
            'mailtrap_password' => $this->has('mail.mailers.mailtrap.password'),
        ];

        $ready = $checks['maya_public_key'] && $checks['maya_secret_key'];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ready' => $ready,
                'driver' => $gateway->name(),
                'base_url' => config('payments.maya.base_url'),
                'webhook_path' => config('payments.webhook.path'),
                'webhook_url' => $this->publicUrl(),
                'webhook_reachable' => $this->hasPublicUrl(),
                'mailer' => config('mail.default'),
                'queue' => config('queue.default'),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));

            return $ready ? self::SUCCESS : self::FAILURE;
        }

        $this->line('Payment gateway');
        $this->line('  driver ......... '.$gateway->name());
        $this->line('  base url ....... '.config('payments.maya.base_url'));
        $this->line('  public key ..... '.$this->stateOf('payments.maya.public_key'));
        $this->line('  secret key ..... '.$this->stateOf('payments.maya.secret_key'));
        $this->line('  webhook path ... /'.config('payments.webhook.path'));
        $this->line('  verification ... server to server lookup before any state change');

        $this->newLine();
        $this->line('Webhook URL to register in the Maya dashboard');
        $this->line('  '.$this->publicUrl());
        $this->line('  status ........ '.($this->hasPublicUrl() ? 'publicly reachable' : 'LOCAL, Maya cannot call it'));

        if (! $this->hasPublicUrl()) {
            $this->newLine();
            $this->warn('  Expose this machine, then set APP_URL to the tunnel address:');
            $this->warn('    cloudflared tunnel --url http://127.0.0.1:8000');
            $this->warn('    APP_URL=https://<subdomain>.trycloudflare.com');
        }

        $this->newLine();
        $this->line('Email');
        $this->line('  mailer ......... '.config('mail.default'));
        $this->line('  mailtrap user .. '.$this->stateOf('mail.mailers.mailtrap.username'));
        $this->line('  mailtrap pass .. '.$this->stateOf('mail.mailers.mailtrap.password'));
        $this->line('  fallback ....... log driver, so a mail outage cannot fail a job');
        $this->line('  queue .......... '.config('queue.default'));

        $this->newLine();

        if (! $ready) {
            $this->error('  Maya keys are missing. Online payment cannot start a checkout.');
        }

        if (! $checks['mailtrap_username']) {
            $this->warn('  Mailtrap is not configured. Emails fall back to the log driver.');
        }

        if (config('queue.default') === 'sync') {
            $this->warn('  QUEUE_CONNECTION is sync. Run "php artisan queue:work" in a second terminal.');
        }

        $this->warn('  The queue worker must be running in a second terminal or no confirmation email is delivered.');

        $this->newLine();

        if ($ready) {
            $this->info('Ready.');
        } else {
            $this->error('Not ready. Fill in the missing values in .env, then run php artisan config:clear.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    /**
     * The address Maya must be able to reach.
     */
    private function publicUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.config('payments.webhook.path');
    }

    private function hasPublicUrl(): bool
    {
        $url = (string) config('app.url');

        return preg_match('#^https://#', $url) === 1
            && preg_match('#^(localhost|127\.0\.0\.1|\[::1\])#', $url) === 0;
    }

    private function stateOf(string $key): string
    {
        return $this->has($key) ? 'set' : 'MISSING';
    }

    private function has(string $key): bool
    {
        $value = config($key);

        return is_string($value) && $value !== '';
    }
}
