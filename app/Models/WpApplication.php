<?php

namespace App\Models;

use App\Services\SocioNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WpApplication extends Model
{
    protected $fillable = [
        'source',
        'kind',
        'external_id',
        'payload_hash',
        'imported_socio_id',
        'target_socio_type_code',
        'target_num_socio',
        'status',
        'display_name',
        'display_email',
        'submitted_at',
        'payload',
        'resolution_notes',
        'resolved_at',
        'wp_status_callback_url',
        'last_callback_at',
        'last_callback_response',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_callback_at' => 'datetime',
        'target_num_socio' => 'integer',
        'payload' => 'array',
    ];

    public function importedSocio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'imported_socio_id');
    }

    public function tryAutoCreateSocioOnValidation(): void
    {
        if (! in_array($this->kind, ['socio', 'escola'], true) || $this->status !== 'validada') {
            return;
        }

        if ($this->imported_socio_id) {
            return;
        }

        $payload = (array) $this->payload;
        $nome = trim((string) $this->resolveNameFromPayload($payload));
        $nif = $this->resolveNifFromPayload($payload);

        if ($nome === '') {
            $this->appendResolutionNote('Auto-criacao de socio falhou: nome em falta no payload.');
            return;
        }

        $email = trim((string) $this->resolveEmailFromPayload($payload));
        $existing = null;

        if ($email !== '') {
            $existing = Socio::query()->where('email', $email)->first();
        }
        if (! $existing) {
            $existing = Socio::query()
                ->where('nome', $nome)
                ->where(function ($q) use ($email) {
                    if ($email !== '') {
                        $q->whereNull('email')->orWhere('email', $email);
                    } else {
                        $q->whereNull('email');
                    }
                })
                ->first();
        }
        if (! $existing && $nif !== null) {
            $existing = Socio::query()
                ->where('numero_fiscal', $nif)
                ->first();
        }

        if ($existing) {
            $this->imported_socio_id = $existing->id;
            $this->appendResolutionNote("Socio ja existente associado automaticamente (ID {$existing->id}).");
            $this->save();
            return;
        }

        $targetTypeCode = strtoupper((string) ($this->target_socio_type_code ?: $this->defaultTargetSocioTypeCode()));
        $tipo = SocioType::query()->where('code', $targetTypeCode)->first() ?? SocioType::query()->orderBy('id')->first();
        if (! $tipo) {
            $this->appendResolutionNote('Auto-criacao de socio falhou: nao existe socio_type configurado.');
            return;
        }

        if ($nif === null) {
            $this->appendResolutionNote('Auto-criacao de socio bloqueada: NIF em falta/invalidado no pedido WP.');
            $this->save();
            return;
        }

        $instrumento = trim((string) ($this->resolvePayloadValue($payload, ['instrumento'], '') ?? ''));
        $isInstrumentista = $instrumento !== '';
        $requestedNumber = (int) ($this->target_num_socio ?? 0);
        $resolvedTargetNumber = 0;

        try {
            $socio = DB::transaction(function () use (
                $tipo,
                $requestedNumber,
                $nome,
                $payload,
                $nif,
                $email,
                $isInstrumentista,
                $instrumento,
                &$resolvedTargetNumber
            ) {
                $targetNumber = $requestedNumber > 0 ? $requestedNumber : null;

                if ($targetNumber !== null) {
                    $numberInUse = Socio::query()
                        ->where('socio_type_id', $tipo->id)
                        ->where('num_socio', $targetNumber)
                        ->lockForUpdate()
                        ->exists();

                    if ($numberInUse) {
                        $targetNumber = null;
                    }
                }

                if ($targetNumber === null) {
                    $targetNumber = app(SocioNumberService::class)->getNextNumberForType($tipo->id);
                }

                $resolvedTargetNumber = $targetNumber;

                return Socio::query()->create([
                    'socio_type_id' => $tipo->id,
                    'num_socio' => $targetNumber,
                    'nome' => $nome,
                    'morada' => $this->resolvePayloadValue($payload, ['morada', 'aluno_morada']),
                    'codigo_postal' => $this->resolvePayloadValue($payload, ['codigo_postal', 'cod_postal', 'aluno_cod_postal']),
                    'localidade' => $this->resolvePayloadValue($payload, ['localidade', 'aluno_localidade']),
                    'telefone' => $this->resolvePayloadValue($payload, ['telefone', 'aluno_telefone']),
                    'telemovel' => $this->resolvePayloadValue($payload, ['telemovel', 'aluno_telemovel']),
                    'data_nascimento' => $this->resolvePayloadValue($payload, ['data_nascimento', 'data_nasc', 'aluno_data_nascimento', 'aluno_data_nasc']),
                    'numero_fiscal' => $nif,
                    'email' => $email !== '' ? $email : null,
                    'data_socio' => now()->toDateString(),
                    'estado' => 'ativo',
                    'is_instrumentista' => $isInstrumentista,
                    'instrumento' => $isInstrumentista ? $instrumento : null,
                    'instrumento_desde' => null,
                    'instrumento_ate' => null,
                ]);
            }, 3);
        } catch (QueryException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $errorCode = (string) ($e->errorInfo[1] ?? '');

            if ($sqlState === '23000' || $errorCode === '1062') {
                $this->appendResolutionNote('Auto-criacao de socio bloqueada: conflito de unicidade na numeracao.');
                $this->save();
                return;
            }

            $this->appendResolutionNote('Auto-criacao de socio falhou em BD: ' . mb_substr($e->getMessage(), 0, 220));
            $this->save();
            report($e);
            return;
        } catch (\Throwable $e) {
            $this->appendResolutionNote('Auto-criacao de socio falhou: ' . mb_substr($e->getMessage(), 0, 220));
            $this->save();
            report($e);
            return;
        }

        if ($requestedNumber > 0 && $requestedNumber !== $resolvedTargetNumber) {
            $this->appendResolutionNote("Numero pedido {$requestedNumber} indisponivel; atribuido automaticamente {$resolvedTargetNumber}.");
        }

        $this->imported_socio_id = $socio->id;
        $this->appendResolutionNote("Socio criado automaticamente (ID {$socio->id}, tipo {$tipo->code}, num {$socio->num_socio}).");
        $this->save();
    }

    private function defaultTargetSocioTypeCode(): string
    {
        return $this->kind === 'escola' ? 'A' : 'B';
    }

    private function resolveNameFromPayload(array $payload): string
    {
        return trim((string) $this->resolvePayloadValue($payload, ['nome', 'aluno_nome'], ''));
    }

    private function resolveEmailFromPayload(array $payload): string
    {
        return trim((string) $this->resolvePayloadValue($payload, ['email', 'enc_email', 'aluno_email'], ''));
    }

    private function resolvePayloadValue(array $payload, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return $value;
        }

        return $default;
    }

    private function resolveNifFromPayload(array $payload): ?string
    {
        $candidates = [
            $payload['numero_fiscal'] ?? null,
            $payload['nif'] ?? null,
            $payload['aluno_numero_fiscal'] ?? null,
            $payload['aluno_nif'] ?? null,
            $payload['cc_bi'] ?? null,
            $payload['aluno_cc_bi'] ?? null,
        ];

        foreach ($candidates as $raw) {
            if (! is_string($raw) && ! is_numeric($raw)) {
                continue;
            }

            $digits = preg_replace('/\D+/', '', (string) $raw);
            if (is_string($digits) && Str::length($digits) === 9) {
                return $digits;
            }
        }

        return null;
    }

    private function appendResolutionNote(string $line): void
    {
        $base = trim((string) ($this->resolution_notes ?? ''));
        $prefix = $base === '' ? '' : ($base . PHP_EOL);
        $this->resolution_notes = $prefix . '[' . now()->format('Y-m-d H:i:s') . '] ' . $line;
    }

    public function syncStatusBackToWordPress(): void
    {
        if (blank($this->wp_status_callback_url)) {
            return;
        }

        if (! $this->isAllowedCallbackUrl($this->wp_status_callback_url)) {
            Log::warning('WP callback blocked: host not allowed.', [
                'wp_application_id' => $this->id,
                'callback_url' => $this->wp_status_callback_url,
            ]);

            $this->forceFill([
                'last_callback_at' => now(),
                'last_callback_response' => 'ERROR Callback host is not allowed by WP_BRIDGE_ALLOWED_CALLBACK_HOSTS',
            ])->save();
            return;
        }

        $token = (string) config('services.wp_bridge.token');
        if ($token === '') {
            Log::warning('WP callback skipped: token not configured.', [
                'wp_application_id' => $this->id,
            ]);

            return;
        }

        try {
            Log::info('WP callback sending.', [
                'wp_application_id' => $this->id,
                'status' => $this->status,
                'callback_url' => $this->wp_status_callback_url,
            ]);

            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => (bool) config('services.wp_bridge.verify_ssl', true),
                ])
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-WP-Bridge-Token' => $token,
                ])
                ->post($this->wp_status_callback_url, [
                    'kind' => $this->kind,
                    'external_id' => $this->external_id,
                    'status' => $this->status,
                    'resolution_notes' => $this->resolution_notes,
                    'resolved_at' => optional($this->resolved_at)->toAtomString(),
                    'laravel_id' => $this->id,
                ]);

            $callbackResult = 'HTTP ' . $response->status() . ' ' . mb_substr((string) $response->body(), 0, 1000);

            Log::info('WP callback sent.', [
                'wp_application_id' => $this->id,
                'http_status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            $callbackResult = 'ERROR ' . class_basename($e) . ': ' . mb_substr($e->getMessage(), 0, 1000);

            Log::error('WP callback failed.', [
                'wp_application_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->forceFill([
            'last_callback_at' => now(),
            'last_callback_response' => $callbackResult,
        ])->save();
    }

    private function isAllowedCallbackUrl(string $url): bool
    {
        $allowedHosts = (array) config('services.wp_bridge.allowed_callback_hosts', []);
        if ($allowedHosts === []) {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        return in_array(mb_strtolower($host), array_map('mb_strtolower', $allowedHosts), true);
    }
}
