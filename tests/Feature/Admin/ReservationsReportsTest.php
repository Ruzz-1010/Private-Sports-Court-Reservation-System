<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationsReportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function reservation(ReservationStatus $status = ReservationStatus::Confirmed): Reservation
    {
        // Courts.name is unique, so each call needs its own court. The factory
        // generates a unique name, which the assertions read back off the
        // reservation instead of hardcoding one.
        $court = Court::factory()->create();
        $customer = User::factory()->create(['role' => Role::Customer]);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->setTime(10, 0);

        return Reservation::factory()->create([
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'status' => $status,
        ]);
    }

    public function test_admin_can_filter_and_open_reservations(): void
    {
        $reservation = $this->reservation();

        $this->actingAs($this->admin())
            ->get(route('admin.reservations.index', ['status' => 'confirmed']))
            ->assertOk()
            ->assertSee($reservation->reference_number);

        $this->actingAs($this->admin())
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee($reservation->court->name)
            ->assertSee($reservation->user->email);
    }

    public function test_reports_count_only_verified_payments_as_revenue(): void
    {
        $paid = $this->reservation();
        $unpaid = $this->reservation(ReservationStatus::Pending);

        Payment::factory()->create([
            'reservation_id' => $paid->id,
            'method' => PaymentMethod::Online,
            'amount' => 1250,
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
        Payment::factory()->create([
            'reservation_id' => $unpaid->id,
            'method' => PaymentMethod::Online,
            'amount' => 9999,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()]))
            ->assertOk()
            ->assertSee('₱1,250.00')
            ->assertDontSee('₱9,999.00');
    }

    public function test_customer_cannot_access_reservations_or_reports(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);

        $this->actingAs($customer)->get(route('admin.reservations.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
    }
}
