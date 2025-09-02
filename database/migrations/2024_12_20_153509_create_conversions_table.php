<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `conversions` table
     */
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table): void {
            $table->id();
            $table->string('click_id');
            $table->foreign('click_id')->references('id')->on('clicks')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->boolean('is_valid')->default(true);
            $table->string('reason')->nullable();
            $table->string('ip_address', 45);
            $table->unique(['click_id', 'event_id']);
            $table->timestamps();
        });
    }

    /**
     * Drop `conversions` table
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
