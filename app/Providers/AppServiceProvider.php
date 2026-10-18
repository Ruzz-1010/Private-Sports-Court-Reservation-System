<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind the payment gateway chosen by configuration.
     *
     * The rest of the application depends on the interface, so switching
     * between the live provider and the local simulator is a config change.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, fn (): PaymentGateway => match (config('payments.gateway')) {
            'paymongo' => new PayMongoGateway,
            default => new FakeGateway,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
