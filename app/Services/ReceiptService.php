<?php

namespace App\Services;

use App\Mail\ReceiptPaidMail;
use App\Models\QuotaYear;
use App\Models\Receipt;
use App\Models\Socio;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReceiptService
{
    /**
     * - 1 recibo por (sócio + quotaYear)
     * - data do recibo = dataPagamento (do Payment)
     * - forceSendEmail=true reenvia email mesmo se o recibo já existir
     */
    public function emitirEEnviar(
        Socio $socio,
        QuotaYear $quotaYear,
        ?string $emailDestino = null,
        ?CarbonInterface $dataPagamento = null,
        bool $forceSendEmail = false
    ): Receipt {

        $existente = Receipt::where('member_id', $socio->id)
            ->where('quota_year_id', $quotaYear->id)
            ->first();

        if ($existente) {
            $existente->load(['member', 'quotaYear']);

            if ($forceSendEmail) {
                $this->enviarEmail($existente, $emailDestino);
            }

            return $existente;
        }

        $anoEmissao = (int) now()->format('Y');
        $dataPagamentoStr = ($dataPagamento ?: now())->toDateString();

        $receipt = DB::transaction(function () use ($socio, $quotaYear, $anoEmissao, $dataPagamentoStr) {
            $dados = Receipt::gerarNumeroSeguro($anoEmissao);

            return Receipt::create([
                'numero' => $dados['numero'],
                'ano' => $anoEmissao,
                'sequencia' => $dados['sequencia'],
                'member_id' => $socio->id,
                'quota_year_id' => $quotaYear->id,
                'valor' => (string) $quotaYear->valor,
                'data_pagamento' => $dataPagamentoStr,
            ]);
        }, 3);

        $receipt->load(['member', 'quotaYear']);

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
