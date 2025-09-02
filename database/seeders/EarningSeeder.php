<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Models\Conversion;
use App\Models\Earning;
use App\Models\Payout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EarningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get all valid conversions and create their corresponding earnings.
        // These earnings will start as "unpaid" (payout_id is null).
        Conversion::where('is_valid', true)
            ->with('event', 'click.refer')
            ->each(function (Conversion $conversion): void {
                $amounts = $conversion->calculated_amounts;

                if ($amounts['user'] > 0) {
                    Earning::factory()->create([
                        'conversion_id' => $conversion->id,
                        'type' => EarningType::USER,
                        'amount' => $amounts['user'],
                    ]);
                }

                if ($conversion->click->refer_id && $amounts['refer'] > 0) {
                    Earning::factory()->create([
                        'conversion_id' => $conversion->id,
                        'type' => EarningType::REFER,
                        'amount' => $amounts['refer'],
                    ]);
                }
            });

        // 2. Simulate batching and paying out some of the unpaid earnings.
        // We'll process about 70% of the unpaid earnings.
        $unpaidEarnings = Earning::whereNull('payout_id')->with('conversion.click', 'conversion.click.refer')->get();
        $earningsToProcess = $unpaidEarnings->random((int) ($unpaidEarnings->count() * 0.7));

        // Group by the UPI associated with the earning's conversion click/refer.
        $groupedByUpi = $earningsToProcess->groupBy(function (Earning $earning) {
            if ($earning->type === EarningType::USER) {
                return $earning->conversion->click->upi;
            }

            return $earning->conversion->click->refer->upi;
        });

        foreach ($groupedByUpi as $upi => $earnings) {
            $this->createPayoutForEarnings($upi, $earnings);
        }
    }

    /**
     * Creates a Payout for a collection of earnings for a specific UPI.
     */
    private function createPayoutForEarnings(string $upi, Collection $earnings): void
    {
        DB::transaction(function () use ($upi, $earnings) {
            $totalAmount = $earnings->sum('amount');
            $payoutStatus = fake()->randomElement(PayoutStatus::cases());

            // Create a Payout with a random final status.
            $payout = Payout::factory()->create([
                'upi' => $upi,
                'total_amount' => $totalAmount,
                'status' => $payoutStatus,
                'comment' => 'Seeder-generated batch payout.',
            ]);

            // Link all the earnings in this batch to the newly created payout.
            Earning::whereIn('id', $earnings->pluck('id'))->update([
                'payout_id' => $payout->id,
            ]);
        });
    }
}
