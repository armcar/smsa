<?php

namespace App\Filament\Exports;

use App\Models\Socio;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class SocioExporter extends Exporter
{
    protected static ?string $model = Socio::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('socioType.code')->label('Tipo'),
            ExportColumn::make('num_socio')->label('Numero'),
            ExportColumn::make('nome')->label('Nome'),
            ExportColumn::make('estado')
                ->label('Estado')
                ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                    'ativo' => 'Ativo',
                    'suspenso' => 'Suspenso',
                    'desistente' => 'Desistente',
                    'falecido' => 'Falecido',
                    default => $state,
                }),
            ExportColumn::make('telefone')->label('Telefone'),
            ExportColumn::make('telemovel')->label('Telemovel'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('numero_fiscal')->label('NIF'),
            ExportColumn::make('morada')->label('Morada'),
            ExportColumn::make('codigo_postal')->label('Codigo Postal'),
            ExportColumn::make('localidade')->label('Localidade'),
            ExportColumn::make('data_nascimento')
                ->label('Data Nascimento')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('data_socio')
                ->label('Data Socio')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('is_instrumentista')
                ->label('Instrumentista')
                ->formatStateUsing(fn ($state): string => (bool) $state ? 'Sim' : 'Nao'),
            ExportColumn::make('instrumento')->label('Instrumento'),
            ExportColumn::make('instrumento_desde')
                ->label('Instrumentista Desde')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
            ExportColumn::make('instrumento_ate')
                ->label('Cessou em')
                ->formatStateUsing(fn ($state): string => filled($state) ? \Illuminate\Support\Carbon::parse($state)->format('d-m-Y') : ''),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['socioType']);
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
