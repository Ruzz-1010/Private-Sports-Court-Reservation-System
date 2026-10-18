<?php

use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentVerificationController as AdminPaymentVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\AvailabilityController as CustomerAvailabilityController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Customer\PaymentSimulatorController as CustomerPaymentSimulatorController;
use App\Http\Controllers\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\Webhooks\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Payment provider callback
|--------------------------------------------------------------------------
|
| Public and unauthenticated because the provider calls it server to server.
| It is rate limited and every payload must carry a valid signature, so an
| unauthenticated request still cannot change any state.
|
*/

Route::post(config('payments.webhook.path'), PayMongoWebhookController::class)
    ->middleware('throttle:'.config('payments.webhook.rate_limit'))
    ->name('webhooks.paymongo');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('customer.dashboard');
    })->name('dashboard');

    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function (): void {
        Route::get('dashboard', CustomerDashboardController::class)->name('dashboard');

        Route::get('courts/{court}/availability', [CustomerAvailabilityController::class, 'index'])->name('availability.index');
        Route::get('courts/{court}/slots', [CustomerAvailabilityController::class, 'slots'])->name('availability.slots');

        Route::get('reservations', [CustomerReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/create/{court}', [CustomerReservationController::class, 'create'])->name('reservations.create');
        Route::post('reservations', [CustomerReservationController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [CustomerReservationController::class, 'show'])->name('reservations.show');
        Route::delete('reservations/{reservation}', [CustomerReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::post('reservations/{reservation}/checkout', [CustomerPaymentController::class, 'checkout'])->name('payments.checkout');

        // Local stand-in for the provider's hosted checkout page.
        Route::get('payments/simulate/{reference}', [CustomerPaymentSimulatorController::class, 'show'])->name('payments.simulator');
        Route::post('payments/simulate/{reference}/send', [CustomerPaymentSimulatorController::class, 'send'])->name('payments.simulator.send');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('courts', AdminCourtController::class);
        Route::post('courts/{court}/restore', [AdminCourtController::class, 'restore'])->name('courts.restore');

        Route::get('payments', [AdminPaymentVerificationController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/verify', [AdminPaymentVerificationController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [AdminPaymentVerificationController::class, 'reject'])->name('payments.reject');
    });
});
