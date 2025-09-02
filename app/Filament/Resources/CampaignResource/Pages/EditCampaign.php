<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Override;

final class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    /**
     * @throws Exception
     */
    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        }
        // FIXME: codeCoverageIgnore
        // @codeCoverageIgnoreStart
        catch (Exception $e) {
            // Get the first error message
            $errorMessage = $e->getMessage();

            // Send a notification
            Notification::make()
                ->title('Campaign Update Error')
                ->body($errorMessage)
                ->danger()
                ->send();

            // Re-throw the exception to halt the update process and show the form errors
            throw $e;
        }
        // @codeCoverageIgnoreEnd
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->action(function () {
                    $newCampaign = $this->record->duplicate();
                    Notification::make()
                        ->title('Campaign duplicated')
                        ->success()
                        ->send();

                    return redirect()->to(CampaignResource::getUrl('edit', [$newCampaign]));
                }),
            DeleteAction::make(),
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}
