<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['court_id', 'starts_at', 'ends_at'], 'court_blocks_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_blocks');
    }
};
