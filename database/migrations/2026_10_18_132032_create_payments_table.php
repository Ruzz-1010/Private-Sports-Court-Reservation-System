<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->string('provider')->nullable();
            $table->string('provider_checkout_session_id')->nullable()->index();
            $table->string('provider_payment_id')->nullable()->index();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('status')->default(PaymentStatus::Pending->value)->index();

            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status'], 'payments_reservation_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
