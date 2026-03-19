<?php

namespace App\Http\Controllers;

use App\Models\WpApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WpApplicationIngestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.wp_bridge.token');
        $receivedToken = (string) $request->header('X-WP-Bridge-Token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['message' => 'Unauthorized integration token.'], 401);
        }

        $data = $request->validate([
            'kind' => ['required', Rule::in(['socio', 'escola'])],
            'external_id' => ['required', 'string', 'max:64'],
            'submitted_at' => ['nullable', 'date'],
            'payload' => ['required', 'array'],
            'wp_status_callback_url' => ['nullable', 'url', 'max:500'],
        ]);

        $displayName = $this->extractDisplayName($data['kind'], $data['payload']);
        $displayEmail = $this->extractDisplayEmail($data['kind'], $data['payload']);

        $application = WpApplication::query()->updateOrCreate(
            [
                'source' => 'wordpress',
                'kind' => $data['kind'],
                'external_id' => $data['external_id'],
            ],
            [
                'submitted_at' => $data['submitted_at'] ?? now(),
                'payload' => $data['payload'],
                'display_name' => $displayName,
                'display_email' => $displayEmail,
                'wp_status_callback_url' => $data['wp_status_callback_url'] ?? null,
            ]
        );
        $application->refresh();

        return response()->json([
            'ok' => true,
            'id' => $application->id,
            'status' => $application->status,
        ], 201);
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
}
