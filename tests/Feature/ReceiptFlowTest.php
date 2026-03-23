<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Receipt;
use App\Services\PaymentCancellationService;
use App\Services\ReceiptService;
use Database\Factories\QuotaChargeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class ReceiptFlowTest extends TestCase
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

    public function test_emitir_recibo_cria_ligacao_com_payment_id(): void
    {
        Mail::fake();
        $this->mockPdfBinary();

        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => '2026-05-20',
            'valor' => 25,
        ]);

        $receipt = app(ReceiptService::class)->emitirEEnviar($payment, forceSendEmail: false);

        $this->assertNotNull($receipt->id);
        $this->assertSame($payment->id, $receipt->payment_id);
    }

    public function test_reemitir_nao_duplica_recibo(): void
    {
        Mail::fake();
        $this->mockPdfBinary();

        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => '2026-05-20',
            'valor' => 25,
        ]);

        $service = app(ReceiptService::class);

        $first = $service->emitirEEnviar($payment, forceSendEmail: false);
        $second = $service->emitirEEnviar($payment, forceSendEmail: false);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Receipt::query()->where('payment_id', $payment->id)->count());
    }

    public function test_recibo_usa_valor_do_pagamento_e_nao_total_da_quota(): void
    {
        Mail::fake();
        $this->mockPdfBinary();

        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => '2026-05-20',
            'valor' => 30,
        ]);

        $receipt = app(ReceiptService::class)->emitirEEnviar($payment, forceSendEmail: false);

        $this->assertSame(30.0, (float) $receipt->valor);
        $this->assertNotSame((float) $quotaCharge->valor, (float) $receipt->valor);
    }

    public function test_pdf_e_email_mostram_ano_da_quota_e_data_emissao_corretos(): void
    {
        $quotaCharge = QuotaChargeFactory::new()->create();
        $quotaYear = $quotaCharge->quotaYear;
        $quotaYear->update(['ano' => 2030]);

        $receipt = Receipt::query()->create([
            'numero' => '2040/0001',
            'ano' => 2040,
            'sequencia' => 1,
            'member_id' => $quotaCharge->socio_id,
            'quota_year_id' => $quotaYear->id,
            'payment_id' => null,
            'valor' => 10,
            'data_pagamento' => '2026-05-20',
        ]);

        $receipt->load(['member.socioType', 'quotaYear']);

        $pdfHtml = view('pdf.receipt', ['receipt' => $receipt])->render();
        $emailHtml = view('emails.receipt_paid', ['receipt' => $receipt])->render();

        $this->assertStringContainsString('Quota do ano 2030', $pdfHtml);
        $this->assertStringContainsString('20-05-2026', $pdfHtml);

        $this->assertStringContainsString('quota do ano <strong>2030</strong>', $emailHtml);
        $this->assertStringContainsString('emissão 2040', $emailHtml);
    }

    public function test_recibo_anulado_fica_com_anulado_em_preenchido(): void
    {
        Mail::fake();
        $this->mockPdfBinary();

        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 100,
        ]);

        $receipt = app(ReceiptService::class)->emitirEEnviar($payment, forceSendEmail: false);

        app(PaymentCancellationService::class)->cancelar($payment);

        $this->assertNotNull($receipt->fresh()->anulado_em);
    }
}