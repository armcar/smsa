<?php

namespace App\Mail;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceiptPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Receipt $receipt,
        public string $pdfBinary
    ) {}

    public function build()
    {
        $filename = 'Recibo_' . str_replace('/', '-', $this->receipt->numero) . '.pdf';

        return $this->subject('Recibo de Pagamento - SMSA (' . $this->receipt->numero . ')')
            ->view('emails.receipt_paid')
            ->with([
                'receipt' => $this->receipt,
            ])
            ->attachData($this->pdfBinary, $filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
