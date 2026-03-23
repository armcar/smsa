<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Services\ReceiptService;
use App\Support\TablePdfExport;
use Carbon\Carbon;

use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;

use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Pagamentos';
    protected static ?string $modelLabel = 'Pagamento';
    protected static ?string $pluralModelLabel = 'Pagamentos';
    protected static ?string $navigationGroup = 'Tesouraria';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('quota_charge_id')
                ->label('Quota')
                ->relationship(
                    name: 'quotaCharge',
                    titleAttribute: 'id',
                    modifyQueryUsing: fn (Builder $query) => $query->whereDoesntHave(
                        'payments',
                        fn (Builder $payments) => $payments->whereNull('anulado_em')
                    )
                )
                ->getOptionLabelFromRecordUsing(function (QuotaCharge $record) {
                    $socio = $record->socio?->nome ?? 'Sócio?';
                    $ano = $record->quotaYear?->ano ?? 'Ano?';
                    $valor = number_format((float) $record->valor, 2, ',', '.');
                    $code = $record->socioType?->code ?? '';
                    $num = $record->socio?->num_socio ?? null;
                    $numFmt = $num ? sprintf('%03d', (int) $num) : '';
                    $prefix = trim($code . ' ' . $numFmt);

                    return $prefix !== ''
                        ? "{$prefix} – {$socio} – {$ano} – {$valor}€"
                        : "{$socio} – {$ano} – {$valor}€";
                })
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (string $operation): bool => $operation === 'edit')
                ->live(),

            Placeholder::make('quota_info')
                ->label('Resumo')
                ->content(function (Get $get) {
                    $id = $get('quota_charge_id');
                    if (! $id) return '—';

                    $q = QuotaCharge::with(['socio', 'quotaYear'])->find($id);
                    if (! $q) return '—';

                    $socio = $q->socio?->nome ?? '—';
                    $ano = $q->quotaYear?->ano ?? '—';
                    $valor = number_format((float) $q->valor, 2, ',', '.');

                    return "Sócio: {$socio} | Ano: {$ano} | Valor: {$valor}€";
                }),

            DatePicker::make('data_pagamento')
                ->label('Data de pagamento')
                ->default(now())
                ->closeOnDateSelection()
                ->required(),

            TextInput::make('valor')
                ->label('Valor (€)')
                ->numeric()
                ->required()
                ->minValue(0),

            Select::make('metodo')
                ->label('Método de pagamento')
                ->options([
                    'mbway' => 'MBWay',
                    'transferencia' => 'Transferência Bancária',
                    'dinheiro' => 'Pagamento em Dinheiro',
                ])
                ->native(false)
                ->preload()
                ->required(),

            TextInput::make('documento_tipo')->label('Tipo de Documento')->maxLength(255)->nullable(),
            TextInput::make('documento_numero')->label('Nº Documento')->maxLength(255)->nullable(),
            TextInput::make('referencia')->label('Referência')->maxLength(255)->nullable(),
            Textarea::make('notas')->label('Notas')->rows(3)->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['receipt']))
            ->columns([
                TextColumn::make('quotaCharge.socio.nome')->label('Sócio')->searchable(),
                TextColumn::make('quotaCharge.quotaYear.ano')->label('Ano')->sortable(),
                TextColumn::make('estado_pagamento')
                    ->label('Estado')
                    ->state(fn (Payment $record): string => $record->anulado_em ? 'anulado' : 'ativo')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'anulado' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'anulado' ? 'Anulado' : 'Ativo'),

                TextColumn::make('metodo')
                    ->label('Método')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'mbway' => 'MBWay',
                        'transferencia' => 'Transferência Bancária',
                        'dinheiro' => 'Pagamento em Dinheiro',
                        default => $state,
                    })
                    ->badge(),

                TextColumn::make('valor')->money('EUR', locale: 'pt_PT')->sortable(),
                TextColumn::make('data_pagamento')->label('Pago em')->date('d-m-Y')->sortable(),
                TextColumn::make('estado_recibo')
                    ->label('Recibo')
                    ->state(function (Payment $record): string {
                        $receipt = $record->receipt;
                        if (! $receipt) {
                            return 'nao_emitido';
                        }

                        return $receipt->anulado_em ? 'anulado' : 'emitido';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'emitido' => 'success',
                        'anulado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'emitido' => 'Emitido',
                        'anulado' => 'Anulado',
                        default => 'Não emitido',
                    }),
                TextColumn::make('anulado_em')
                    ->label('Anulado em')
                    ->dateTime('d-m-Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_pagamento', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('quota_year_id')
                    ->label('Ano')
                    ->options(fn () => QuotaYear::query()->orderBy('ano', 'desc')->pluck('ano', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        $yearId = $data['value'] ?? null;

                        if (! $yearId) {
                            return $query;
                        }

                        return $query->whereHas('quotaCharge', fn (Builder $quotaChargeQuery) => $quotaChargeQuery->where('quota_year_id', $yearId));
                    }),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exporter(PaymentExporter::class)
                    ->formats([ExportFormat::Xlsx]),
                Action::make('exportarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-text')
                    ->action(function (HasTable $livewire) {
                        $rows = $livewire
                            ->getTableQueryForExport()
                            ->with(['quotaCharge.quotaYear', 'quotaCharge.socioType', 'quotaCharge.socio'])
                            ->get()
                            ->map(function (Payment $payment): array {
                                return [
                                    $payment->quotaCharge?->quotaYear?->ano ?? '',
                                    $payment->quotaCharge?->socioType?->code ?? '',
                                    $payment->quotaCharge?->socio?->num_socio ?? '',
                                    $payment->quotaCharge?->socio?->nome ?? '',
                                    match ($payment->metodo) {
                                        'mbway' => 'MBWay',
                                        'transferencia' => 'Transferencia Bancaria',
                                        'dinheiro' => 'Pagamento em Dinheiro',
                                        default => (string) $payment->metodo,
                                    },
                                    number_format((float) $payment->valor, 2, ',', '.') . ' EUR',
                                    filled($payment->data_pagamento) ? Carbon::parse($payment->data_pagamento)->format('d-m-Y') : '',
                                    (string) ($payment->referencia ?? ''),
                                    (string) ($payment->documento_tipo ?? ''),
                                    (string) ($payment->documento_numero ?? ''),
                                    (string) ($payment->notas ?? ''),
                                    filled($payment->anulado_em) ? Carbon::parse($payment->anulado_em)->format('d-m-Y H:i') : '',
                                ];
                            })
                            ->all();

                        return TablePdfExport::download(
                            filename: 'pagamentos_' . now()->format('Ymd_His') . '.pdf',
                            title: 'Exportação de Pagamentos',
                            columns: ['Ano', 'Tipo', 'No Socio', 'Socio', 'Metodo', 'Valor', 'Pago em', 'Referencia', 'Tipo Documento', 'No Documento', 'Notas', 'Anulado em'],
                            rows: $rows,
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('emitirRecibo')
                    ->label('Emitir Recibo')
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->modalHeading('Emitir recibo')
                    ->modalDescription('Gera o recibo e envia o PDF por email ao sócio.')
                    ->visible(function ($record) {
                        if (! empty($record->anulado_em)) return false;
                        if (empty($record->data_pagamento)) return false;

                        $qc = $record->quotaCharge?->loadMissing(['socio', 'quotaYear']);
                        if (! $qc || ! $qc->socio || ! $qc->quotaYear) return false;

                        return ! \App\Models\Receipt::where('payment_id', $record->id)
                            ->exists();
                    })
                    ->action(function ($record) {
                        if (! $record->quotaCharge?->loadMissing(['socio', 'quotaYear'])) {
                            Notification::make()->title('Pagamento sem quota/sócio/ano associado.')->danger()->send();
                            return;
                        }

                        try {
                            $receipt = app(ReceiptService::class)->emitirEEnviar(
                                payment: $record,
                                emailDestino: null,
                                forceSendEmail: true
                            );
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Recibo ' . $receipt->numero . ' emitido/enviado.')
                            ->success()
                            ->actions([
                                NotificationAction::make('download')
                                    ->label('Download PDF')
                                    ->url(URL::signedRoute('receipts.download', ['receipt' => $receipt]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),

                Action::make('reenviarRecibo')
                    ->label('Reenviar Recibo')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar recibo')
                    ->modalDescription('Reenvia o recibo por email (sem criar novo número).')
                    ->visible(function ($record) {
                        if (! empty($record->anulado_em)) return false;
                        if (empty($record->data_pagamento)) return false;

                        $qc = $record->quotaCharge?->loadMissing(['socio', 'quotaYear']);
                        if (! $qc || ! $qc->socio || ! $qc->quotaYear) return false;

                        return \App\Models\Receipt::where('payment_id', $record->id)
                            ->whereNull('anulado_em')
                            ->exists();
                    })
                    ->action(function ($record) {
                        $quotaCharge = $record->quotaCharge?->loadMissing(['socio', 'quotaYear']);

                        if (! $quotaCharge || ! $quotaCharge->socio || ! $quotaCharge->quotaYear) {
                            Notification::make()->title('Pagamento sem quota/sócio/ano associado.')->danger()->send();
                            return;
                        }

                        try {
                            $receipt = app(ReceiptService::class)->emitirEEnviar(
                                payment: $record,
                                emailDestino: $quotaCharge->socio->email ?? null,
                                forceSendEmail: true
                            );
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Recibo reenviado: ' . $receipt->numero)
                            ->success()
                            ->actions([
                                NotificationAction::make('download')
                                    ->label('Download PDF')
                                    ->url(URL::signedRoute('receipts.download', ['receipt' => $receipt]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
