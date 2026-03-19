<?php

namespace App\Filament\Resources;

use App\Filament\Exports\SocioExporter;
use App\Filament\Resources\SocioResource\Pages;
use App\Models\Socio;
use App\Models\SocioType;
use App\Rules\PortugueseNif;
use App\Support\TablePdfExport;
use App\Support\Nif;
use Carbon\Carbon;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class SocioResource extends Resource
{
    protected static ?string $model = Socio::class;

    protected static ?string $navigationLabel = 'Socios';
    protected static ?string $modelLabel = 'Socio';
    protected static ?string $pluralModelLabel = 'Socios';
    protected static ?string $navigationGroup = 'Associados e Musicos';
    protected static ?string $navigationIcon = null;
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                Select::make('socio_type_id')
                    ->label('Tipo de socio')
                    ->options(
                        SocioType::query()
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "{$t->code} - {$t->nome}"])
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->columnSpan(2),

                TextInput::make('num_socio')
                    ->label('Numero')
                    ->helperText('So o numero (ex: 1, 2, 3).')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(999999)
                    ->required()
                    ->rule('integer')
                    ->rules([
                        fn (Get $get, ?Socio $record) => Rule::unique('socios', 'num_socio')
                            ->where(fn ($query) => $query->where('socio_type_id', $get('socio_type_id')))
                            ->ignore($record?->id),
                    ])
                    ->live()
                    ->columnSpan(2),

                Placeholder::make('preview')
                    ->label('Preview')
                    ->content(function (Get $get) {
                        $typeId = $get('socio_type_id');
                        $n = (int) $get('num_socio');
                        if (! $typeId || $n <= 0) {
                            return '-';
                        }

                        $code = SocioType::find($typeId)?->code ?? '?';
                        return sprintf('%s %03d', $code, $n);
                    })
                    ->columnSpan(2),

                Grid::make(12)->schema([
                    Toggle::make('estado_ativo')
                        ->label('')
                        ->live()
                        ->afterStateHydrated(function (?Socio $record, Set $set): void {
                            $estado = $record?->estado ?? 'ativo';
                            $set('estado_ativo', $estado === 'ativo');
                        })
                        ->afterStateUpdated(function (bool $state, Set $set): void {
                            $set('estado', $state ? 'ativo' : 'suspenso');
                        })
                        ->disabled(function (?Socio $record): bool {
                            return $record && in_array($record->estado, ['falecido', 'desistente'], true);
                        })
                        ->extraAttributes(['class' => 'mt-6'])
                        ->columnSpan(2),

                    TextInput::make('nome')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(10),
                ])->columnSpan(6),
            ])->columnSpanFull(),

            TextInput::make('morada')
                ->label('Morada')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('codigo_postal')
                ->label('Codigo Postal')
                ->placeholder('9999-999')
                ->maxLength(8)
                ->regex('/^\d{4}-\d{3}$/')
                ->nullable(),

            TextInput::make('localidade')
                ->label('Localidade')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('telefone')
                ->label('Telefone')
                ->tel()
                ->maxLength(20)
                ->nullable(),

            TextInput::make('telemovel')
                ->label('Telemovel')
                ->tel()
                ->maxLength(20)
                ->nullable(),

            DatePicker::make('data_nascimento')
                ->label('Data de nascimento')
                ->closeOnDateSelection()
                ->minDate(now()->subYears(120))
                ->maxDate(now())
                ->nullable(),

            TextInput::make('numero_fiscal')
                ->label('Numero Fiscal (NIF)')
                ->helperText('9 algarismos (podes escrever com espacos, o sistema limpa).')
                ->required()
                ->live(debounce: 400)
                ->dehydrateStateUsing(fn ($state) => preg_replace('/\D+/', '', (string) $state))
                ->minLength(9)
                ->maxLength(9)
                ->rules(['digits:9'])
                ->rule(new PortugueseNif())
                ->validationMessages([
                    'required' => 'O NIF e obrigatorio.',
                ])
                ->suffixIcon(function (Get $get) {
                    $nif = preg_replace('/\D+/', '', (string) $get('numero_fiscal'));
                    if (strlen($nif) !== 9) {
                        return null;
                    }

                    return Nif::isValid($nif) ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';
                })
                ->suffixIconColor(function (Get $get) {
                    $nif = preg_replace('/\D+/', '', (string) $get('numero_fiscal'));
                    if (strlen($nif) !== 9) {
                        return null;
                    }

                    return Nif::isValid($nif) ? 'success' : 'danger';
                })
                ->columnSpan(1),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255)
                ->nullable(),

            Grid::make(12)->schema([
                Toggle::make('is_instrumentista')
                    ->label('Instrumentista')
                    ->inline(false)
                    ->live()
                    ->columnSpan(2),

                TextInput::make('instrumento')
                    ->label('Instrumento')
                    ->maxLength(120)
                    ->visible(fn (Get $get): bool => (bool) $get('is_instrumentista'))
                    ->required(fn (Get $get): bool => (bool) $get('is_instrumentista'))
                    ->columnSpan(4),

                DatePicker::make('instrumento_desde')
                    ->label('Instrumentista desde')
                    ->closeOnDateSelection()
                    ->visible(fn (Get $get): bool => (bool) $get('is_instrumentista'))
                    ->required(fn (Get $get): bool => (bool) $get('is_instrumentista'))
                    ->maxDate(now())
                    ->columnSpan(3),

                DatePicker::make('instrumento_ate')
                    ->label('Cessou em')
                    ->closeOnDateSelection()
                    ->visible(fn (Get $get): bool => (bool) $get('is_instrumentista'))
                    ->rule('after_or_equal:instrumento_desde')
                    ->helperText('Opcional. Preencher quando deixar de tocar.')
                    ->columnSpan(3),
            ])->columnSpanFull(),

            DatePicker::make('data_socio')
                ->label('Data de inscricao')
                ->closeOnDateSelection()
                ->minDate(now()->subYears(60))
                ->maxDate(now()->addYears(1))
                ->nullable(),

            Select::make('estado')
                ->label('Estado')
                ->options([
                    'ativo' => 'Ativo',
                    'suspenso' => 'Suspenso',
                    'desistente' => 'Desistente',
                    'falecido' => 'Falecido',
                ])
                ->default('ativo')
                ->required()
                ->live()
                ->afterStateHydrated(function (?Socio $record, Set $set): void {
                    $estado = $record?->estado ?? 'ativo';
                    $set('estado', $estado);
                    $set('estado_ativo', $estado === 'ativo');
                })
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $set('estado_ativo', $state === 'ativo');
                }),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('num_socio')
                    ->label('N')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nome')
                    ->label('Nome')
                    ->formatStateUsing(function ($state, Socio $record) {
                        $estado = $record->estado;

                        $icon = $estado === 'ativo'
                            ? '<span class="text-success-600 font-semibold">&#10004;</span>'
                            : '<span class="text-danger-600 font-semibold">&#10006;</span>';

                        $nameClass = match ($estado) {
                            'ativo' => 'text-gray-900 dark:text-gray-100',
                            'suspenso' => 'text-gray-500 dark:text-gray-400',
                            'desistente' => 'text-gray-400 dark:text-gray-500 italic',
                            'falecido' => 'text-gray-400 dark:text-gray-500 italic line-through',
                            default => 'text-gray-700 dark:text-gray-200',
                        };

                        return $icon . ' ' . '<span class="' . $nameClass . '">' . e($state) . '</span>';
                    })
                    ->html()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('telemovel')
                    ->label('Telemovel')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('numero_fiscal')
                    ->label('NIF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('instrumento')
                    ->label('Instrumento')
                    ->toggleable(),

                TextColumn::make('data_socio')
                    ->label('Data Socio')
                    ->date('d-m-Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('instrumentistas_ativos')
                    ->label('Instrumentistas ativos')
                    ->query(fn ($query) => $query
                        ->where('is_instrumentista', true)
                        ->where('estado', 'ativo')),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exporter(SocioExporter::class)
                    ->formats([ExportFormat::Xlsx]),
                Tables\Actions\Action::make('exportarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-text')
                    ->action(function (HasTable $livewire) {
                        $rows = $livewire
                            ->getTableQueryForExport()
                            ->with(['socioType'])
                            ->get()
                            ->map(function (Socio $socio): array {
                                return [
                                    $socio->socioType?->code ?? '',
                                    (string) ($socio->num_socio ?? ''),
                                    (string) ($socio->nome ?? ''),
                                    match ($socio->estado) {
                                        'ativo' => 'Ativo',
                                        'suspenso' => 'Suspenso',
                                        'desistente' => 'Desistente',
                                        'falecido' => 'Falecido',
                                        default => (string) $socio->estado,
                                    },
                                    (string) ($socio->telefone ?? ''),
                                    (string) ($socio->telemovel ?? ''),
                                    (string) ($socio->email ?? ''),
                                    (string) ($socio->numero_fiscal ?? ''),
                                    (string) ($socio->morada ?? ''),
                                    (string) ($socio->codigo_postal ?? ''),
                                    (string) ($socio->localidade ?? ''),
                                    filled($socio->data_nascimento) ? Carbon::parse($socio->data_nascimento)->format('d-m-Y') : '',
                                    filled($socio->data_socio) ? Carbon::parse($socio->data_socio)->format('d-m-Y') : '',
                                    $socio->is_instrumentista ? 'Sim' : 'Nao',
                                    (string) ($socio->instrumento ?? ''),
                                    filled($socio->instrumento_desde) ? Carbon::parse($socio->instrumento_desde)->format('d-m-Y') : '',
                                    filled($socio->instrumento_ate) ? Carbon::parse($socio->instrumento_ate)->format('d-m-Y') : '',
                                ];
                            })
                            ->all();

                        return TablePdfExport::download(
                            filename: 'socios_' . now()->format('Ymd_His') . '.pdf',
                            title: 'Exportação de Sócios',
                            columns: ['Tipo', 'Numero', 'Nome', 'Estado', 'Telefone', 'Telemovel', 'Email', 'NIF', 'Morada', 'Codigo Postal', 'Localidade', 'Data Nascimento', 'Data Socio', 'Instrumentista', 'Instrumento', 'Instrumentista Desde', 'Cessou em'],
                            rows: $rows,
                        );
                    }),
            ])
            ->defaultSort('num_socio', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocios::route('/'),
            'create' => Pages\CreateSocio::route('/create'),
            'edit' => Pages\EditSocio::route('/{record}/edit'),
        ];
    }
}
