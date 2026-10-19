<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtBlockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_block_and_remove_an_available_court_interval(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $court = Court::factory()->create();
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $this->actingAs($admin)->post(route('admin.court-blocks.store'), [
            'court_id' => $court->id,
            'starts_at' => $start->format('Y-m-d\TH:i'),
            'ends_at' => $start->addHour()->format('Y-m-d\TH:i'),
            'reason' => 'Court maintenance',
        ])->assertRedirect();

        $block = CourtBlock::firstOrFail();
        $this->assertSame('Court maintenance', $block->reason);
        $this->actingAs($admin)->delete(route('admin.court-blocks.destroy', $block))->assertRedirect();
        $this->assertDatabaseMissing('court_blocks', ['id' => $block->id]);
    }

    public function test_a_customer_cannot_access_schedule_block_management(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $this->actingAs($customer)->get(route('admin.court-blocks.index'))->assertForbidden();
    }
}
