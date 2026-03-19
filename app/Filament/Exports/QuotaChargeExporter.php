<?php

namespace App\Filament\Exports;

use App\Models\QuotaCharge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class QuotaChargeExporter extends Exporter
{
    protected static ?string $model = QuotaCharge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('quotaYear.ano')->label('Ano'),
            ExportColumn::make('socioType.code')->label('Tipo'),
            ExportColumn::make('socio.num_socio')->label('No Socio'),
            ExportColumn::make('socio.nome')->label('Socio'),
            ExportColumn::make('valor')->label('Valor'),
            ExportColumn::make('estado')
                ->label('Estado')
                ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                    'pendente' => 'Pendente',
                    'pago' => 'Pago',
                    default => $state,
                }),
            ExportColumn::make('emitido_em')
                ->label('Emitido em')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('vencimento_em')
                ->label('Vencimento')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('observacoes')->label('Observacoes'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['quotaYear', 'socioType', 'socio']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successfulRows = Number::format($export->successful_rows);
        $failedRows = $export->getFailedRowsCount();

        if ($failedRows > 0) {
            return "Exportação concluida: {$successfulRows} linhas exportadas e " . Number::format($failedRows) . ' falhas.';
        }

        return "Exportação concluida: {$successfulRows} linhas exportadas.";
    }
}
