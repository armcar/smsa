<?php

namespace Database\Factories;

use App\Models\Socio;
use App\Models\SocioType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Socio>
 */
class SocioFactory extends Factory
{
    protected $model = Socio::class;

    public function definition(): array
    {
        return [
            'socio_type_id' => SocioTypeFactory::new()->create()->id,
            'num_socio' => fake()->unique()->numberBetween(1, 999999),
            'nome' => fake()->name(),
            'morada' => fake()->streetAddress(),
            'codigo_postal' => fake()->numerify('####-###'),
            'localidade' => fake()->city(),
            'telefone' => fake()->numerify('2########'),
            'telemovel' => fake()->numerify('9########'),
            'data_nascimento' => fake()->date('Y-m-d', '-18 years'),
            'numero_fiscal' => fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'data_socio' => now()->toDateString(),
            'estado' => 'ativo',
            'is_instrumentista' => false,
            'instrumento' => null,
            'instrumento_desde' => null,
            'instrumento_ate' => null,
        ];
    }

    public function forType(SocioType $type): static
    {
        return $this->state(fn () => [
            'socio_type_id' => $type->id,
        ]);
    }
}
