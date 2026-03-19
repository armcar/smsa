<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActiveMusicianResource\Pages;
use App\Models\Socio;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActiveMusicianResource extends Resource
{
    protected static ?string $model = Socio::class;

    protected static ?string $navigationLabel = 'Musicos ativos';
    protected static ?string $modelLabel = 'Musico ativo';
    protected static ?string $pluralModelLabel = 'Musicos ativos';
    protected static ?string $navigationGroup = 'Associados e Musicos';
    protected static ?string $navigationIcon = null;
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('is_instrumentista', true)
                ->where('estado', 'ativo')
                ->orderBy('num_socio'))
            ->columns([
                TextColumn::make('num_socio')
                    ->label('N')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('instrumento')
                    ->label('Instrumento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('instrumento_desde')
                    ->label('Desde')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('instrumento_ate')
                    ->label('Ate')
                    ->date('d-m-Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('telemovel')
                    ->label('Telemovel')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Socio $record): string => SocioResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActiveMusicians::route('/'),
        ];
    }
}
