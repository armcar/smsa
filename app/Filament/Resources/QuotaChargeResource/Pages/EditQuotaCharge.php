<?php

namespace App\Filament\Resources\QuotaChargeResource\Pages;

use App\Filament\Resources\QuotaChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotaCharge extends EditRecord
{
    protected static string $resource = QuotaChargeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Registo Editado';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
