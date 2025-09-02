<?php

declare(strict_types=1);

namespace App\Filament\Resources\UPIBlockListResource\Pages;

use App\Filament\Resources\UPIBlockListResource;
use App\Models\UPIBlockList;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

final class ListUPIBlockList extends ManageRecords
{
    protected static string $resource = UPIBlockListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add to Blocklist')
                ->schema([
                    Textarea::make('strings')
                        ->label('Mobile/UPI')
                        ->required()
                        ->placeholder('Mobile (9876543210) or UPI(hacker69@jio) full or partial (@jio)')
                        ->helperText('Enter one or more entries, separated by new lines.'),
                    TextInput::make('block_reason')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $entries = preg_split('/\\r\\n|\\r|\\n/', $data['strings']);
                    $reason = $data['block_reason'];
                    $count = 0;
                    $skipped = 0;

                    DB::transaction(function () use ($entries, $reason, &$count, &$skipped) {
                        foreach ($entries as $entry) {
                            $trimmedEntry = mb_trim($entry);
                            if (empty($trimmedEntry)) {
                                continue;
                            }

                            // Use firstOrCreate to avoid duplicates
                            $result = UPIBlockList::firstOrCreate(
                                ['string' => $trimmedEntry],
                                ['block_reason' => $reason]
                            );

                            if ($result->wasRecentlyCreated) {
                                $count++;
                            } else {
                                $skipped++;
                            }
                        }
                    });

                    $message = "Successfully added {$count} new entries to the blocklist.";
                    if ($skipped > 0) {
                        $message .= " Skipped {$skipped} duplicates.";
                    }

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                }),
        ];
    }
}
