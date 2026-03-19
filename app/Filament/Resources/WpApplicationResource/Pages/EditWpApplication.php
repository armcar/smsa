<?php

namespace App\Filament\Resources\WpApplicationResource\Pages;

use App\Filament\Resources\WpApplicationResource;
use Filament\Resources\Pages\EditRecord;

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
            $data['target_num_socio'] = 999;
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

        if ($this->previousStatus !== $this->record->status) {
            $this->record->syncStatusBackToWordPress();
        }
    }
}
