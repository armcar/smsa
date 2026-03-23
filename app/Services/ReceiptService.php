<?php

namespace App\Services;

use App\Mail\ReceiptPaidMail;
use App\Models\Payment;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReceiptService
{
    /**
     * - 1 recibo por pagamento (payment_id)
     * - forceSendEmail=true reenvia email mesmo se o recibo já existir
     */
    public function emitirEEnviar(
        Payment $payment,
        ?string $emailDestino = null,
        bool $forceSendEmail = false
    ): Receipt {
        $payment->loadMissing(['quotaCharge.socio', 'quotaCharge.quotaYear']);
        $quotaCharge = $payment->quotaCharge;
        $socio = $quotaCharge?->socio;
        $quotaYear = $quotaCharge?->quotaYear;

        if ($payment->anulado_em !== null) {
            throw new \RuntimeException('Não é possível emitir/reenviar recibo para pagamento anulado.');
        }

        if (! $quotaCharge || ! $socio || ! $quotaYear) {
            throw new \RuntimeException('Pagamento sem quota/sócio/ano associado.');
        }

        $existente = Receipt::query()
            ->where('payment_id', $payment->id)
            ->first();

        if ($existente) {
            if ($existente->anulado_em !== null) {
                throw new \RuntimeException('O recibo deste pagamento encontra-se anulado.');
            }

            $existente->load(['member', 'quotaYear', 'payment.quotaCharge']);

            if ($forceSendEmail) {
                $this->enviarEmail($existente, $emailDestino);
            }

            return $existente;
        }

        // Compatibilidade: recibos antigos não têm payment_id e podem já existir por sócio+ano.
        $legacy = Receipt::query()
            ->whereNull('payment_id')
            ->where('member_id', $socio->id)
            ->where('quota_year_id', $quotaYear->id)
            ->first();

        if ($legacy) {
            $legacy->payment_id = $payment->id;
            $legacy->valor = (string) $payment->valor;
            $legacy->data_pagamento = $payment->data_pagamento;
            $legacy->save();

            $legacy->load(['member', 'quotaYear', 'payment.quotaCharge']);

            if ($forceSendEmail) {
                $this->enviarEmail($legacy, $emailDestino);
            }

            return $legacy;
        }

        $anoEmissao = (int) now()->format('Y');
        $dataPagamentoStr = $payment->data_pagamento?->toDateString() ?? now()->toDateString();

        $receipt = DB::transaction(function () use ($payment, $socio, $quotaYear, $anoEmissao, $dataPagamentoStr) {
            $dados = Receipt::gerarNumeroSeguro($anoEmissao);

            return Receipt::create([
                'numero' => $dados['numero'],
                'ano' => $anoEmissao,
                'sequencia' => $dados['sequencia'],
                'member_id' => $socio->id,
                'quota_year_id' => $quotaYear->id,
                'payment_id' => $payment->id,
                'valor' => (string) $payment->valor,
                'data_pagamento' => $dataPagamentoStr,
            ]);
        }, 3);

        $receipt->load(['member', 'quotaYear', 'payment.quotaCharge']);

        $this->enviarEmail($receipt, $emailDestino);

        return $receipt;
    }

    private function enviarEmail(Receipt $receipt, ?string $emailDestino = null): void
    {
        $pdfBinary = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
        ])
            ->setPaper('a4')
            ->output();

        $to = $emailDestino ?: ($receipt->member->email ?? null);

        if ($to) {
            Mail::to($to)->send(new ReceiptPaidMail($receipt, $pdfBinary));
        }
    }
}
