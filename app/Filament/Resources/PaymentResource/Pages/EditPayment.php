<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentCancellationService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

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
            Actions\Action::make('anularPagamento')
                ->label('Anular pagamento')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('motivo_anulacao_recibo')
                        ->label('Motivo da anulação do recibo')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Opcional. Se vazio, será usado um motivo padrão.'),
                ])
                ->visible(fn (Payment $record) => $record->anulado_em === null)
                ->action(function (Payment $record, array $data): void {
                    app(PaymentCancellationService::class)->cancelar(
                        payment: $record,
                        motivoRecibo: $data['motivo_anulacao_recibo'] ?? null,
                    );

                    Notification::make()
                        ->title('Pagamento anulado com sucesso.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
