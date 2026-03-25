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
        $secret = $this->resolveSharedSecret();
        if ($secret === null) {
            return $this->unauthorized('Integração indisponível.');
        }

        $wpUserId = (int) $request->header('X-SMSA-WP-User-ID', 0);
        $timestamp = (int) $request->header('X-SMSA-Timestamp', 0);
        $signature = (string) $request->header('X-SMSA-Signature', '');

        if ($wpUserId <= 0 || $timestamp <= 0 || $signature === '') {
            return $this->unauthorized('Pedido inválido.');
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

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expectedSignature, $signature)) {
            return $this->unauthorized('Assinatura inválida.');
        }

        $socio = Socio::query()
            ->where('wp_user_id', $wpUserId)
            ->where('estado', 'ativo')
            ->first();

        if (! $socio) {
            return $this->unauthorized('Sócio não encontrado.');
        }

        $request->attributes->set('member_socio', $socio);
        $request->attributes->set('member_wp_user_id', $wpUserId);

        return $next($request);
    }

    private function resolveSharedSecret(): ?string
    {
        $secret = trim((string) config('wordpress.member_area_shared_secret', ''));
        if ($secret !== '') {
            return $secret;
        }

        $legacyBridgeToken = trim((string) config('services.wp_bridge.token', ''));
        return $legacyBridgeToken !== '' ? $legacyBridgeToken : null;
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
