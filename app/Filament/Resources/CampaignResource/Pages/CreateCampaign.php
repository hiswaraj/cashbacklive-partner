<?php

declare(strict_types=1);

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Override;

final class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    /**
     * @throws Exception
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        }
        // FIXME: codeCoverageIgnore
        // @codeCoverageIgnoreStart
        catch (Exception $e) {
            // Get the first error message
            $errorMessage = $e->getMessage();

            // Send a notification
            Notification::make()
                ->title('Campaign Creation Error')
                ->body($errorMessage)
                ->danger()
                ->send();

            // Re-throw the exception to halt the creation process and show the form errors
            throw $e;
        }
        // @codeCoverageIgnoreEnd
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}
