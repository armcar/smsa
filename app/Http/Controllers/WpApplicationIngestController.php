<?php

namespace App\Http\Controllers;

use App\Models\WpApplication;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WpApplicationIngestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.wp_bridge.token');
        $receivedToken = (string) $request->header('X-WP-Bridge-Token', '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            Log::warning('WP ingest rejected: invalid integration token.', [
                'ip' => $request->ip(),
                'external_id' => (string) $request->input('external_id', ''),
            ]);

            return response()->json(['message' => 'Unauthorized integration token.'], 401);
        }

        $data = $request->validate([
            'kind' => ['required', Rule::in(['socio', 'escola'])],
            'external_id' => ['required', 'string', 'max:64'],
            'submitted_at' => ['nullable', 'date'],
            'payload' => ['required', 'array'],
            'wp_status_callback_url' => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $allowedHosts = (array) config('services.wp_bridge.allowed_callback_hosts', []);
                    if ($allowedHosts === []) {
                        return;
                    }

                    $host = parse_url($value, PHP_URL_HOST);
                    if (! is_string($host) || ! in_array(mb_strtolower($host), array_map('mb_strtolower', $allowedHosts), true)) {
                        $fail('Callback URL host is not allowed.');
                    }
                },
            ],
        ]);

        Log::info('WP ingest received.', [
            'kind' => $data['kind'],
            'external_id' => $data['external_id'],
            'ip' => $request->ip(),
        ]);

        $displayName = $this->extractDisplayName($data['kind'], $data['payload']);
        $displayEmail = $this->extractDisplayEmail($data['kind'], $data['payload']);
        $payloadHash = $this->buildPayloadHash($data['kind'], $data['payload']);

        $application = WpApplication::query()
            ->where('source', 'wordpress')
            ->where('kind', $data['kind'])
            ->where('external_id', $data['external_id'])
            ->first();

        $deduplicatedByPayload = false;
        $deduplicatedByMember = false;

        if (! $application) {
            $application = $this->findExistingMemberApplication($data['kind'], $data['payload']);
            $deduplicatedByMember = $application !== null;
        }

        if (! $application) {
            $application = WpApplication::query()
                ->where('source', 'wordpress')
                ->where('kind', $data['kind'])
                ->where('payload_hash', $payloadHash)
                ->where('status', 'pendente')
                ->latest('id')
                ->first();

            $deduplicatedByPayload = $application !== null;
        }

        $attributes = [
            'submitted_at' => $data['submitted_at'] ?? now(),
            'payload' => $data['payload'],
            'payload_hash' => $payloadHash,
            'display_name' => $displayName,
            'display_email' => $displayEmail,
            'wp_status_callback_url' => $data['wp_status_callback_url'] ?? null,
        ];
        $isMemberUpdate = $this->isMemberUpdateSubmission($data['kind'], $data['payload']);
        if ($isMemberUpdate) {
            $attributes['status'] = 'pendente';
            $attributes['resolved_at'] = null;
        }

        $created = false;

        try {
            DB::transaction(function () use (
                &$application,
                &$created,
                $attributes,
                $data,
                $isMemberUpdate
            ): void {
                if ($application) {
                    $application->fill($attributes + [
                        'source' => 'wordpress',
                        'kind' => $data['kind'],
                        'external_id' => $data['external_id'],
                    ])->save();
                } else {
                    $application = WpApplication::query()->create($attributes + [
                        'source' => 'wordpress',
                        'kind' => $data['kind'],
                        'external_id' => $data['external_id'],
                    ]);

                    $created = true;
                }

                if ($isMemberUpdate) {
                    $this->applyMemberUpdateToSocio($application, $data['payload']);
                }
            });
        } catch (\Throwable $e) {
            Log::error('WP ingest member update failed.', [
                'kind' => $data['kind'],
                'external_id' => $data['external_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Falha ao aplicar atualização de dados do sócio.',
            ], 422);
        }

        $application->refresh();

        if ($deduplicatedByPayload) {
            Log::info('WP ingest deduplicated by payload hash.', [
                'application_id' => $application->id,
                'kind' => $application->kind,
                'external_id' => $application->external_id,
                'payload_hash' => $payloadHash,
            ]);
        }

        if (! $created) {
            Log::info('WP ingest duplicate submission handled idempotently.', [
                'application_id' => $application->id,
                'kind' => $application->kind,
                'external_id' => $application->external_id,
                'strategy' => $deduplicatedByMember
                    ? 'member_socio'
                    : ($deduplicatedByPayload ? 'payload_hash' : 'external_id'),
            ]);
        }

        $statusCode = $created ? 201 : 200;

        return response()->json([
            'ok' => true,
            'id' => $application->id,
            'status' => $application->status,
            'deduplicated' => $deduplicatedByPayload,
            'deduplicated_by_member' => $deduplicatedByMember,
            'member_update' => $isMemberUpdate,
        ], $statusCode);
    }

    private function isMemberUpdateSubmission(string $kind, array $payload): bool
    {
        if ($kind !== 'socio') {
            return false;
        }

        $mode = mb_strtolower(trim((string) ($payload['wp_submission_mode'] ?? '')));
        if ($mode !== 'update') {
            return false;
        }

        return (int) ($payload['wp_member_user_id'] ?? 0) > 0;
    }

    private function applyMemberUpdateToSocio(WpApplication $application, array $payload): void
    {
        $wpUserId = (int) ($payload['wp_member_user_id'] ?? 0);
        if ($wpUserId <= 0) {
            return;
        }

        $socio = Socio::query()
            ->where('wp_user_id', $wpUserId)
            ->first();

        if (! $socio) {
            throw new \RuntimeException('Sócio não encontrado para atualização imediata.');
        }

        $numeroFiscal = $this->resolveNifFromPayload($payload);
        if ($numeroFiscal === null) {
            throw new \RuntimeException('NIF inválido na atualização de dados.');
        }

        $birthDate = $this->resolvePayloadValue($payload, ['data_nascimento', 'data_nasc']);
        if (is_string($birthDate)) {
            $birthDate = trim($birthDate);
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birthDate) === 1) {
                [$d, $m, $y] = explode('/', $birthDate);
                $birthDate = sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
            }
        }

        $socio->fill([
            'nome' => trim((string) ($payload['nome'] ?? $socio->nome)),
            'morada' => $this->resolvePayloadValue($payload, ['morada'], $socio->morada),
            'codigo_postal' => $this->resolvePayloadValue($payload, ['codigo_postal', 'cod_postal'], $socio->codigo_postal),
            'localidade' => $this->resolvePayloadValue($payload, ['localidade'], $socio->localidade),
            'telefone' => $this->resolvePayloadValue($payload, ['telefone'], $socio->telefone),
            'telemovel' => $this->resolvePayloadValue($payload, ['telemovel'], $socio->telemovel),
            'data_nascimento' => $birthDate ?: $socio->data_nascimento,
            'numero_fiscal' => $numeroFiscal,
            'email' => trim((string) ($payload['email'] ?? $socio->email)),
            'estado' => 'ativo',
        ])->save();

        $application->imported_socio_id = $socio->id;
        $application->resolution_notes = $this->buildMemberUpdateNote($payload);
        $application->save();
    }

    private function buildMemberUpdateNote(array $payload): string
    {
        $when = now()->format('d/m/Y H:i');
        $who = trim((string) ($payload['wp_member_login'] ?? 'utilizador'));

        return "Atualizacao de dados efetuada em {$when} pelo utilizador {$who}";
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
        ];

        foreach ($candidates as $raw) {
            if (! is_string($raw) && ! is_numeric($raw)) {
                continue;
            }

            $digits = preg_replace('/\D+/', '', (string) $raw);
            if (is_string($digits) && strlen($digits) === 9) {
                return $digits;
            }
        }

        return null;
    }

    private function findExistingMemberApplication(string $kind, array $payload): ?WpApplication
    {
        if ($kind !== 'socio') {
            return null;
        }

        $wpUserId = (int) ($payload['wp_member_user_id'] ?? 0);
        if ($wpUserId <= 0) {
            return null;
        }

        $socio = Socio::query()
            ->where('wp_user_id', $wpUserId)
            ->first();

        if (! $socio) {
            return null;
        }

        return WpApplication::query()
            ->where('source', 'wordpress')
            ->where('kind', 'socio')
            ->where('imported_socio_id', $socio->id)
            ->latest('id')
            ->first();
    }

    private function extractDisplayName(string $kind, array $payload): ?string
    {
        if ($kind === 'socio') {
            return $payload['nome'] ?? null;
        }

        return $payload['aluno_nome'] ?? null;
    }

    private function extractDisplayEmail(string $kind, array $payload): ?string
    {
        if ($kind === 'socio') {
            return $payload['email'] ?? null;
        }

        return $payload['enc_email'] ?? ($payload['aluno_email'] ?? null);
    }

    private function buildPayloadHash(string $kind, array $payload): string
    {
        return hash('sha256', $kind . '|' . json_encode(
            $this->normalizeForHash($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    private function normalizeForHash(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->normalizeForHash($value);
                continue;
            }

            if (is_string($value)) {
                $payload[$key] = trim($value);
            }
        }

        return $payload;
    }
}
