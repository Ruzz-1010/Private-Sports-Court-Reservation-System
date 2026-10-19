<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use Illuminate\Console\Command;

class CheckPaymentConfig extends Command
{
    protected $signature = 'config:check-integrations {--json : Emit machine readable output}';

    protected $description = 'Report payment and email readiness without revealing secrets';

    public function handle(PaymentGateway $gateway): int
    {
        $checks = [
            'paymongo_secret_key' => $this->has('payments.paymongo.secret_key'),
            'paymongo_webhook_secret' => $this->has('payments.paymongo.webhook_secret'),
            'mailtrap_username' => $this->has('mail.mailers.mailtrap.username'),
            'mailtrap_password' => $this->has('mail.mailers.mailtrap.password'),
        ];
        $ready = $checks['paymongo_secret_key'] && $checks['paymongo_webhook_secret'] && $this->hasPublicUrl();
        $data = ['ready' => $ready, 'driver' => $gateway->name(), 'base_url' => config('payments.paymongo.base_url'), 'webhook_url' => $this->publicUrl(), 'webhook_reachable' => $this->hasPublicUrl(), 'checks' => $checks];
        if ($this->option('json')) {
            $this->line((string) json_encode($data, JSON_PRETTY_PRINT));

            return $ready ? self::SUCCESS : self::FAILURE;
        }
        $this->table(['Setting', 'State'], [
            ['Gateway', $gateway->name()], ['PayMongo secret key', $this->stateOf('payments.paymongo.secret_key')], ['Webhook signing secret', $this->stateOf('payments.paymongo.webhook_secret')], ['Webhook URL', $this->publicUrl()], ['Public HTTPS URL', $this->hasPublicUrl() ? 'set' : 'MISSING'], ['Mailtrap', $checks['mailtrap_username'] ? 'set' : 'not configured (log fallback)'],
        ]);
        if (! $ready) {
            $this->warn('Add the PayMongo Test Secret Key and webhook signing secret, then run php artisan config:clear.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function publicUrl(): string
    {
        $url = config('payments.webhook.public_url');

        return is_string($url) && $url !== '' ? rtrim($url, '/') : rtrim((string) config('app.url'), '/').'/'.config('payments.webhook.path');
    }

    private function hasPublicUrl(): bool
    {
        return preg_match('#^https://(?!localhost|127\.0\.0\.1|\[::1\])#', $this->publicUrl()) === 1;
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
