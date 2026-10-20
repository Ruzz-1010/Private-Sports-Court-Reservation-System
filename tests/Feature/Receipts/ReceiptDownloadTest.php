<?php

namespace Tests\Feature\Receipts;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationConfirmed;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptDownloadTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{Reservation, Payment, Receipt} */
    private function paidReservation(string $method = 'on_site'): array
    {
        $court = Court::factory()->create(['name' => 'Basketball Court A', 'location' => 'Main Building']);
        $customer = User::factory()->create(['role' => Role::Customer, 'name' => 'Maria Santos']);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-RECEIPT-1',
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'hourly_rate' => 500,
            'total_amount' => 1000,
            'status' => ReservationStatus::Pending,
            'payment_method' => $method,
            'payment_deadline' => now()->addHours(24),
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'method' => $method,
            'provider' => 'paymongo',
            'amount' => 1000,
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        // Go through the real confirmation path so the receipt is the same one
        // the webhook and on-site verification would produce.
        $receipt = app(ConfirmPayment::class)->handle($payment)['receipt'];

        return [$reservation->refresh(), $payment->refresh(), $receipt];
    }

    public function test_a_customer_can_download_their_own_receipt_as_a_pdf(): void
    {
        [, , $receipt] = $this->paidReservation();

        $response = $this->actingAs($receipt->reservation->user)
            ->get(route('customer.receipts.show', $receipt));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('receipt-'.ltrim($receipt->receipt_number, '-').'.pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_pdf_contains_every_required_receipt_detail(): void
    {
        [, , $receipt] = $this->paidReservation();

        $html = view('receipts.pdf', [
            'receipt' => $receipt,
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ])->render();

        // Every field the instructor requires on a receipt must be present.
        foreach ([
            $receipt->receipt_number,
            $receipt->reservation_reference,
            $receipt->customer_name,
            $receipt->customer_email,
            $receipt->court_name,
            $receipt->court_location,
            'PHP 1,000.00',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->assertStringContainsString('On-site', $html);
        $this->assertStringContainsString('PAID', $html);
    }

    public function test_an_online_payment_receipt_is_labelled_as_online(): void
    {
        [, , $receipt] = $this->paidReservation('online');

        $this->assertSame('online', $receipt->payment_method);

        $html = view('receipts.pdf', [
            'receipt' => $receipt,
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ])->render();

        $this->assertStringContainsString('Online', $html);
    }

    public function test_a_customer_cannot_download_another_customers_receipt(): void
    {
        [, , $receipt] = $this->paidReservation();
        $intruder = User::factory()->create(['role' => Role::Customer]);

        $this->actingAs($intruder)
            ->get(route('customer.receipts.show', $receipt))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_a_receipt(): void
    {
        [, , $receipt] = $this->paidReservation();

        $this->get(route('customer.receipts.show', $receipt))->assertRedirect(route('login'));
    }

    public function test_an_admin_may_download_any_receipt_for_reconciliation(): void
    {
        [, , $receipt] = $this->paidReservation();
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->get(route('customer.receipts.show', $receipt))
            ->assertOk();
    }

    public function test_the_receipt_stays_downloadable_from_reservation_history(): void
    {
        [$reservation, , $receipt] = $this->paidReservation();

        $this->actingAs($reservation->user)
            ->get(route('customer.reservations.index'))
            ->assertOk()
            ->assertSee('PDF')
            ->assertSee(route('customer.receipts.show', $receipt), false);
    }

    public function test_the_detail_page_offers_the_download_when_a_receipt_exists(): void
    {
        [$reservation, , $receipt] = $this->paidReservation();

        $this->actingAs($reservation->user)
            ->get(route('customer.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Download receipt (PDF)')
            ->assertSee($receipt->receipt_number);
    }

    public function test_the_confirmation_email_links_directly_to_the_pdf(): void
    {
        [$reservation, , $receipt] = $this->paidReservation();

        $mail = (new ReservationConfirmed($reservation))->toMail($reservation->user);

        // MailMessage exposes a single action button, not a list.
        $this->assertSame('View receipt online', $mail->actionText);
        $this->assertSame(route('customer.receipts.show', $receipt), $mail->actionUrl);
    }

    public function test_the_confirmation_email_attaches_the_receipt_pdf(): void
    {
        [$reservation, , $receipt] = $this->paidReservation();

        $mail = (new ReservationConfirmed($reservation))->toMail($reservation->user);

        // attachData() writes to rawAttachments, so the receipt must travel
        // with the email rather than depending on a link that needs a login
        // and a stable deployment URL.
        $this->assertCount(1, $mail->rawAttachments);

        $attachment = $mail->rawAttachments[0];

        $this->assertSame('receipt-'.ltrim($receipt->receipt_number, '-').'.pdf', $attachment['name']);
        $this->assertSame('application/pdf', $attachment['options']['mime'] ?? null);
        $this->assertStringStartsWith('%PDF', $attachment['data']);
    }

    public function test_a_pending_reservation_attaches_no_receipt(): void
    {
        $court = Court::factory()->create();
        $customer = User::factory()->create(['role' => Role::Customer]);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::factory()->create([
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHour(),
            'status' => ReservationStatus::Pending,
        ]);

        $mail = (new ReservationConfirmed($reservation))->toMail($customer);

        $this->assertCount(0, $mail->rawAttachments);
    }

    public function test_a_pending_reservation_shows_no_receipt_offer(): void
    {
        $court = Court::factory()->create();
        $customer = User::factory()->create(['role' => Role::Customer]);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-NO-RECEIPT',
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'hourly_rate' => 500,
            'total_amount' => 1000,
            'status' => ReservationStatus::Pending,
            'payment_method' => PaymentMethod::Online,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.reservations.show', $reservation))
            ->assertOk()
            ->assertDontSee('Download receipt (PDF)');
    }
}
