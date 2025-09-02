<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EarningType;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Earning;
use App\Models\Payout;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\UnableToProcessCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

final class ClickInspector extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public array $tableData = [];

    public ?array $data = [];

    protected static ?string $title = 'Click Inspector';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-magnifying-glass-plus';

    protected static string|null|UnitEnum $navigationGroup = 'Miscellaneous';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.click-inspector';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('clickIdsCsv')
                    ->label('Click IDs CSV File')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'text/comma-separated-values',
                        '.csv',
                    ])
                    ->aboveContent(Text::make('Upload a CSV file with Click IDs in the first column.'))
                    ->storeFiles(false),
            ])
            ->statePath('data');
    }

    public function process(): void
    {
        $this->form->validate();
        $clickIdsCsv = $this->data['clickIdsCsv'] ?? [];
        $this->tableData = [];

        $file = current($clickIdsCsv);
        if (! $file) {
            Notification::make()->title('File Error')->body('Could not access the uploaded file.')->danger()->send();

            return;
        }

        try {
            $reader = Reader::createFromPath($file->getRealPath());
            $records = $reader->fetchColumn();
            $clickIds = collect(iterator_to_array($records))
                ->map(fn ($id): string => mb_trim((string) $id))
                ->filter()
                ->unique();
        } catch (UnableToProcessCsv|Exception $e) {
            Notification::make()->title('CSV Processing Error')->body($e->getMessage())->danger()->send();

            return;
        }

        if ($clickIds->isEmpty()) {
            Notification::make()->title('No Click IDs Found')->body('The uploaded CSV file appears to be empty.')->warning()->send();

            return;
        }

        $clicks = Click::with(['campaign', 'refer', 'conversions.event', 'conversions.earnings.payout'])
            ->whereIn('id', $clickIds->all())
            ->get();

        $foundClickIds = $clicks->pluck('id');
        $notFoundClickIds = $clickIds->diff($foundClickIds);

        if ($notFoundClickIds->isNotEmpty()) {
            Notification::make()->title('Clicks Not Found')->body('Not found: '.$notFoundClickIds->implode(', '))->warning()->send();
        }

        $results = [];
        foreach ($clicks as $click) {
            if ($click->conversions->isEmpty()) {
                $results[] = $this->flattenData($click);
            } else {
                foreach ($click->conversions as $conversion) {
                    if ($conversion->earnings->isEmpty()) {
                        $results[] = $this->flattenData($click, $conversion);
                    } else {
                        foreach ($conversion->earnings as $earning) {
                            $results[] = $this->flattenData($click, $conversion, $earning);
                        }
                    }
                }
            }
        }

        $this->tableData = $results;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getFilteredTableData())
            ->columns([
                TextColumn::make('click_id')->label('Click ID')->searchable()->toggleable(),
                TextColumn::make('campaign_name')->label('Campaign')->searchable()->toggleable(),
                TextColumn::make('user_upi')->label('User UPI')->searchable()->toggleable(),
                TextColumn::make('conversion_id')->label('Conversion ID')->searchable()->toggleable(),
                TextColumn::make('event_param')->label('Event')->searchable()->toggleable(),
                IconColumn::make('is_conversion_valid')->label('Valid')->boolean()->toggleable(),
                TextColumn::make('earning_id')->label('Earning ID')->searchable()->toggleable(),
                TextColumn::make('earning_type')->label('Type')->badge()->toggleable(),
                TextColumn::make('earning_amount')->label('Amount')->money('INR')->toggleable()->sortable(),
                TextColumn::make('payout_status')->label('Status')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'Unpaid' => 'info',
                        default => 'gray',
                    })->toggleable(),
                TextColumn::make('earning_time')->label('Earning Time')->dateTime()->toggleable()->sortable(),
                TextColumn::make('payout_time')->label('Payout Time')->dateTime()->toggleable()->sortable(),
                TextColumn::make('click_time')->label('Click Time')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                TextColumn::make('user_ip')->label('User IP')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('refer_id')->label('Refer ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('refer_upi')->label('Refer UPI')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invalid_reason')->label('Reason')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conversion_time')->label('Conversion Time')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                TextColumn::make('payout_gateway')->label('Gateway')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payout_reference_id')->label('Ref ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payout_payment_id')->label('Payment ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payout_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'success' => 'Success',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('earning_type')
                    ->options(EarningType::class),
                SelectFilter::make('payout_gateway')
                    ->options(function (): array {
                        $payouts = new Payout();
                        $gateways = $payouts->query()
                            ->distinct()
                            ->pluck('payment_gateway', 'payment_gateway')
                            ->all();

                        return $gateways;
                    }),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse|Response => $this->exportData()),
            ])
            ->defaultSort('click_time', 'desc')
            ->deferLoading();
    }

    public function exportData(): StreamedResponse|Response
    {
        $dataToExport = $this->getFilteredTableData();

        if ($dataToExport->isEmpty()) {
            Notification::make()->warning()->title('No Data to Export')->body('There is no data matching the current filters.')->send();

            return response()->noContent();
        }

        $fileName = 'click-inspector-export-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($dataToExport): void {
            $file = fopen('php://output', 'w');
            $columnHeaders = [
                'Click ID', 'Campaign', 'User UPI', 'Conversion ID', 'Event', 'Valid',
                'Earning ID', 'Type', 'Amount', 'Status', 'Earning Time', 'Payout Time', 'Click Time',
                'User IP', 'Refer ID', 'Refer UPI', 'Reason', 'Conversion Time',
                'Gateway', 'Reference ID', 'Payment ID',
            ];
            fputcsv($file, $columnHeaders);

            foreach ($dataToExport as $row) {
                fputcsv($file, [
                    $row['click_id'] ?? '',
                    $row['campaign_name'] ?? '',
                    $row['user_upi'] ?? '',
                    $row['conversion_id'] ?? '',
                    $row['event_param'] ?? '',
                    ($row['is_conversion_valid'] ?? false) ? 'Yes' : 'No',
                    $row['earning_id'] ?? '',
                    $row['earning_type'] ?? '',
                    $row['earning_amount'] ?? '',
                    $row['payout_status'] ?? '',
                    $row['earning_time'] ?? '',
                    $row['payout_time'] ?? '',
                    $row['click_time'] ?? '',
                    $row['user_ip'] ?? '',
                    $row['refer_id'] ?? '',
                    $row['refer_upi'] ?? '',
                    $row['invalid_reason'] ?? '',
                    $row['conversion_time'] ?? '',
                    $row['payout_gateway'] ?? '',
                    $row['payout_reference_id'] ?? '',
                    $row['payout_payment_id'] ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getFilteredTableData(): Collection
    {
        $data = collect($this->tableData);
        $filters = $this->tableFilters;
        $search = $this->tableSearch;
        $sortColumn = $this->tableSortColumn;
        $sortDirection = $this->tableSortDirection;

        if (filled($search)) {
            $data = $data->filter(function ($record) use ($search): bool {
                $searchableColumns = [
                    'click_id', 'campaign_name', 'user_upi', 'conversion_id', 'earning_id',
                    'user_ip', 'refer_id', 'refer_upi', 'payout_reference_id', 'payout_payment_id',
                ];
                foreach ($searchableColumns as $column) {
                    if (isset($record[$column]) && Str::contains((string) $record[$column], $search, ignoreCase: true)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if (filled($filters['payout_status']['value'] ?? null)) {
            $data = $data->where('payout_status', mb_strtolower($filters['payout_status']['value']));
        }
        if (filled($filters['earning_type']['value'] ?? null)) {
            $data = $data->where('earning_type', $filters['earning_type']['value']);
        }
        if (filled($filters['payout_gateway']['value'] ?? null)) {
            $data = $data->where('payout_gateway', $filters['payout_gateway']['value']);
        }

        if ($sortColumn && $sortDirection) {
            return $data->sortBy($sortColumn, SORT_REGULAR, $sortDirection === 'desc');
        }

        return $data;
    }

    private function flattenData(Click $click, ?Conversion $conversion = null, ?Earning $earning = null): array
    {
        return [
            // Click Data
            'click_id' => $click->id,
            'campaign_id' => $click->campaign_id,
            'campaign_name' => $click->campaign->name,
            'click_time' => $click->created_at,
            'user_upi' => $click->upi,
            'user_ip' => $click->ip_address,
            'refer_id' => $click->refer_id,
            'refer_upi' => $click->refer?->upi,

            // Conversion Data
            'conversion_id' => $conversion?->id,
            'event_param' => $conversion?->event->param,
            'event_label' => $conversion?->event->label,
            'is_conversion_valid' => $conversion?->is_valid,
            'invalid_reason' => $conversion?->reason,
            'conversion_time' => $conversion?->created_at,

            // Earning & Payout Data
            'earning_id' => $earning?->id,
            'earning_type' => $earning?->type->value,
            'earning_amount' => $earning?->amount,
            'earning_time' => $earning?->created_at,
            'payout_status' => $earning?->payout?->status->value ?? 'Unpaid',
            'payout_gateway' => $earning?->payout?->payment_gateway,
            'payout_reference_id' => $earning?->payout?->reference_id,
            'payout_payment_id' => $earning?->payout?->payment_id,
            'payout_time' => $earning?->payout?->created_at,
        ];
    }
}
