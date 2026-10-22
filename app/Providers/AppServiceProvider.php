<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Mail\Transport\ResendApiTransport;
use Illuminate\Support\Facades\Mail;
use App\Services\Payments\PayMongoGateway;
use App\Services\Payments\DemoGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind the payment gateway chosen by configuration.
     *
     * The rest of the application depends on the interface, so a second
     * provider can be introduced later without touching reservation, receipt,
     * or reporting logic.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn (): PaymentGateway =>
            config('payments.gateway') === 'demo' ? new DemoGateway : new PayMongoGateway
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Laravel's existing HTTP client so Render does not need SMTP or an extra SDK.
        Mail::extend('resend', fn (array $config = []) => new ResendApiTransport(
            (string) config('services.resend.key', ''),
        ));
    }
}
