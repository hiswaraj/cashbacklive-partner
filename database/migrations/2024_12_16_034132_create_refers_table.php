<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `refers` table
     */
    public function up(): void
    {
        Schema::create('refers', function (Blueprint $table): void {
            $table->string('id', 8)->primary();
            $table->string('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->string('upi')->index();
            $table->string('mobile');
            $table->string('telegram_url')->nullable();
            $table->json('commission_split_settings')->nullable();
            $table->timestamps();

            // Add unique constraint for campaign_id and upi combination
            $table->unique(['campaign_id', 'upi']);
        });
    }

    /**
     * Drop `refers` table
     */
    public function down(): void
    {
        Schema::dropIfExists('refers');
    }
};
