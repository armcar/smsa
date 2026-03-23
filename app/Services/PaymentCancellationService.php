<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class PaymentCancellationService
{
    public function cancelar(Payment $payment, ?string $motivoRecibo = null): Payment
    {
        $motivo = trim((string) $motivoRecibo);
        if ($motivo === '') {
            $motivo = 'Recibo anulado por anulação do pagamento associado.';
        }

        return DB::transaction(function () use ($payment, $motivo): Payment {
            $payment->refresh();

            if ($payment->anulado_em === null) {
                $payment->anulado_em = now();
                $payment->save();
            }

            $receipt = Receipt::query()
                ->where('payment_id', $payment->id)
                ->first();

            if ($receipt && $receipt->anulado_em === null) {
                $receipt->forceFill([
                    'anulado_em' => now(),
                    'motivo_anulacao' => $motivo,
                ])->save();
            }

            $payment->load('quotaCharge');

            return $payment;
        }, 3);
    }
}
