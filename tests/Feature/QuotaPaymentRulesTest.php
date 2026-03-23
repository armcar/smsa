<?php

namespace Tests\Feature;

use App\Models\QuotaCharge;
use App\Models\Payment;
use Database\Factories\QuotaChargeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotaPaymentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_sem_pagamentos_fica_pendente(): void
    {
        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
            'estado' => 'pago',
        ]);

        $quotaCharge->syncEstadoFromPayments();

        $this->assertSame('pendente', $quotaCharge->fresh()->estado);
        $this->assertSame(0.0, $quotaCharge->totalPago());
        $this->assertSame(100.0, $quotaCharge->valorEmDivida());
    }

    public function test_quota_com_pagamento_parcial_fica_parcial(): void
    {
        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
            'estado' => 'pendente',
        ]);

        Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 40,
        ]);

        $quotaCharge = $quotaCharge->fresh();

        $this->assertSame('parcial', $quotaCharge->estado);
        $this->assertSame(40.0, $quotaCharge->totalPago());
        $this->assertSame(60.0, $quotaCharge->valorEmDivida());
    }

    public function test_quota_com_pagamento_total_fica_pago(): void
    {
        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
            'estado' => 'pendente',
        ]);

        Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 100,
        ]);

        $quotaCharge = $quotaCharge->fresh();

        $this->assertSame('pago', $quotaCharge->estado);
        $this->assertSame(100.0, $quotaCharge->totalPago());
        $this->assertSame(0.0, $quotaCharge->valorEmDivida());
    }

    public function test_pagamento_anulado_deixa_de_contar_no_estado_da_quota(): void
    {
        $quotaCharge = QuotaChargeFactory::new()->create([
            'valor' => 100,
            'estado' => 'pendente',
        ]);

        $payment = Payment::query()->create([
            'quota_charge_id' => $quotaCharge->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => 100,
        ]);

        $this->assertSame('pago', $quotaCharge->fresh()->estado);

        $payment->update([
            'anulado_em' => now(),
        ]);

        $quotaCharge = $quotaCharge->fresh();

        $this->assertSame('pendente', $quotaCharge->estado);
        $this->assertSame(0.0, $quotaCharge->totalPago());
    }
}
