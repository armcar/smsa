<?php

namespace App\Filament\Resources;

use App\Filament\Exports\QuotaChargeExporter;
use App\Filament\Resources\QuotaChargeResource\Pages;
use App\Models\Payment;
use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Support\TablePdfExport;
use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class QuotaChargeResource extends Resource
{
    protected static ?string $model = QuotaCharge::class;

    protected static ?string $navigationLabel = 'Quotas (lançamentos)';
    protected static ?string $modelLabel = 'Quota (lançamento)';
    protected static ?string $pluralModelLabel = 'Quotas (lançamentos)';
    protected static ?string $navigationGroup = '💰 Tesouraria';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                Select::make('quota_year_id')
                    ->label('Ano')
                    ->relationship('quotaYear', 'ano')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(3),

                Select::make('socio_id')
                    ->label('Sócio')
                    ->relationship('socio', 'nome')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(6),

                Select::make('socio_type_id')
                    ->label('Tipo')
                    ->relationship('socioType', 'nome')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(3),

                TextInput::make('valor')
                    ->label('Valor (€)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->columnSpan(3),

                Select::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                    ])
                    ->required()
                    ->default('pendente')
                    ->columnSpan(3),

                DatePicker::make('emitido_em')
                    ->label('Emitido em')
                    ->closeOnDateSelection()
                    ->nullable()
                    ->columnSpan(3),

                DatePicker::make('vencimento_em')
                    ->label('Vencimento')
                    ->closeOnDateSelection()
                    ->nullable()
                    ->columnSpan(3),

                Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(3)
                    ->columnSpan(12),

                Placeholder::make('payment_info')
                    ->label('Pagamento')
                    ->content(function (?QuotaCharge $record) {
                        if (! $record) return '—';
                        $p = $record->payments()->whereNull('anulado_em')->latest('data_pagamento')->first();
                        if (! $p) return 'Sem pagamento registado.';

                        $metodo = match ($p->metodo) {
                            'mbway' => 'MBWay',
                            'transferencia' => 'Transferência Bancária',
                            'dinheiro' => 'Pagamento em Dinheiro',
                            default => (string) $p->metodo,
                        };

                        $data = optional($p->data_pagamento)->format('d-m-Y') ?? '—';
                        $valor = number_format((float) $p->valor, 2, ',', '.');

                        return "Pago em {$data} | {$metodo} | {$valor}€";
                    })
                    ->columnSpan(12),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['socio', 'quotaYear', 'socioType']))
            ->columns([
                TextColumn::make('quotaYear.ano')->label('Ano')->sortable(),
                TextColumn::make('socioType.code')->label('Tipo')->sortable(),
                TextColumn::make('socio.num_socio')->label('Nº')->sortable(),
                TextColumn::make('socio.nome')->label('Sócio')->searchable()->wrap(),
                TextColumn::make('valor')->label('Valor')->money('EUR', locale: 'pt_PT')->sortable(),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendente',
                        'success' => 'pago',
                    ])
                    ->formatStateUsing(fn(string $state) => $state === 'pago' ? 'Pago' : 'Pendente')
                    ->sortable(),
            ])
            ->defaultSort('quota_year_id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('quota_year_id')
                    ->label('Ano')
                    ->options(fn() => QuotaYear::query()->orderBy('ano', 'desc')->pluck('ano', 'id')->toArray()),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exporter(QuotaChargeExporter::class)
                    ->formats([ExportFormat::Xlsx]),
                Action::make('exportarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-text')
                    ->action(function (HasTable $livewire) {
                        $rows = $livewire
                            ->getTableQueryForExport()
                            ->with(['quotaYear', 'socioType', 'socio'])
                            ->get()
                            ->map(function (QuotaCharge $quotaCharge): array {
                                return [
                                    $quotaCharge->quotaYear?->ano ?? '',
                                    $quotaCharge->socioType?->code ?? '',
                                    $quotaCharge->socio?->num_socio ?? '',
                                    $quotaCharge->socio?->nome ?? '',
                                    number_format((float) $quotaCharge->valor, 2, ',', '.') . ' EUR',
                                    $quotaCharge->estado === 'pago' ? 'Pago' : 'Pendente',
                                    filled($quotaCharge->emitido_em) ? Carbon::parse($quotaCharge->emitido_em)->format('d-m-Y') : '',
                                    filled($quotaCharge->vencimento_em) ? Carbon::parse($quotaCharge->vencimento_em)->format('d-m-Y') : '',
                                    (string) ($quotaCharge->observacoes ?? ''),
                                ];
                            })
                            ->all();

                        return TablePdfExport::download(
                            filename: 'quotas_' . now()->format('Ymd_His') . '.pdf',
                            title: 'Exportação de Quotas',
                            columns: ['Ano', 'Tipo', 'No Socio', 'Socio', 'Valor', 'Estado', 'Emitido em', 'Vencimento', 'Observacoes'],
                            rows: $rows,
                        );
                    }),
            ])
            ->actions([
                Action::make('registarPagamento')
                    ->label('Registar Pagamento')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn(QuotaCharge $record) => $record->estado !== 'pago' && ! $record->payments()->whereNull('anulado_em')->exists())
                    ->form([
                        DatePicker::make('data_pagamento')->label('Data de pagamento')->default(now())->required(),
                        TextInput::make('valor')->label('Valor (€)')->numeric()->required()->default(fn(QuotaCharge $record) => (float) $record->valor),
                        Select::make('metodo')
                            ->label('Método')
                            ->options([
                                'mbway' => 'MBWay',
                                'transferencia' => 'Transferência Bancária',
                                'dinheiro' => 'Pagamento em Dinheiro',
                            ])
                            ->native(false)
                            ->required(),
                        TextInput::make('referencia')->label('Referência')->maxLength(255)->nullable(),
                        Textarea::make('notas')->label('Notas')->rows(3)->nullable(),
                    ])
                    ->action(function (QuotaCharge $record, array $data) {
                        Payment::create([
                            'quota_charge_id' => $record->id,
                            'data_pagamento' => $data['data_pagamento'],
                            'valor' => $data['valor'],
                            'metodo' => $data['metodo'],
                            'referencia' => $data['referencia'] ?? null,
                            'notas' => $data['notas'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Pagamento registado')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn(QuotaCharge $record) => $record->estado !== 'pago'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotaCharges::route('/'),
            'create' => Pages\CreateQuotaCharge::route('/create'),
            'edit' => Pages\EditQuotaCharge::route('/{record}/edit'),
        ];
    }
}
