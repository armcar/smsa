<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Models\Receipt;
use App\Models\Socio;
use Database\Factories\SocioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberAreaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('wordpress.member_area_shared_secret', 'member-area-secret');
        config()->set('wordpress.member_area_max_drift_seconds', 300);
    }

    public function test_rejeita_pedido_sem_assinatura_valida(): void
    {
        SocioFactory::new()->create([
            'wp_user_id' => 1001,
            'estado' => 'ativo',
        ]);

        $response = $this->getJson('/api/member-area/me', [
            'X-SMSA-WP-User-ID' => '1001',
        ]);

        $response->assertStatus(401);
    }

    public function test_devolve_apenas_dados_do_socio_autenticado(): void
    {
        $socioA = SocioFactory::new()->create([
            'wp_user_id' => 2001,
            'estado' => 'ativo',
        ]);

        $socioB = SocioFactory::new()->create([
            'wp_user_id' => 2002,
            'estado' => 'ativo',
        ]);

        $quotaYear = QuotaYear::query()->create([
            'ano' => (int) now()->year,
            'valor' => 24.00,
            'ativo' => true,
            'data_inicio' => now()->startOfYear()->toDateString(),
            'data_fim' => now()->endOfYear()->toDateString(),
            'nota' => null,
        ]);

        $quotaChargeA = QuotaCharge::query()->create([
            'socio_id' => $socioA->id,
            'quota_year_id' => $quotaYear->id,
            'socio_type_id' => $socioA->socio_type_id,
            'valor' => 24.00,
            'estado' => 'pendente',
            'emitido_em' => now()->toDateString(),
            'vencimento_em' => now()->addMonth()->toDateString(),
            'observacoes' => null,
        ]);

        $paymentA = Payment::query()->create([
            'quota_charge_id' => $quotaChargeA->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 24.00,
            'metodo' => 'mbway',
            'documento_tipo' => null,
            'documento_numero' => null,
            'referencia' => null,
            'notas' => null,
            'anulado_em' => null,
        ]);

        Receipt::query()->create([
            'numero' => now()->format('Y') . '/0001',
            'ano' => (int) now()->format('Y'),
            'sequencia' => 1,
            'member_id' => $socioA->id,
            'quota_year_id' => $quotaYear->id,
            'payment_id' => $paymentA->id,
            'valor' => 24.00,
            'data_pagamento' => now()->toDateString(),
            'anulado_em' => null,
            'motivo_anulacao' => null,
        ]);

        $quotaChargeB = QuotaCharge::query()->create([
            'socio_id' => $socioB->id,
            'quota_year_id' => $quotaYear->id,
            'socio_type_id' => $socioB->socio_type_id,
            'valor' => 40.00,
            'estado' => 'pendente',
            'emitido_em' => now()->toDateString(),
            'vencimento_em' => now()->addMonth()->toDateString(),
            'observacoes' => null,
        ]);

        Payment::query()->create([
            'quota_charge_id' => $quotaChargeB->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 40.00,
            'metodo' => 'dinheiro',
            'documento_tipo' => null,
            'documento_numero' => null,
            'referencia' => null,
            'notas' => null,
            'anulado_em' => null,
        ]);

        $headers = $this->signedHeaders('GET', '/api/member-area/me', 2001);
        $response = $this->getJson('/api/member-area/me', $headers);

        $response->assertOk()
            ->assertJsonPath('member.name', $socioA->nome)
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonCount(1, 'data.receipts')
            ->assertJsonPath('data.payments.0.amount', 24)
            ->assertJsonPath('data.payments.0.method', 'mbway')
            ->assertJsonPath('data.quota.status', 'pago');
    }

    public function test_recibo_download_nao_permite_troca_de_wp_user_id_no_link_assinado(): void
    {
        $socio = SocioFactory::new()->create([
            'wp_user_id' => 3001,
            'estado' => 'ativo',
        ]);

        $quotaYear = QuotaYear::query()->create([
            'ano' => (int) now()->year,
            'valor' => 30.00,
            'ativo' => true,
            'data_inicio' => now()->startOfYear()->toDateString(),
            'data_fim' => now()->endOfYear()->toDateString(),
            'nota' => null,
        ]);

        $quotaCharge = QuotaCharge::query()->create([
            'socio_id' => $socio->id,
            'quota_year_id' => $quotaYear->id,
            'socio_type_id' => $socio->socio_type_id,
            'valor' => 30.00,
            'estado' => 'pendente',
            'emitido_em' => now()->toDateString(),
            'vencimento_em' => now()->addMonth()->toDateString(),
            'observacoes' => null,
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 30.00,
            'metodo' => 'transferencia',
            'documento_tipo' => null,
            'documento_numero' => null,
            'referencia' => null,
            'notas' => null,
            'anulado_em' => null,
        ]);

        $receipt = Receipt::query()->create([
            'numero' => now()->format('Y') . '/0002',
            'ano' => (int) now()->format('Y'),
            'sequencia' => 2,
            'member_id' => $socio->id,
            'quota_year_id' => $quotaYear->id,
            'payment_id' => $payment->id,
            'valor' => 30.00,
            'data_pagamento' => now()->toDateString(),
            'anulado_em' => null,
            'motivo_anulacao' => null,
        ]);

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'member-area.receipts.download',
            now()->addMinutes(5),
            [
                'receipt' => $receipt->id,
                'wp_user_id' => 9999,
            ]
        );

        $response = $this->get($signedUrl);
        $response->assertStatus(403);
    }

    private function signedHeaders(string $method, string $path, int $wpUserId): array
    {
        $timestamp = time();
        $payload = implode("\n", [
            strtoupper($method),
            $path,
            (string) $timestamp,
            (string) $wpUserId,
        ]);

        $signature = hash_hmac('sha256', $payload, 'member-area-secret');

        return [
            'X-SMSA-WP-User-ID' => (string) $wpUserId,
            'X-SMSA-Timestamp' => (string) $timestamp,
            'X-SMSA-Signature' => $signature,
        ];
    }
}
