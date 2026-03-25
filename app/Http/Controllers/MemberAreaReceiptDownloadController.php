<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MemberAreaReceiptDownloadController extends Controller
{
    public function __invoke(Request $request, Receipt $receipt): Response
    {
        $wpUserId = (int) $request->query('wp_user_id', 0);
        $member = $receipt->member;

        abort_unless($wpUserId > 0, 403);
        abort_unless($member !== null, 404);
        abort_unless((int) $member->wp_user_id === $wpUserId, 403);
        abort_if($receipt->isAnulado(), 404);

        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt->load(['member', 'quotaYear', 'payment.quotaCharge']),
        ])->setPaper('a4');

        $filename = 'Recibo_' . str_replace('/', '-', $receipt->numero) . '.pdf';

        return $pdf->download($filename);
    }
}
