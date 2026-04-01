<?php

namespace App\Http\Middleware;

use App\Models\Socio;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWordPressMemberAreaRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $secrets = $this->resolveSharedSecrets();
        if ($secrets === []) {
            return $this->unauthorized('Integracao indisponivel.');
        }

        $wpUserId = (int) $request->header('X-SMSA-WP-User-ID', 0);
        $timestamp = (int) $request->header('X-SMSA-Timestamp', 0);
        $signature = (string) $request->header('X-SMSA-Signature', '');

        if ($wpUserId <= 0 || $timestamp <= 0 || $signature === '') {
            return $this->unauthorized('Pedido invalido.');
        }

        $maxDrift = (int) config('wordpress.member_area_max_drift_seconds', 300);
        if (abs(time() - $timestamp) > $maxDrift) {
            return $this->unauthorized('Pedido expirado.');
        }

        $payload = implode("\n", [
            strtoupper($request->getMethod()),
            (string) $request->getPathInfo(),
            (string) $timestamp,
            (string) $wpUserId,
        ]);

        if (! $this->hasValidSignature($payload, $signature, $secrets)) {
            return $this->unauthorized('Assinatura invalida.');
        }

        $socio = Socio::query()
            ->where('wp_user_id', $wpUserId)
            ->where('estado', 'ativo')
            ->first();

        if (! $socio) {
            return $this->unauthorized('Socio nao encontrado.');
        }

        $request->attributes->set('member_socio', $socio);
        $request->attributes->set('member_wp_user_id', $wpUserId);

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function resolveSharedSecrets(): array
    {
        $primary = trim((string) config('wordpress.member_area_shared_secret', ''));
        $previous = array_map('trim', (array) config('wordpress.member_area_previous_shared_secrets', []));
        $legacyBridgeToken = trim((string) config('services.wp_bridge.token', ''));

        return array_values(array_unique(array_filter([
            $primary,
            ...$previous,
            $legacyBridgeToken,
        ], static fn (string $secret): bool => $secret !== '')));
    }

    /**
     * @param array<int, string> $secrets
     */
    private function hasValidSignature(string $payload, string $signature, array $secrets): bool
    {
        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $payload, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}

