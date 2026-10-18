<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            // Immutable snapshot taken at issue time. Court renames, rate changes,
            // and later reservation edits must never rewrite a receipt.
            $table->string('reservation_reference');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('court_name');
            $table->string('court_location');
            $table->date('reservation_date');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->unsignedInteger('duration_minutes');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->timestampTz('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
