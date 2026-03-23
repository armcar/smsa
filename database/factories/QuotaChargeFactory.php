<?php

namespace Database\Factories;

use App\Models\QuotaCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotaCharge>
 */
class QuotaChargeFactory extends Factory
{
    protected $model = QuotaCharge::class;

    public function definition(): array
    {
        $socio = SocioFactory::new()->create();
        $quotaYear = QuotaYearFactory::new()->create();

        return [
            'socio_id' => $socio->id,
            'quota_year_id' => $quotaYear->id,
            'socio_type_id' => $socio->socio_type_id,
            'valor' => fake()->randomFloat(2, 5, 100),
            'estado' => 'pendente',
            'emitido_em' => now()->toDateString(),
            'vencimento_em' => now()->addMonth()->toDateString(),
            'observacoes' => null,
        ];
    }
}
