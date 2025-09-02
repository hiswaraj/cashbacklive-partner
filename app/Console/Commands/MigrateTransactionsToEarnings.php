<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EarningType;
use App\Enums\PayoutStatus;
use App\Models\Conversion;
use App\Models\Earning;
use App\Models\Payout;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class MigrateTransactionsToEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-transactions-to-earnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-off script to migrate data from the old transactions table to the new earnings and payouts tables.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! Schema::hasTable('transactions')) {
            $this->warn('The legacy `transactions` table does not exist. No migration needed.');

            return self::SUCCESS;
        }

        if (Earning::count() > 0 || Payout::count() > 0) {
            $this->error('The `earnings` or `payouts` tables already contain data. Aborting migration to prevent duplication.');

            return self::FAILURE;
        }

        if (! $this->confirm('This will migrate all historical transaction data to the new earnings/payouts structure. Are you sure you want to proceed?')) {
            $this->info('Migration cancelled by user.');

            return self::SUCCESS;
        }

        $this->info('Starting migration from transactions to earnings/payouts...');

        try {
            DB::transaction(function () {
                $legacyTransactions = LegacyTransaction::query()->get();
                $progressBar = $this->output->createProgressBar($legacyTransactions->count());
                $progressBar->start();

                foreach ($legacyTransactions as $transaction) {
                    // 1. Create the Payout record from the old transaction.
                    // This preserves the historical payment attempt.
                    $payout = Payout::create([
                        'upi' => $transaction->upi,
                        'total_amount' => $transaction->amount,
                        'payment_gateway' => $transaction->payment_gateway,
                        'payment_id' => $transaction->payment_id,
                        'reference_id' => $transaction->reference_id,
                        'status' => $this->mapPayoutStatus($transaction->payment_status),
                        'comment' => $transaction->comment,
                        'api_response' => $transaction->api_response,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);

                    // 2. Create the corresponding Earning record.
                    // This creates the auditable financial ledger entry.
                    Earning::create([
                        'conversion_id' => $transaction->conversion_id,
                        'payout_id' => $payout->id, // Link the earning to its historical payout
                        'type' => EarningType::from($transaction->type), // Directly cast from old enum value
                        'amount' => $transaction->amount,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2); // Add some space after the progress bar
            });
        } catch (Throwable $e) {
            $this->error('Migration failed! The transaction has been rolled back.');
            $this->error('Error: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->info('✅ Migration completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Maps the old transaction status string to the new PayoutStatus enum.
     */
    private function mapPayoutStatus(string $legacyStatus): PayoutStatus
    {
        return match ($legacyStatus) {
            'success' => PayoutStatus::SUCCESS,
            'failed' => PayoutStatus::FAILED,
            default => PayoutStatus::PENDING,
        };
    }
}

// This temporary, local model allows us to query the old 'transactions' table
// without relying on the app/Models/Transaction.php file, which will be deleted.
final class LegacyTransaction extends Model
{
    protected $table = 'transactions';

    // Since we are only reading, no need for casts, fillable, etc.
    // The relationship is defined here for eager loading.
    public function conversion()
    {
        // We can't use the FQCN of the old Conversion model if it's changing
        // but since it's just the relationship name that changes, this is fine.
        return $this->belongsTo(Conversion::class);
    }
}
