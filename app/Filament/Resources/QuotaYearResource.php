<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotaYearResource\Pages;
use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Models\Socio;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QuotaYearResource extends Resource
{
    protected static ?string $model = QuotaYear::class;

    protected static ?string $navigationLabel = 'Quotas por Ano';
    protected static ?string $modelLabel = 'Quota por Ano';
    protected static ?string $pluralModelLabel = 'Quotas por Ano';
    protected static ?string $navigationGroup = '💰 Tesouraria';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                TextInput::make('ano')
                    ->label('Ano')
                    ->numeric()
                    ->required()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->unique(ignoreRecord: true)
                    ->columnSpan(3),

                TextInput::make('valor')
                    ->label('Valor (€)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->columnSpan(3),

                Toggle::make('ativo')
                    ->label('Ativo')
                    ->columnSpan(2),

                DatePicker::make('data_inicio')
                    ->label('Data Início')
                    ->closeOnDateSelection()
                    ->required()
                    ->columnSpan(2),

                DatePicker::make('data_fim')
                    ->label('Data Fim')
                    ->closeOnDateSelection()
                    ->required()
                    ->columnSpan(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ano')->label('Ano')->sortable()->searchable(),
                TextColumn::make('valor')->label('Valor')->money('EUR', locale: 'pt_PT')->sortable(),
                IconColumn::make('ativo')->label('Ativo')->boolean()->sortable(),
                TextColumn::make('data_inicio')->label('Data Início')->date('d-m-Y')->sortable(),
                TextColumn::make('data_fim')->label('Data Fim')->date('d-m-Y')->sortable(),
            ])
            ->defaultSort('ano', 'desc')
            ->actions([
                Action::make('lancarQuotas')
                    ->label('Lançar Quotas')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn(QuotaYear $record) => (bool) $record->ativo)
                    ->requiresConfirmation()
                    ->action(function (QuotaYear $record) {
                        $created = 0;

                        DB::transaction(function () use ($record, &$created) {
                            $socios = Socio::query()
                                ->where('estado', 'ativo')
                                ->whereHas('socioType', fn(Builder $q) => $q->where('code', 'B'))
                                ->get();

                            foreach ($socios as $socio) {
                                $charge = QuotaCharge::firstOrCreate(
                                    [
                                        'socio_id' => $socio->id,
                                        'quota_year_id' => $record->id,
                                    ],
                                    [
                                        'socio_type_id' => $socio->socio_type_id,
                                        'valor' => $record->valor,
                                        'estado' => 'pendente',
                                        'emitido_em' => now()->toDateString(),
                                        'vencimento_em' => optional($record->data_fim)->toDateString(),
                                        'observacoes' => null,
                                    ]
                                );

                                if ($charge->wasRecentlyCreated) {
                                    $created++;
                                }
                            }
                        });

                        Notification::make()
                            ->title('Lançamento concluído')
                            ->body("Foram criadas {$created} quotas pendentes para o ano {$record->ano}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotaYears::route('/'),
            'create' => Pages\CreateQuotaYear::route('/create'),
            'edit' => Pages\EditQuotaYear::route('/{record}/edit'),
        ];
    }
}