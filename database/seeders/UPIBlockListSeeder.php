<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UPIBlockList;
use Illuminate\Database\Seeder;

final class UPIBlockListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonBlockedTerms = [
            '@jio' => 'Known spam account',
            'scam' => 'Reported for scam activities',
            'fraud' => 'Fraudulent activities detected',
            '@paytm' => 'System abuse detected',
            'test' => 'Test account',
        ];

        foreach ($commonBlockedTerms as $term => $reason) {
            UPIBlockList::factory()->create([
                'string' => $term,
                'block_reason' => $reason,
            ]);
        }

        // Add some random blocked terms
        UPIBlockList::factory(5)->create();
    }
}
