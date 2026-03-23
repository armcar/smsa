<?php

namespace Tests\Feature;

use App\Models\QuotaCharge;
use Database\Factories\QuotaYearFactory;
use Database\Factories\SocioFactory;
use Database\Factories\SocioTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualQuotaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_cria_quotas_para_socios_elegiveis(): void
    {
        $typeB = SocioTypeFactory::new()->create(['code' => 'B', 'nome' => 'Benfeitor']);
        $typeA = SocioTypeFactory::new()->create(['code' => 'A', 'nome' => 'Aluno']);

        SocioFactory::new()->forType($typeB)->create(['estado' => 'ativo', 'num_socio' => 1]);
        SocioFactory::new()->forType($typeB)->create(['estado' => 'ativo', 'num_socio' => 2]);
        SocioFactory::new()->forType($typeB)->create(['estado' => 'suspenso', 'num_socio' => 3]);
        SocioFactory::new()->forType($typeA)->create(['estado' => 'ativo', 'num_socio' => 1]);

        $quotaYear = QuotaYearFactory::new()->create([
            'ano' => 2040,
            'valor' => 15,
        ]);

        $this->artisan('quotas:generate-annual', ['--year' => 2040])
            ->assertSuccessful();

        $this->assertSame(2, QuotaCharge::query()->where('quota_year_id', $quotaYear->id)->count());
    }

    public function test_segunda_execucao_nao_duplica_quotas_do_mesmo_ano(): void
    {
        $typeB = SocioTypeFactory::new()->create(['code' => 'B', 'nome' => 'Benfeitor']);
        SocioFactory::new()->forType($typeB)->create(['estado' => 'ativo', 'num_socio' => 1]);
        SocioFactory::new()->forType($typeB)->create(['estado' => 'ativo', 'num_socio' => 2]);

        $quotaYear = QuotaYearFactory::new()->create([
            'ano' => 2041,
            'valor' => 20,
        ]);

        $this->artisan('quotas:generate-annual', ['--year' => 2041])
            ->assertSuccessful();

        $countAfterFirst = QuotaCharge::query()->where('quota_year_id', $quotaYear->id)->count();

        $this->artisan('quotas:generate-annual', ['--year' => 2041])
            ->assertSuccessful();

        $countAfterSecond = QuotaCharge::query()->where('quota_year_id', $quotaYear->id)->count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }
}
