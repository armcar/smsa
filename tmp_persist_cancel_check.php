<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Receipt;
use App\Services\PaymentCancellationService;

function out($k, $v) { echo $k . '=' . $v . PHP_EOL; }

$payment = Payment::query()
    ->whereNull('anulado_em')
    ->whereHas('receipt', fn ($q) => $q->whereNull('anulado_em'))
    ->with(['quotaCharge', 'receipt'])
    ->latest('id')
    ->first();

if (! $payment) {
    out('RESULT', 'NO_ELIGIBLE_PAYMENT_FOUND');
    exit(0);
}

$receipt = $payment->receipt;
$motivo = 'Validação persistente controlada (Sprint 3).';

out('TARGET_PAYMENT_ID', $payment->id);
out('TARGET_RECEIPT_ID', $receipt?->id ?? 'NONE');
out('BEFORE_PAYMENT_ANULADO', $payment->anulado_em ? 'YES' : 'NO');
out('BEFORE_RECEIPT_ANULADO', $receipt && $receipt->anulado_em ? 'YES' : 'NO');
out('BEFORE_QUOTA_ID', $payment->quotaCharge?->id ?? 'NONE');
out('BEFORE_QUOTA_ESTADO', $payment->quotaCharge?->estado ?? 'NONE');

app(PaymentCancellationService::class)->cancelar($payment, $motivo);

$paymentFresh = Payment::query()->with(['quotaCharge', 'receipt'])->find($payment->id);
$receiptFresh = Receipt::query()->find($receipt->id);

out('AFTER_PAYMENT_ANULADO', $paymentFresh && $paymentFresh->anulado_em ? 'YES' : 'NO');
out('AFTER_PAYMENT_ANULADO_EM', (string) ($paymentFresh->anulado_em ?? 'NULL'));
out('AFTER_RECEIPT_ANULADO', $receiptFresh && $receiptFresh->anulado_em ? 'YES' : 'NO');
out('AFTER_RECEIPT_ANULADO_EM', (string) ($receiptFresh->anulado_em ?? 'NULL'));
out('AFTER_RECEIPT_MOTIVO', (string) ($receiptFresh->motivo_anulacao ?? 'NULL'));
out('AFTER_QUOTA_ESTADO', $paymentFresh->quotaCharge?->fresh()->estado ?? 'NONE');

$emitirVisible = $paymentFresh && $paymentFresh->anulado_em === null;
out('EXPECT_EMITIR_RECIBO_VISIBLE', $emitirVisible ? 'YES' : 'NO');

$activeReceiptForPayment = Receipt::query()
    ->where('payment_id', $paymentFresh->id)
    ->whereNull('anulado_em')
    ->exists();
out('ACTIVE_RECEIPT_FOR_PAYMENT_EXISTS', $activeReceiptForPayment ? 'YES' : 'NO');

out('RESULT', 'OK_PERSISTED');
