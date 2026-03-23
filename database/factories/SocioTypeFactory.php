<?php

namespace Database\Factories;

use App\Models\SocioType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocioType>
 */
class SocioTypeFactory extends Factory
{
    protected $model = SocioType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(range('A', 'Z')),
            'nome' => fake()->unique()->words(2, true),
            'descricao' => fake()->optional()->sentence(),
            'ativo' => true,
        ];
    }

    public function benfeitor(): static
    {
        return $this->state(fn () => [
            'code' => 'B',
            'nome' => 'Benfeitor',
        ]);
    }
}
