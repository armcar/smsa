<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'quota_charge_id' => QuotaChargeFactory::new()->create()->id,
            'data_pagamento' => now()->toDateString(),
            'valor' => fake()->randomFloat(2, 1, 100),
            'metodo' => fake()->randomElement(['dinheiro', 'transferencia', 'mbway']),
            'documento_tipo' => null,
            'documento_numero' => null,
            'referencia' => null,
            'notas' => null,
            'anulado_em' => null,
        ];
    }
}
