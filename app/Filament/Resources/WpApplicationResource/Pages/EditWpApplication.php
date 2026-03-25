<?php

namespace App\Filament\Resources\WpApplicationResource\Pages;

use App\Filament\Resources\WpApplicationResource;
use App\Models\SocioType;
use App\Services\SocioNumberService;
use App\Services\WordPressUserProvisioner;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Throwable;

class EditWpApplication extends EditRecord
{
    protected static string $resource = WpApplicationResource::class;

    protected ?string $previousStatus = null;
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $kind = (string) ($data['kind'] ?? '');

        if (empty($data['target_socio_type_code'])) {
            $data['target_socio_type_code'] = $kind === 'escola' ? 'A' : 'B';
        }

        if (empty($data['target_num_socio'])) {
            $code = strtoupper((string) ($data['target_socio_type_code'] ?? ($kind === 'escola' ? 'A' : 'B')));
            $data['target_num_socio'] = $this->nextTargetNumber($code);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousStatus = $this->record->status;

        if (($data['status'] ?? null) !== 'pendente' && empty($data['resolved_at'])) {
            $data['resolved_at'] = now();
        }

        if (($data['status'] ?? null) === 'pendente') {
            $data['resolved_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->tryAutoCreateSocioOnValidation();

        if ($this->previousStatus !== 'validada' && $this->record->status === 'validada' && $this->record->importedSocio) {
            try {
                $result = app(WordPressUserProvisioner::class)->createMemberUser($this->record->importedSocio);
                $message = $this->buildWpProvisioningNotificationMessage($result->message, $result->username, $result->plainPassword);

                Notification::make()
                    ->title('Integração WordPress')
                    ->body($message)
                    ->success()
                    ->send();
            } catch (Throwable $e) {
                Notification::make()
                    ->title('Integração WordPress')
                    ->body('Falha ao criar/associar utilizador WordPress: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        if ($this->previousStatus !== $this->record->status) {
            $this->record->syncStatusBackToWordPress();
        }
    }

    private function buildWpProvisioningNotificationMessage(string $baseMessage, string $username, ?string $plainPassword): string
    {
        if (! app()->environment(['local', 'development'])) {
            return $baseMessage;
        }

        if ($plainPassword === null || $plainPassword === '') {
            return $baseMessage . " Username: {$username}.";
        }

        return $baseMessage
            . " Username: {$username} | Password: {$plainPassword} | Apenas visível agora (ambiente de testes).";
    }

    private function nextTargetNumber(string $targetCode): int
    {
        $type = SocioType::query()->where('code', $targetCode)->first();

        if (! $type) {
            return 1;
        }

        return app(SocioNumberService::class)->getNextNumberForType($type->id);
    }
}
