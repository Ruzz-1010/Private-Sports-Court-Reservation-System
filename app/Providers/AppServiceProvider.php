<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payments\MayaGateway;
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
        $this->app->bind(PaymentGateway::class, fn (): PaymentGateway => new MayaGateway);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
