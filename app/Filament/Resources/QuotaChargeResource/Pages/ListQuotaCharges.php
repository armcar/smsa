<?php

namespace App\Filament\Resources\QuotaChargeResource\Pages;

use App\Filament\Resources\QuotaChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotaCharges extends ListRecords
{
    protected static string $resource = QuotaChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
