<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\PaymentCancellationService;
use App\Services\ReceiptService;
use Database\Factories\QuotaChargeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class PaymentCancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function mockPdfBinary(string $binary = 'pdf-binary'): void
    {
        $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('output')->andReturn($binary);

        Pdf::shouldReceive('loadView')
            ->andReturn($pdfMock);
    }

    public function test_anular_pagamento_anula_recibo_grava_motivo_e_recalcula_quota(): void
    {
        Mail::fake();
        $this->mockPdfBinary();

        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
            'estado' => 'pendente',
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 100,
        ]);

        $receipt = app(ReceiptService::class)->emitirEEnviar($payment, forceSendEmail: false);

        $this->assertSame('pago', $quotaCharge->fresh()->estado);

        app(PaymentCancellationService::class)->cancelar($payment, 'Teste de anulação');

        $payment = $payment->fresh();
        $receipt = $receipt->fresh();
        $quotaCharge = $quotaCharge->fresh();

        $this->assertNotNull($payment->anulado_em);
        $this->assertNotNull($receipt->anulado_em);
        $this->assertSame('Teste de anulação', $receipt->motivo_anulacao);
        $this->assertSame('pendente', $quotaCharge->estado);
    }
}