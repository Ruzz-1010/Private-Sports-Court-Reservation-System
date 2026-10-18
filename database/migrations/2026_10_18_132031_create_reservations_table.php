<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();

            // Half-open interval [starts_at, ends_at).
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');

            // Locked at booking time so later rate changes never rewrite history.
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->string('status')->default(ReservationStatus::Pending->value)->index();
            $table->string('payment_method');
            $table->timestampTz('payment_deadline')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->text('expiration_reason')->nullable();
            $table->timestamps();

            $table->index(['court_id', 'starts_at', 'ends_at'], 'reservations_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
