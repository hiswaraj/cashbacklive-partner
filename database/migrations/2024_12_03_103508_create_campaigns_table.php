<?php

declare(strict_types=1);

use App\Enums\AccessPolicy;
use App\Enums\ExtraInputType;
use App\Enums\ReferralPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `campaigns` table
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->string('id', 15)->primary();
            // Basic info
            $table->string('name');
            $table->string('subtitle');
            $table->string('logo_path')->nullable();
            $table->text('description');
            $table->text('terms');
            $table->string('url');

            // Settings & Configuration
            $table->boolean('is_active')->default(true);
            $table->enum('access_policy', array_column(AccessPolicy::cases(), 'value'))->default(AccessPolicy::PRIVATE->value);
            $table->enum('referral_policy', array_column(ReferralPolicy::cases(), 'value'))->default(ReferralPolicy::DISABLED->value);
            $table->boolean('is_footer_telegram_enabled')->default(true);
            $table->boolean('is_referer_telegram_allowed')->default(false);
            $table->boolean('is_telegram_enabled_on_404')->default(false);
            $table->boolean('is_auto_redirect_to_telegram_on_404')->default(true);
            $table->boolean('is_direct_redirect')->default(false);

            // Limits & Security
            $table->string('webhook_secret');
            $table->unsignedInteger('max_upi_attempts')->default(1);
            $table->unsignedInteger('max_ip_attempts')->default(1);

            // Extra Input Settings
            $table->boolean('is_extra_input_1_active')->default(false);
            $table->boolean('is_extra_input_1_required')->default(false);
            $table->enum('extra_input_1_type', ExtraInputType::values())->default(ExtraInputType::MOBILE->value);
            $table->string('extra_input_1_label')->nullable();

            $table->boolean('is_extra_input_2_active')->default(false);
            $table->boolean('is_extra_input_2_required')->default(false);
            $table->enum('extra_input_2_type', ExtraInputType::values())->default(ExtraInputType::EMAIL->value);
            $table->string('extra_input_2_label')->nullable();

            $table->boolean('is_extra_input_3_active')->default(false);
            $table->boolean('is_extra_input_3_required')->default(false);
            $table->enum('extra_input_3_type', ExtraInputType::values())->default(ExtraInputType::NUMBER->value);
            $table->string('extra_input_3_label')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Drop `campaigns` table
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
