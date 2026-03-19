<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptController extends Controller
{
    public function download(Request $request, Receipt $receipt): Response
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt->load(['member', 'quotaYear']),
        ])->setPaper('a4');

        $filename = 'Recibo_' . str_replace('/', '-', $receipt->numero) . '.pdf';

        return $pdf->download($filename);
    }
}
