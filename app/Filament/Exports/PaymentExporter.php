<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('quotaCharge.quotaYear.ano')->label('Ano'),
            ExportColumn::make('quotaCharge.socioType.code')->label('Tipo'),
            ExportColumn::make('quotaCharge.socio.num_socio')->label('No Socio'),
            ExportColumn::make('quotaCharge.socio.nome')->label('Socio'),
            ExportColumn::make('metodo')
                ->label('Metodo')
                ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                    'mbway' => 'MBWay',
                    'transferencia' => 'Transferencia Bancaria',
                    'dinheiro' => 'Pagamento em Dinheiro',
                    default => $state,
                }),
            ExportColumn::make('valor')->label('Valor'),
            ExportColumn::make('data_pagamento')
                ->label('Pago em')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('referencia')->label('Referencia'),
            ExportColumn::make('documento_tipo')->label('Tipo Documento'),
            ExportColumn::make('documento_numero')->label('No Documento'),
            ExportColumn::make('notas')->label('Notas'),
            ExportColumn::make('anulado_em')
                ->label('Anulado em')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y H:i') : ''),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['quotaCharge.quotaYear', 'quotaCharge.socioType', 'quotaCharge.socio']);
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
