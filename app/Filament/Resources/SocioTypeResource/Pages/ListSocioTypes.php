<?php

namespace App\Filament\Resources\SocioTypeResource\Pages;

use App\Filament\Resources\SocioTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocioTypes extends ListRecords
{
    protected static string $resource = SocioTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
