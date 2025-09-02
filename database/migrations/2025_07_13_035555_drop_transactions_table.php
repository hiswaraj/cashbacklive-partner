<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('transactions');
    }

    /**
     * Reverse the migrations.
     *
     * Note: Reversing this migration is destructive and will not restore
     * the original table structure or data. The forward migration is a
     * one-way operation after data has been moved.
     */
    public function down(): void
    {
        // We leave this empty intentionally. There is no going back from this
        // point without restoring a database backup. The new structure of
        // earnings/payouts is the source of truth.
    }
};
