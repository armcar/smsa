<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocioTypeResource\Pages;
use App\Models\SocioType;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class SocioTypeResource extends Resource
{
    protected static ?string $model = SocioType::class;

protected static ?string $navigationLabel = 'Tipos de Socio';
protected static ?string $modelLabel = 'Tipo de Socio';
protected static ?string $pluralModelLabel = 'Tipos de Socio';
protected static ?string $navigationGroup = 'Associados e Musicos';
    protected static ?string $navigationIcon = null;
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Tipo de socio')
                ->schema([
                    TextInput::make('code')
                        ->label('Codigo')
                        ->helperText('A = Musicos | B = Socio | C = Outro | D = Desativado')
                        ->required()
                        ->maxLength(1)
                        ->regex('/^[ABCD]$/')
                        ->unique(ignoreRecord: true),

                    TextInput::make('nome')
                        ->label('Nome do tipo')
                        ->required()
                        ->maxLength(100),

                    Textarea::make('descricao')
                        ->label('Descricao')
                        ->rows(3)
                        ->columnSpanFull()
                        ->nullable(),

                    Toggle::make('ativo')
                        ->label('Ativo')
                        ->default(true)
                        ->helperText('Sugestao: "D - Desativado" deve ficar inativo.')
                        ->afterStateHydrated(function (Toggle $component, ?SocioType $record) {
                            // ao editar: se for D, forca false (mas podes voltar a ligar manualmente se quiseres)
                            if ($record?->code === 'D') {
                                $component->state(false);
                            }
                        }),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codigo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nome')
                    ->label('Tipo')
                    ->sortable()
                    ->searchable(),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('descricao')
                    ->label('Descricao')
                    ->limit(50)
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSocioTypes::route('/'),
            'create' => Pages\CreateSocioType::route('/create'),
            'edit'   => Pages\EditSocioType::route('/{record}/edit'),
        ];
    }
}


