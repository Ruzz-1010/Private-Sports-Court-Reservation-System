<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_new_users_are_always_created_as_customers(): void
    {
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'juan@example.com')->firstOrFail();

        $this->assertSame(Role::Customer, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_requires_a_matching_password_confirmation(): void
    {
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create(['role' => Role::Customer]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_away_from_protected_routes(): void
    {
        $this->get('/customer/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_away_from_guest_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Customer]))
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_customers_cannot_reach_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Customer]))
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admins_cannot_reach_the_customer_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/customer/dashboard')
            ->assertForbidden();
    }

    public function test_admins_can_reach_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_dashboard_routes_each_role_to_its_own_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Customer]))
            ->get('/dashboard')
            ->assertRedirect(route('customer.dashboard'));

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
