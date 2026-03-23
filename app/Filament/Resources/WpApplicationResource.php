<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WpApplicationResource\Pages;
use App\Filament\Resources\SocioResource;
use App\Models\Socio;
use App\Models\SocioType;
use App\Models\WpApplication;
use Filament\Forms\Get;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class WpApplicationResource extends Resource
{
    protected static ?string $model = WpApplication::class;

    protected static ?string $navigationLabel = 'Pedidos Externos';
    protected static ?string $modelLabel = 'Pedido Externo';
    protected static ?string $pluralModelLabel = 'Pedidos Externos';
    protected static ?string $navigationGroup = '🔗 Integrações';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Origem e Verificacao WP')->schema([
                TextInput::make('source')
                    ->label('Origem')
                    ->disabled(),
                TextInput::make('kind')
                    ->label('Tipo')
                    ->disabled(),
                TextInput::make('external_id')
                    ->label('ID no WP')
                    ->disabled(),
                TextInput::make('display_name')
                    ->label('Nome')
                    ->disabled(),
                TextInput::make('display_email')
                    ->label('Email')
                    ->disabled(),
                Placeholder::make('submitted_at_wp')
                    ->label('Submetido no WP')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.submitted_at_wp', (string) optional($record?->submitted_at)->format('Y-m-d H:i:s'))),
                Placeholder::make('nif_validation')
                    ->label('Validacao NIF (WP)')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.nif_validation')),
                Placeholder::make('nif_valid')
                    ->label('NIF valido')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.nif_valid')),
                Placeholder::make('nonce_verified')
                    ->label('Nonce verificado')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.nonce_verified')),
                Placeholder::make('request_ip')
                    ->label('IP origem')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.request_ip')),
                Placeholder::make('user_agent')
                    ->label('User-Agent')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.user_agent')),
                Placeholder::make('referer')
                    ->label('Referer')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_verification.referer')),
                Placeholder::make('wp_pdf_generated')
                    ->label('PDF WP gerado')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_pdf.generated')),
                Placeholder::make('wp_pdf_hash')
                    ->label('Hash PDF WP')
                    ->content(fn (?WpApplication $record) => self::payloadValue($record, 'wp_pdf.hash')),
                TextInput::make('wp_status_callback_url')
                    ->label('Callback WP')
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(3)
                ->collapsed(),

            Section::make('Formulario Novo Socio (WP)')->schema([
                Placeholder::make('s_nome')->label('Nome')->content(fn (?WpApplication $record) => self::payloadValue($record, 'nome')),
                Placeholder::make('s_morada')->label('Morada')->content(fn (?WpApplication $record) => self::payloadValue($record, 'morada')),
                Placeholder::make('s_codigo_postal')->label('Codigo Postal')->content(fn (?WpApplication $record) => self::payloadValue($record, 'codigo_postal', self::payloadValue($record, 'cod_postal'))),
                Placeholder::make('s_localidade')->label('Localidade')->content(fn (?WpApplication $record) => self::payloadValue($record, 'localidade')),
                Placeholder::make('s_telefone')->label('Telefone')->content(fn (?WpApplication $record) => self::payloadValue($record, 'telefone')),
                Placeholder::make('s_telemovel')->label('Telemovel')->content(fn (?WpApplication $record) => self::payloadValue($record, 'telemovel')),
                Placeholder::make('s_data_nascimento')->label('Data de nascimento')->content(fn (?WpApplication $record) => self::payloadValue($record, 'data_nascimento', self::payloadValue($record, 'data_nasc'))),
                Placeholder::make('s_nif')->label('NIF')->content(fn (?WpApplication $record) => self::payloadValue($record, 'numero_fiscal', self::payloadValue($record, 'nif'))),
                Placeholder::make('s_email')->label('Email')->content(fn (?WpApplication $record) => self::payloadValue($record, 'email')),
                Placeholder::make('s_rgpd')->label('RGPD')->content(fn (?WpApplication $record) => self::payloadValue($record, 'rgpd')),
                Placeholder::make('s_obs')->label('Observacoes')->content(fn (?WpApplication $record) => self::payloadValue($record, 'obs')),
            ])->columns(3)
                ->visible(fn (?WpApplication $record) => $record?->kind === 'socio'),

            Section::make('Formulario Novo Aluno (WP)')->schema([
                Placeholder::make('e_aluno_nome')->label('Aluno nome')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_nome')),
                Placeholder::make('e_aluno_morada')->label('Aluno morada')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_morada')),
                Placeholder::make('e_aluno_cod_postal')->label('Aluno codigo postal')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_cod_postal')),
                Placeholder::make('e_aluno_localidade')->label('Aluno localidade')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_localidade')),
                Placeholder::make('e_aluno_telefone')->label('Aluno telefone')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_telefone')),
                Placeholder::make('e_aluno_telemovel')->label('Aluno telemovel')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_telemovel')),
                Placeholder::make('e_aluno_data_nasc')->label('Aluno data nascimento')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_data_nascimento', self::payloadValue($record, 'aluno_data_nasc'))),
                Placeholder::make('e_aluno_nif')->label('Aluno NIF')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_numero_fiscal', self::payloadValue($record, 'aluno_nif'))),
                Placeholder::make('e_aluno_email')->label('Aluno email')->content(fn (?WpApplication $record) => self::payloadValue($record, 'aluno_email')),
                Placeholder::make('e_instrumento')->label('Instrumento')->content(fn (?WpApplication $record) => self::payloadValue($record, 'instrumento')),
                Placeholder::make('e_enc_nome')->label('Encarregado nome')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_nome')),
                Placeholder::make('e_enc_morada')->label('Encarregado morada')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_morada')),
                Placeholder::make('e_enc_cod_postal')->label('Encarregado codigo postal')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_cod_postal')),
                Placeholder::make('e_enc_localidade')->label('Encarregado localidade')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_localidade')),
                Placeholder::make('e_enc_telefone')->label('Encarregado telefone')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_telefone')),
                Placeholder::make('e_enc_telemovel')->label('Encarregado telemovel')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_telemovel')),
                Placeholder::make('e_enc_email')->label('Encarregado email')->content(fn (?WpApplication $record) => self::payloadValue($record, 'enc_email')),
                Placeholder::make('e_rgpd')->label('RGPD')->content(fn (?WpApplication $record) => self::payloadValue($record, 'rgpd')),
                Placeholder::make('e_obs')->label('Observacoes')->content(fn (?WpApplication $record) => self::payloadValue($record, 'obs')),
            ])->columns(3)
                ->visible(fn (?WpApplication $record) => $record?->kind === 'escola'),

            Section::make('Gestao Laravel')->schema([
                TextInput::make('imported_socio_id')
                    ->label('Socio criado (ID)')
                    ->disabled()
                    ->dehydrated(false),
                Placeholder::make('open_socio_record')
                    ->label('Ficha de socio')
                    ->content(function (?WpApplication $record): HtmlString|string {
                        $socioId = $record?->imported_socio_id;
                        if (! $socioId) {
                            return '—';
                        }

                        $url = SocioResource::getUrl('edit', ['record' => $socioId]);

                        return new HtmlString('<a href="' . e($url) . '" class="text-primary-600 underline">Ver ficha de socio completa</a>');
                    }),
                Select::make('target_socio_type_code')
                    ->label('Tipo de socio alvo')
                    ->options([
                        'A' => 'A (pedido de aluno)',
                        'B' => 'B (pedido de socio)',
                    ])
                    ->helperText('Default: A para aluno, B para socio.'),
                TextInput::make('target_num_socio')
                    ->label('Numero de socio a atribuir')
                    ->numeric()
                    ->minValue(1)
                    ->rule('integer')
                    ->live(debounce: 500)
                    ->suffixIcon(function (Get $get, ?WpApplication $record): ?string {
                        $value = $get('target_num_socio');
                        if ($value === null || $value === '') {
                            return null;
                        }

                        $number = (int) $value;
                        if ($number <= 0) {
                            return null;
                        }

                        $targetCode = strtoupper((string) ($get('target_socio_type_code') ?: ($record?->kind === 'escola' ? 'A' : 'B')));
                        $targetType = SocioType::query()->where('code', $targetCode)->first();
                        if (! $targetType) {
                            return null;
                        }

                        $existing = Socio::query()
                            ->where('socio_type_id', $targetType->id)
                            ->where('num_socio', $number)
                            ->first();

                        if (! $existing) {
                            return 'heroicon-m-check-circle';
                        }

                        if ($record?->imported_socio_id && (int) $record->imported_socio_id === (int) $existing->id) {
                            return 'heroicon-m-check-circle';
                        }

                        return 'heroicon-m-x-circle';
                    })
                    ->suffixIconColor(function (Get $get, ?WpApplication $record): ?string {
                        $value = $get('target_num_socio');
                        if ($value === null || $value === '') {
                            return null;
                        }

                        $number = (int) $value;
                        if ($number <= 0) {
                            return null;
                        }

                        $targetCode = strtoupper((string) ($get('target_socio_type_code') ?: ($record?->kind === 'escola' ? 'A' : 'B')));
                        $targetType = SocioType::query()->where('code', $targetCode)->first();
                        if (! $targetType) {
                            return null;
                        }

                        $existing = Socio::query()
                            ->where('socio_type_id', $targetType->id)
                            ->where('num_socio', $number)
                            ->first();

                        if (! $existing) {
                            return 'success';
                        }

                        if ($record?->imported_socio_id && (int) $record->imported_socio_id === (int) $existing->id) {
                            return 'success';
                        }

                        return 'danger';
                    })
                    ->helperText(function (Get $get, ?WpApplication $record): string {
                        $value = $get('target_num_socio');
                        if ($value === null || $value === '') {
                            return 'Se vazio, o sistema atribui automaticamente o proximo numero disponivel.';
                        }

                        $number = (int) $value;
                        if ($number <= 0) {
                            return 'Se vazio, o sistema atribui automaticamente o proximo numero disponivel.';
                        }

                        $targetCode = strtoupper((string) ($get('target_socio_type_code') ?: ($record?->kind === 'escola' ? 'A' : 'B')));
                        $targetType = SocioType::query()->where('code', $targetCode)->first();
                        if (! $targetType) {
                            return 'Tipo de socio alvo invalido.';
                        }

                        $existing = Socio::query()
                            ->where('socio_type_id', $targetType->id)
                            ->where('num_socio', $number)
                            ->first();

                        if (! $existing) {
                            return "Disponivel para o tipo {$targetCode}.";
                        }

                        if ($record?->imported_socio_id && (int) $record->imported_socio_id === (int) $existing->id) {
                            return "Ja associado a este pedido (Socio ID {$existing->id}).";
                        }

                        return "Ja existe para o tipo {$targetCode} (Socio ID {$existing->id}).";
                    })
                    ->rules([
                        fn (Get $get, ?WpApplication $record) => function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                            if ($value === null || $value === '') {
                                return;
                            }

                            $number = (int) $value;
                            if ($number <= 0) {
                                return;
                            }

                            $targetCode = strtoupper((string) ($get('target_socio_type_code') ?: ($record?->kind === 'escola' ? 'A' : 'B')));
                            $targetType = SocioType::query()->where('code', $targetCode)->first();
                            if (! $targetType) {
                                return;
                            }

                            $existing = Socio::query()
                                ->where('socio_type_id', $targetType->id)
                                ->where('num_socio', $number)
                                ->first();

                            if (! $existing) {
                                return;
                            }

                            if ($record?->imported_socio_id && (int) $record->imported_socio_id === (int) $existing->id) {
                                return;
                            }

                            $fail("O numero {$number} ja existe para o tipo {$targetCode} (Socio ID {$existing->id}).");
                        },
                    ])
                    ,
                Select::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'validada' => 'Validada',
                        'rejeitada' => 'Rejeitada',
                    ])
                    ->required(),
                DateTimePicker::make('resolved_at')
                    ->label('Resolvido em')
                    ->seconds(false),
                Textarea::make('resolution_notes')
                    ->label('Notas')
                    ->rows(3),
                Textarea::make('last_callback_response')
                    ->label('Ultimo callback')
                    ->rows(3)
                    ->disabled()
                    ->dehydrated(false),
            ])->columns(2),
        ])->columns(2);
    }

    protected static function payloadValue(?WpApplication $record, string $key, mixed $default = '—'): string
    {
        $value = Arr::get($record?->payload ?? [], $key, $default);

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Nao';
        }

        if ($value === null || $value === '') {
            return (string) $default;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->latest('id'))
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('display_name')
                    ->label('Nome')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('display_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('external_id')
                    ->label('ID WP')
                    ->sortable(),

                TextColumn::make('imported_socio_id')
                    ->label('Socio ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('target_socio_type_code')
                    ->label('Tipo alvo')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('target_num_socio')
                    ->label('Num alvo')
                    ->toggleable(),

                TextColumn::make('submitted_at')
                    ->label('Submetido')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),

                TextColumn::make('resolved_at')
                    ->label('Resolvido')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'validada' => 'Validada',
                        'rejeitada' => 'Rejeitada',
                    ]),
                Tables\Filters\SelectFilter::make('kind')
                    ->label('Tipo')
                    ->options([
                        'socio' => 'Socio',
                        'escola' => 'Escola',
                    ]),
            ])
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
            'index' => Pages\ListWpApplications::route('/'),
            'edit' => Pages\EditWpApplication::route('/{record}/edit'),
        ];
    }
}
