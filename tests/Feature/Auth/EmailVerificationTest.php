<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Services\Auth\EmailVerificationCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The code gate must let a real customer through and keep everyone else out.
 *
 * These tests also guard against the worst failure mode: a gate so strict that
 * a correctly registered customer can never get in.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'juan@example.com'): User
    {
        $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => $email,
            'password' => 'pickleball2026',
            'password_confirmation' => 'pickleball2026',
            'terms' => '1',
        ]);

        return User::where('email', $email)->firstOrFail();
    }

    public function test_registration_creates_an_unverified_account_and_sends_a_code(): void
    {
        Notification::fake();

        $user = $this->register();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, EmailVerificationCode::class);
    }

    public function test_registration_lands_on_the_code_screen(): void
    {
        Notification::fake();

        $this->post('/register', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'pickleball2026',
            'password_confirmation' => 'pickleball2026',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));
    }

    public function test_an_unverified_customer_cannot_open_the_customer_area(): void
    {
        $user = User::factory()->unverified()->create(['role' => Role::Customer]);

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_the_correct_code_verifies_the_account_and_opens_the_dashboard(): void
    {
        Notification::fake();

        $user = $this->register();
        $code = app(EmailVerificationCodes::class)->issue($user);

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user->fresh())
            ->get('/customer/dashboard')
            ->assertOk();
    }

    public function test_a_wrong_code_does_not_verify_the_account(): void
    {
        $user = $this->register();
        $code = app(EmailVerificationCodes::class)->issue($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->actingAs($user)
            ->post('/email/verify', ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_code_is_burned_after_too_many_wrong_tries(): void
    {
        config(['reservation.email_verification.max_attempts' => 2]);

        $user = $this->register();
        $code = app(EmailVerificationCodes::class)->issue($user);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->actingAs($user)->post('/email/verify', ['code' => $wrong]);
        $this->actingAs($user)->post('/email/verify', ['code' => $wrong]);

        // Even the right code is refused once the limit is reached.
        $this->actingAs($user)
            ->post('/email/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_new_code_can_be_requested_after_the_wait(): void
    {
        Notification::fake();

        $user = $this->register();

        // The first code blocks an immediate resend.
        $this->actingAs($user)
            ->post('/email/verification-code')
            ->assertSessionHasErrors('code');

        $this->travel((int) config('reservation.email_verification.resend_seconds') + 1)->seconds();

        $this->actingAs($user)
            ->post('/email/verification-code')
            ->assertSessionHas('status');

        Notification::assertSentTo($user, EmailVerificationCode::class, 2);
    }

    public function test_an_unverified_customer_is_taken_to_the_code_screen_after_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => Role::Customer,
            'password' => 'pickleball2026',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'pickleball2026',
        ])->assertRedirect(route('verification.notice'));
    }

    public function test_a_verified_administrator_is_never_asked_for_a_code(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }
}
