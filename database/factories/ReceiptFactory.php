<?php

namespace Database\Factories;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        $payment = PaymentFactory::new()->create();
        $quotaCharge = $payment->quotaCharge;
        $ano = (int) now()->format('Y');
        $sequencia = fake()->unique()->numberBetween(1, 9999);

        return [
            'numero' => sprintf('%d/%04d', $ano, $sequencia),
            'ano' => $ano,
            'sequencia' => $sequencia,
            'member_id' => $quotaCharge->socio_id,
            'quota_year_id' => $quotaCharge->quota_year_id,
            'payment_id' => $payment->id,
            'valor' => $payment->valor,
            'data_pagamento' => $payment->data_pagamento,
            'anulado_em' => null,
            'motivo_anulacao' => null,
        ];
    }
}
