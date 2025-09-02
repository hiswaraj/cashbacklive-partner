<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create `upi_blocklist` table
     */
    public function up(): void
    {
        Schema::create('upi_blocklist', function (Blueprint $table): void {
            $table->id();
            $table->string('string')->unique();
            $table->string('block_reason')->nullable();
        });
    }

    /**
     * Drop `upi_blocklist` table
     */
    public function down(): void
    {
        Schema::dropIfExists('upi_blocklist');
    }
};
