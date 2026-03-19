<?php

namespace App\Filament\Resources\QuotaYearResource\Pages;

use App\Filament\Resources\QuotaYearResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotaYear extends CreateRecord
{
    protected static string $resource = QuotaYearResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registo Inserido';
    }
}
