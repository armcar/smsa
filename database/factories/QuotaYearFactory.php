<?php

namespace Database\Factories;

use App\Models\QuotaYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotaYear>
 */
class QuotaYearFactory extends Factory
{
    protected $model = QuotaYear::class;

    public function definition(): array
    {
        $ano = (int) fake()->unique()->numberBetween(2020, 2099);

        return [
            'ano' => $ano,
            'valor' => fake()->randomFloat(2, 5, 100),
            'ativo' => false,
            'data_inicio' => sprintf('%d-01-01', $ano),
            'data_fim' => sprintf('%d-12-31', $ano),
            'nota' => fake()->optional()->sentence(),
        ];
    }
}
