<?php

namespace App\Filament\Resources\QuotaYearResource\Pages;

use App\Filament\Resources\QuotaYearResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotaYears extends ListRecords
{
    protected static string $resource = QuotaYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
