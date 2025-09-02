<?php

declare(strict_types=1);

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
        Schema::table('clicks', function (Blueprint $table) {
            // Optimizes the `whereBetween('created_at', ...)` queries
            $table->index('created_at');
            // Optimizes the dashboard widget when filtering by campaign and date
            $table->index(['campaign_id', 'created_at']);
        });

        Schema::table('conversions', function (Blueprint $table) {
            // This composite index is optimal for queries filtering by date.
            $table->index('created_at');
        });

        Schema::table('earnings', function (Blueprint $table) {
            // This composite index is optimal for queries filtering by date.
            $table->index('created_at');
            // Optimizes filtering by User vs Referrer earnings
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['campaign_id', 'created_at']);
        });

        Schema::table('conversions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('earnings', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type']);
        });
    }
};
