<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PayoutStatus;
use App\Events\PaymentStatusUpdated;
use App\Models\Payout;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\PaymentGatewayResolver;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

final class FetchUpdatePayments extends Command
{
    protected $signature = 'payouts:fetch-update';

    protected $description = 'Check pending payments and update their status';

    public function handle(PaymentGatewayResolver $gatewayResolver): int
    {
        $this->info('Starting to check pending payments...');

        $pendingPayouts = Payout::query()
            ->where('status', 'pending')
            ->where(function (Builder $query): void {
                $query->whereNull('updated_at')
                    ->orWhere('created_at', '>', Carbon::now()->subMinutes(5))
                    ->orWhere('updated_at', '<', Carbon::now()->subMinutes(5));
            })
            ->get();

        $this->info("Found {$pendingPayouts->count()} pending payouts to check.");

        foreach ($pendingPayouts as $payout) {
            try {
                $gateway = $gatewayResolver->resolveByName($payout->payment_gateway);

                if (! $gateway instanceof PaymentGateway) {
                    $this->error("Could not resolve gateway: {$payout->payment_gateway} for payout: {$payout->id}");

                    continue;
                }

                if (empty($payout->payment_id)) {
                    $this->warn("Skipping status check for payout {$payout->id}: payment_id is missing.");

                    continue;
                }

                $result = $gateway->fetchPaymentStatus($payout->payment_id, $payout->reference_id);

                $payout->status = PayoutStatus::from($result['status']);
                $payout->api_response = $result['api_response'];
                $payout->save();

                PaymentStatusUpdated::dispatch($payout);

                $this->info("Updated payout {$payout->id} status to: {$payout->status->value}");
            } catch (Exception $e) {
                $this->error("Error updating payout {$payout->id}: {$e->getMessage()}");
                Log::error('Failed to check payout status', [
                    'payout_id' => $payout->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Finished checking pending payouts.');

        return CommandAlias::SUCCESS;
    }
}
