<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\QuotaCharge;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\JoinClause;

class PendingPaymentsByYearChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?string $heading = 'Pagamentos em falta (valores por ano)';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $paidSub = Payment::query()
            ->selectRaw('quota_charge_id, COALESCE(SUM(valor), 0) as total_pago')
            ->whereNull('anulado_em')
            ->groupBy('quota_charge_id');

        $outstandingExpr = 'GREATEST(quota_charges.valor - COALESCE(payments_agg.total_pago, 0), 0)';

        $rows = QuotaCharge::query()
            ->join('quota_years', 'quota_years.id', '=', 'quota_charges.quota_year_id')
            ->leftJoinSub($paidSub, 'payments_agg', function (JoinClause $join): void {
                $join->on('payments_agg.quota_charge_id', '=', 'quota_charges.id');
            })
            ->selectRaw('quota_years.ano as ano')
            ->selectRaw('SUM(' . $outstandingExpr . ') as em_falta')
            ->groupBy('quota_years.ano')
            ->havingRaw('SUM(' . $outstandingExpr . ') > 0')
            ->orderBy('quota_years.ano')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Em falta (€)',
                    'data' => $rows->map(fn ($row): float => round((float) $row->em_falta, 2))->all(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.65)',
                    'borderColor' => 'rgba(29, 78, 216, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $rows->map(fn ($row): string => (string) $row->ano)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
