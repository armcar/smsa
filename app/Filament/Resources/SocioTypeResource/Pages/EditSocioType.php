<?php

namespace App\Filament\Resources\SocioTypeResource\Pages;

use App\Filament\Resources\SocioTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocioType extends EditRecord
{
    protected static string $resource = SocioTypeResource::class;

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
