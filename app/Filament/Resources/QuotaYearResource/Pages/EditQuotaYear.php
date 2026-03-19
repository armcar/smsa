<?php

namespace App\Filament\Resources\QuotaYearResource\Pages;

use App\Filament\Resources\QuotaYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotaYear extends EditRecord
{
    protected static string $resource = QuotaYearResource::class;

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
