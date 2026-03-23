<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotaYearResource\Pages;
use App\Models\QuotaYear;
use App\Services\AnnualQuotaGenerationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotaYearResource extends Resource
{
    protected static ?string $model = QuotaYear::class;

    protected static ?string $navigationLabel = 'Quotas Anuais';
    protected static ?string $modelLabel = 'Quota Anual';
    protected static ?string $pluralModelLabel = 'Quotas Anuais';
    protected static ?string $navigationGroup = 'Tesouraria';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 1;

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
                    ->visible(fn (QuotaYear $record) => (bool) $record->ativo)
                    ->requiresConfirmation()
                    ->action(function (QuotaYear $record) {
                        $result = app(AnnualQuotaGenerationService::class)->generateForQuotaYear($record);

                        Notification::make()
                            ->title('Lançamento concluído')
                            ->body("Foram criadas {$result['created']} quotas pendentes para o ano {$record->ano}.")
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
