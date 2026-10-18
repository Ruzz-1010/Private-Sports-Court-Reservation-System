<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => Role::Customer]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tennis Court 1',
            'location' => 'Main Facility',
            'description' => 'Hard court',
            'hourly_rate' => '750.00',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_list_page_shows_existing_courts(): void
    {
        Court::factory()->create(['name' => 'Badminton Court 1']);
        Court::factory()->create(['name' => 'Volleyball Court']);

        $this->actingAs($this->admin())
            ->get(route('admin.courts.index'))
            ->assertOk()
            ->assertSee('Badminton Court 1')
            ->assertSee('Volleyball Court');
    }

    public function test_detail_page_shows_a_single_court(): void
    {
        $court = Court::factory()->create([
            'name' => 'Basketball Court A',
            'description' => 'Indoor court',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.courts.show', $court))
            ->assertOk()
            ->assertSee('Basketball Court A')
            ->assertSee('Indoor court');
    }

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.courts.create'))
            ->assertOk()
            ->assertSee('Court name');
    }

    public function test_admin_can_create_a_court(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload());

        $response->assertRedirect();

        $this->assertDatabaseHas('courts', [
            'name' => 'Tennis Court 1',
            'location' => 'Main Facility',
            'is_active' => true,
        ]);

        $court = Court::where('name', 'Tennis Court 1')->firstOrFail();
        $this->assertSame('750.00', $court->hourly_rate);
    }

    public function test_creating_a_court_is_audited(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.courts.store'), $this->payload());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'court.created',
        ]);
    }

    public function test_creating_a_court_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('courts', 0);
    }

    public function test_creating_a_court_requires_a_numeric_rate(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload(['hourly_rate' => 'free']))
            ->assertSessionHasErrors('hourly_rate');
    }

    public function test_court_names_must_be_unique(): void
    {
        Court::factory()->create(['name' => 'Tennis Court 1']);

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload())
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Court::where('name', 'Tennis Court 1')->count());
    }

    public function test_edit_form_is_prepopulated(): void
    {
        $court = Court::factory()->create([
            'name' => 'Basketball Court B',
            'location' => 'Annex',
            'hourly_rate' => '450.00',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.courts.edit', $court))
            ->assertOk()
            ->assertSee('Basketball Court B')
            ->assertSee('Annex')
            ->assertSee('450.00');
    }

    public function test_admin_can_update_a_court(): void
    {
        $court = Court::factory()->create(['hourly_rate' => '500.00']);

        $this->actingAs($this->admin())
            ->put(route('admin.courts.update', $court), $this->payload([
                'name' => 'Renamed Court',
                'hourly_rate' => '600.00',
            ]))
            ->assertRedirect(route('admin.courts.show', $court));

        $court->refresh();

        $this->assertSame('Renamed Court', $court->name);
        $this->assertSame('600.00', $court->hourly_rate);
    }

    public function test_updating_ignores_the_courts_own_name_for_uniqueness(): void
    {
        $court = Court::factory()->create(['name' => 'Tennis Court 1']);

        $this->actingAs($this->admin())
            ->put(route('admin.courts.update', $court), $this->payload(['name' => 'Tennis Court 1']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Tennis Court 1', $court->fresh()->name);
    }

    public function test_admin_can_delete_an_unused_court_permanently(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.courts.destroy', $court))
            ->assertRedirect(route('admin.courts.index'));

        $this->assertDatabaseMissing('courts', ['id' => $court->id]);
    }

    public function test_a_court_with_reservations_is_archived_not_deleted(): void
    {
        $court = Court::factory()->create(['is_active' => true]);
        $user = $this->customer();

        Reservation::factory()->create([
            'court_id' => $court->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.courts.destroy', $court))
            ->assertRedirect(route('admin.courts.index'));

        $court->refresh();

        $this->assertTrue($court->trashed(), 'Court should be soft-deleted, not removed.');
        $this->assertFalse($court->is_active, 'Archived court must not stay active.');

        $this->assertSame(
            1,
            Reservation::where('court_id', $court->id)->count(),
            'Reservation history must be preserved.',
        );
    }

    public function test_archiving_a_court_is_audited(): void
    {
        $court = Court::factory()->create();
        Reservation::factory()->create([
            'court_id' => $court->id,
            'user_id' => $this->customer()->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.courts.destroy', $court));

        $this->assertDatabaseHas('audit_logs', ['action' => 'court.archived']);
    }

    public function test_admin_can_restore_an_archived_court(): void
    {
        $court = Court::factory()->create(['is_active' => true]);
        $court->update(['is_active' => false]);
        $court->delete();

        $this->assertNull(Court::find($court->id));

        $this->actingAs($this->admin())
            ->post(route('admin.courts.restore', $court->id));

        $this->assertNotNull(Court::find($court->id), 'Restored court should be visible again.');
    }

    public function test_customers_cannot_reach_court_management(): void
    {
        $court = Court::factory()->create();

        $this->actingAs($this->customer())->get(route('admin.courts.index'))->assertForbidden();
        $this->actingAs($this->customer())->get(route('admin.courts.create'))->assertForbidden();
        $this->actingAs($this->customer())->get(route('admin.courts.show', $court))->assertForbidden();
        $this->actingAs($this->customer())->get(route('admin.courts.edit', $court))->assertForbidden();
        $this->actingAs($this->customer())->post(route('admin.courts.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->customer())->delete(route('admin.courts.destroy', $court))->assertForbidden();

        $this->assertDatabaseCount('courts', 1);
    }

    public function test_guests_cannot_reach_court_management(): void
    {
        $this->get(route('admin.courts.index'))->assertRedirect(route('login'));
    }

    public function test_audit_details_never_contain_secrets(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.courts.store'), $this->payload());

        $log = AuditLog::where('action', 'court.created')->firstOrFail();

        $encoded = json_encode($log->details);

        $this->assertStringNotContainsStringIgnoringCase('password', $encoded);
        $this->assertStringNotContainsStringIgnoringCase('secret', $encoded);
        $this->assertStringNotContainsStringIgnoringCase('card', $encoded);
    }
}
