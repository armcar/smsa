<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $totaisPagosSub = DB::table('payments')
            ->select('quota_charge_id', DB::raw('COALESCE(SUM(valor), 0) as total_pago'))
            ->whereNull('anulado_em')
            ->groupBy('quota_charge_id');

        DB::table('quota_charges')
            ->leftJoinSub($totaisPagosSub, 'totais_pagos', function ($join): void {
                $join->on('totais_pagos.quota_charge_id', '=', 'quota_charges.id');
            })
            ->select(
                'quota_charges.id',
                'quota_charges.valor',
                DB::raw('COALESCE(totais_pagos.total_pago, 0) as total_pago')
            )
            ->orderBy('quota_charges.id')
            ->chunk(500, function ($rows): void {
                foreach ($rows as $row) {
                    $valor = (float) $row->valor;
                    $totalPago = (float) $row->total_pago;

                    $estado = 'pago';
                    if ($totalPago <= 0) {
                        $estado = 'pendente';
                    } elseif ($totalPago < $valor) {
                        $estado = 'parcial';
                    }

                    DB::table('quota_charges')
                        ->where('id', $row->id)
                        ->update([
                            'estado' => $estado,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Sem rollback de dados: manter estado financeiro atual.
    }
};
