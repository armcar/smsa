<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Models\Receipt;
use App\Models\Socio;
use Illuminate\Support\Facades\URL;

class MemberAreaSnapshotService
{
    public function buildForSocio(Socio $socio): array
    {
        return [
            'quota' => $this->buildQuotaState($socio),
            'payments' => $this->buildPayments($socio),
            'receipts' => $this->buildReceipts($socio),
        ];
    }

    private function buildQuotaState(Socio $socio): array
    {
        $currentYear = (int) now()->year;
        $quotaYear = QuotaYear::query()
            ->where('ano', $currentYear)
            ->first();

        if (! $quotaYear) {
            return [
                'year' => $currentYear,
                'amount' => null,
                'status' => 'sem_definicao',
                'amount_due' => null,
                'message' => 'Ainda não existem quotas definidas para o ano corrente.',
            ];
        }

        $quotaCharge = QuotaCharge::query()
            ->where('socio_id', $socio->id)
            ->where('quota_year_id', $quotaYear->id)
            ->latest('id')
            ->first();

        if (! $quotaCharge) {
            return [
                'year' => (int) $quotaYear->ano,
                'amount' => round((float) $quotaYear->valor, 2),
                'status' => 'em_divida',
                'amount_due' => round((float) $quotaYear->valor, 2),
                'message' => 'Sem pagamento registado para a quota deste ano.',
            ];
        }

        $derivedStatus = $quotaCharge->estadoDerivado();
        $isPaid = $derivedStatus === 'pago';

        return [
            'year' => (int) $quotaYear->ano,
            'amount' => round((float) $quotaCharge->valor, 2),
            'status' => $isPaid ? 'pago' : 'em_divida',
            'amount_due' => $isPaid ? 0.0 : round($quotaCharge->valorEmDivida(), 2),
            'message' => null,
        ];
    }

    private function buildPayments(Socio $socio): array
    {
        return Payment::query()
            ->whereNull('anulado_em')
            ->whereHas('quotaCharge', fn ($query) => $query->where('socio_id', $socio->id))
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Payment $payment): array => [
                'date' => optional($payment->data_pagamento)->format('Y-m-d'),
                'amount' => round((float) $payment->valor, 2),
                'method' => (string) ($payment->metodo ?? ''),
                'method_label' => $this->paymentMethodLabel((string) ($payment->metodo ?? '')),
            ])
            ->all();
    }

    private function buildReceipts(Socio $socio): array
    {
        return Receipt::query()
            ->ativos()
            ->where('member_id', $socio->id)
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Receipt $receipt): array => [
                'number' => (string) $receipt->numero,
                'date' => optional($receipt->data_pagamento)->format('Y-m-d'),
                'amount' => round((float) $receipt->valor, 2),
                'download_url' => URL::temporarySignedRoute(
                    'member-area.receipts.download',
                    now()->addMinutes(20),
                    [
                        'receipt' => $receipt->id,
                        'wp_user_id' => (int) $socio->wp_user_id,
                    ]
                ),
            ])
            ->all();
    }

    private function paymentMethodLabel(string $method): string
    {
        return match (mb_strtolower(trim($method))) {
            'mbway' => 'MBWay',
            'transferencia', 'transferência' => 'Transferência',
            'dinheiro' => 'Dinheiro',
            default => trim($method) !== '' ? ucfirst($method) : 'Não definido',
        };
    }
}
