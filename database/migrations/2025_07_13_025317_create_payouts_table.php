<?php

declare(strict_types=1);

use App\Enums\PayoutStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('upi')->index();
            $table->unsignedInteger('total_amount');
            $table->string('payment_gateway');
            $table->string('payment_id')->nullable();
            $table->string('reference_id')->unique();
            $table->enum('status', PayoutStatus::values())->index();
            $table->string('comment')->nullable();
            $table->text('api_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
