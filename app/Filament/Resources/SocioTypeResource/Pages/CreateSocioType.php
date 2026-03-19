<?php

namespace App\Filament\Resources\SocioTypeResource\Pages;

use App\Filament\Resources\SocioTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSocioType extends CreateRecord
{
    protected static string $resource = SocioTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registo Inserido';
    }
}
