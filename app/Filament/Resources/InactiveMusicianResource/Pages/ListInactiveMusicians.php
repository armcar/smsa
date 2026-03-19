<?php

namespace App\Filament\Resources\InactiveMusicianResource\Pages;

use App\Filament\Resources\InactiveMusicianResource;
use Filament\Resources\Pages\ListRecords;

class ListInactiveMusicians extends ListRecords
{
    protected static string $resource = InactiveMusicianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

