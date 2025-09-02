<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `clicks` table
     */
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table): void {
            $table->string('id', 15)->primary();
            $table->string('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->string('refer_id')->nullable();
            $table->foreign('refer_id')->references('id')->on('refers')->cascadeOnDelete();
            $table->string('upi')->index();
            $table->string('ip_address', 45);
            $table->string('extra_input_1')->nullable();
            $table->string('extra_input_2')->nullable();
            $table->string('extra_input_3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop `clicks` table
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
