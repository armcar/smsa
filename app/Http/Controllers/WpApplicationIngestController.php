<?php

namespace App\Http\Controllers;

use App\Models\WpApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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

        $created = false;

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
                'strategy' => $deduplicatedByPayload ? 'payload_hash' : 'external_id',
            ]);
        }

        $statusCode = $created ? 201 : 200;

        return response()->json([
            'ok' => true,
            'id' => $application->id,
            'status' => $application->status,
            'deduplicated' => $deduplicatedByPayload,
        ], $statusCode);
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
