<?php

use App\Http\Controllers\Admin\CourtController as AdminCourtController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentVerificationController as AdminPaymentVerificationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\AvailabilityController as CustomerAvailabilityController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Customer\ReceiptController as CustomerReceiptController;
use App\Http\Controllers\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\Webhooks\MayaWebhookController;
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
| It is rate limited. Maya's payment state is then retrieved using the secret
| API key before an unauthenticated callback can change any local state.
|
*/

Route::post(config('payments.webhook.path'), MayaWebhookController::class)
    ->middleware('throttle:'.config('payments.webhook.rate_limit'))
    ->name('webhooks.maya');

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
    });

    // Reachable by customers for their own receipts and by administrators for
    // reconciliation. It sits outside the role groups because ReceiptPolicy, not
    // the route, decides ownership: one customer still cannot open another
    // customer's receipt.
    Route::get('receipts/{receipt}', [CustomerReceiptController::class, 'show'])->name('customer.receipts.show');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('courts', AdminCourtController::class);
        Route::post('courts/{court}/restore', [AdminCourtController::class, 'restore'])->name('courts.restore');

        Route::resource('reservations', AdminReservationController::class)->only(['index', 'show']);
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');

        Route::get('payments', [AdminPaymentVerificationController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/verify', [AdminPaymentVerificationController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [AdminPaymentVerificationController::class, 'reject'])->name('payments.reject');
    });
});
