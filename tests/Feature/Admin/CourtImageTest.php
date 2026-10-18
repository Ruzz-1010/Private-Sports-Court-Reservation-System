<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourtImageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tennis Court 1',
            'location' => 'Main Facility',
            'hourly_rate' => '750.00',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_an_image_can_be_uploaded_when_creating_a_court(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload([
                'image' => UploadedFile::fake()->image('court.jpg', 800, 600),
            ]));

        $court = Court::where('name', 'Tennis Court 1')->firstOrFail();

        $this->assertNotNull($court->image_path);
        Storage::disk('public')->assertExists($court->image_path);
    }

    public function test_a_court_can_be_created_without_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload());

        $court = Court::where('name', 'Tennis Court 1')->firstOrFail();

        $this->assertNull($court->image_path);
        $this->assertNull($court->imageUrl());
    }

    public function test_uploading_an_image_replaces_the_previous_file(): void
    {
        Storage::fake('public');

        $court = Court::factory()->create();
        $original = $court->image_path;

        $this->actingAs($this->admin())
            ->put(route('admin.courts.update', $court), $this->payload([
                'image' => UploadedFile::fake()->image('new.png', 800, 600),
            ]));

        $court->refresh();

        $this->assertNotSame($original, $court->image_path);
        Storage::disk('public')->assertExists($court->image_path);
    }

    public function test_non_image_files_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload([
                'image' => UploadedFile::fake()->create('malware.php', 10),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Court::count());
    }

    public function test_oversized_images_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload([
                'image' => UploadedFile::fake()->image('huge.jpg')->size(5000),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Court::count());
    }

    public function test_images_smaller_than_the_minimum_size_are_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.courts.store'), $this->payload([
                'image' => UploadedFile::fake()->image('tiny.png', 100, 100),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertSame(0, Court::count());
    }

    public function test_image_url_is_null_without_a_path(): void
    {
        $court = Court::factory()->create(['image_path' => null]);

        $this->assertNull($court->imageUrl());
    }

    public function test_image_url_points_at_the_public_storage_path(): void
    {
        $court = Court::factory()->create(['image_path' => 'courts/test.jpg']);

        $this->assertStringContainsString('storage/courts/test.jpg', $court->imageUrl());
    }

    public function test_customers_cannot_upload_a_court_image(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create(['role' => Role::Customer]);

        $this->actingAs($customer)
            ->post(route('admin.courts.store'), $this->payload([
                'image' => UploadedFile::fake()->image('court.jpg'),
            ]))
            ->assertForbidden();

        $this->assertSame(0, Court::count());
    }
}
