<?php

namespace App\Filament\Resources\QuotaChargeResource\Pages;

use App\Filament\Resources\QuotaChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotaCharge extends CreateRecord
{
    protected static string $resource = QuotaChargeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registo Inserido';
    }
}
