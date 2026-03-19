<?php

namespace App\Filament\Resources\ActiveMusicianResource\Pages;

use App\Filament\Resources\ActiveMusicianResource;
use Filament\Resources\Pages\ListRecords;

class ListActiveMusicians extends ListRecords
{
    protected static string $resource = ActiveMusicianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

