<?php

namespace Tests\Feature;

use App\Models\Socio;
use App\Models\WpApplication;
use Database\Factories\SocioFactory;
use Database\Factories\SocioTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocioNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_novo_socio_auto_criado_nao_usa_placeholder_999(): void
    {
        $typeB = SocioTypeFactory::new()->create(['code' => 'B', 'nome' => 'Benfeitor']);

        SocioFactory::new()->forType($typeB)->create(['num_socio' => 1]);
        SocioFactory::new()->forType($typeB)->create(['num_socio' => 2]);

        $app = WpApplication::query()->create([
            'source' => 'wordpress',
            'kind' => 'socio',
            'external_id' => 'num-1',
            'status' => 'validada',
            'submitted_at' => now(),
            'payload' => [
                'nome' => 'Novo Socio Sequencial',
                'email' => 'novo.sequencial@example.org',
                'numero_fiscal' => '123456789',
                'data_nascimento' => '1990-01-01',
            ],
            'target_socio_type_code' => 'B',
            'target_num_socio' => null,
        ]);

        $app->tryAutoCreateSocioOnValidation();
        $app->refresh();

        $socio = $app->importedSocio;

        $this->assertNotNull($socio);
        $this->assertNotSame(999, (int) $socio->num_socio);
        $this->assertSame(3, (int) $socio->num_socio);
    }

    public function test_numeracao_segura_faz_fallback_para_proximo_numero_disponivel(): void
    {
        $typeB = SocioTypeFactory::new()->create(['code' => 'B', 'nome' => 'Benfeitor']);

        SocioFactory::new()->forType($typeB)->create(['num_socio' => 10]);

        $app = WpApplication::query()->create([
            'source' => 'wordpress',
            'kind' => 'socio',
            'external_id' => 'num-2',
            'status' => 'validada',
            'submitted_at' => now(),
            'payload' => [
                'nome' => 'Novo Socio Fallback',
                'email' => 'novo.fallback@example.org',
                'numero_fiscal' => '987654321',
                'data_nascimento' => '1991-02-02',
            ],
            'target_socio_type_code' => 'B',
            'target_num_socio' => 10,
        ]);

        $app->tryAutoCreateSocioOnValidation();
        $app->refresh();

        $socio = $app->importedSocio;

        $this->assertNotNull($socio);
        $this->assertSame(11, (int) $socio->num_socio);

        $this->assertSame(1, Socio::query()->where('socio_type_id', $typeB->id)->where('num_socio', 10)->count());
        $this->assertSame(1, Socio::query()->where('socio_type_id', $typeB->id)->where('num_socio', 11)->count());
    }
}
