<?php

namespace App\Filament\Resources\SocioResource\Pages;

use App\Filament\Resources\SocioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocio extends EditRecord
{
    protected static string $resource = SocioResource::class;

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
