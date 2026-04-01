<?php

namespace App\Filament\Resources\SocioResource\Pages;

use App\Filament\Resources\SocioResource;
use App\Models\Socio;
use Filament\Actions;
use Filament\Notifications\Notification;
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
            Actions\Action::make('inativar')
                ->label('Inativar')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Socio $record): bool => $record->isAtivo())
                ->action(function (Socio $record): void {
                    $record->inativar();

                    Notification::make()
                        ->title('Socio inativado com sucesso.')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('reativar')
                ->label('Reativar')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Socio $record): bool => ! $record->isAtivo())
                ->action(function (Socio $record): void {
                    $record->reativar();

                    Notification::make()
                        ->title('Socio reativado com sucesso.')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->visible(fn (Socio $record): bool => ! $record->hasMovimentos()),
        ];
    }
}
