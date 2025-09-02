<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `events` table
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->string('param');
            $table->string('label');
            $table->unsignedInteger('user_amount')->default(0);
            $table->string('user_payment_comment')->nullable();
            $table->boolean('is_instant_pay_user')->default(false);
            $table->unsignedInteger('refer_amount')->default(0);
            $table->string('referrer_payment_comment')->nullable();
            $table->boolean('is_instant_pay_refer')->default(false);
            $table->boolean('is_commission_split_allowed')->default(false);
            $table->unsignedInteger('min_refer_commission')->default(0);
            $table->unsignedInteger('max_refer_commission')->default(0);
            $table->unsignedSmallInteger('time_gap_in_seconds')->default(0);
            $table->unsignedInteger('sort_order');
        });
    }

    /**
     * Drop `events` table
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
