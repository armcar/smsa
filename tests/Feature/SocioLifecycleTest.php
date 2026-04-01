<?php

namespace Tests\Feature;

use App\Models\Receipt;
use Database\Factories\QuotaChargeFactory;
use Database\Factories\QuotaYearFactory;
use Database\Factories\SocioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SocioLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_socio_sem_movimentos_pode_ser_eliminado(): void
    {
        $socio = SocioFactory::new()->create();

        $socio->delete();

        $this->assertDatabaseMissing('socios', ['id' => $socio->id]);
    }

    public function test_socio_com_movimentos_nao_pode_ser_eliminado(): void
    {
        $socio = SocioFactory::new()->create();

        QuotaChargeFactory::new()->create([
            'socio_id' => $socio->id,
            'socio_type_id' => $socio->socio_type_id,
        ]);

        $this->expectException(ValidationException::class);

        $socio->delete();
    }

    public function test_socio_com_recibo_e_considerado_com_movimentos(): void
    {
        $socio = SocioFactory::new()->create();
        $quotaYear = QuotaYearFactory::new()->create();

        Receipt::query()->create([
            'numero' => '2026/0001',
            'ano' => 2026,
            'sequencia' => 1,
            'member_id' => $socio->id,
            'quota_year_id' => $quotaYear->id,
            'payment_id' => null,
            'valor' => 20,
            'data_pagamento' => '2026-01-20',
        ]);

        $this->assertTrue($socio->fresh()->hasMovimentos());
    }

    public function test_socio_com_movimentos_pode_ser_inativado_e_reativado(): void
    {
        $socio = SocioFactory::new()->create(['estado' => 'ativo']);

        QuotaChargeFactory::new()->create([
            'socio_id' => $socio->id,
            'socio_type_id' => $socio->socio_type_id,
        ]);

        $socio->inativar();
        $this->assertSame('suspenso', $socio->fresh()->estado);
        $this->assertTrue($socio->fresh()->hasMovimentos());

        $socio->fresh()->reativar();
        $this->assertSame('ativo', $socio->fresh()->estado);
    }
}
