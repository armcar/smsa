<?php

namespace App\Filament\Widgets;

use App\Models\Receipt;
use Filament\Widgets\ChartWidget;

class ReceiptsByMonthChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?string $heading = 'Recibos por mês (ano atual)';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $ano = (int) now()->format('Y');

        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $data = array_fill(0, 12, 0);

        $rows = Receipt::query()
            ->where('ano', $ano)
            ->selectRaw('MONTH(data_pagamento) as m, COUNT(*) as c')
            ->groupBy('m')
            ->pluck('c', 'm')
            ->toArray();

        foreach ($rows as $m => $c) {
            $idx = (int) $m - 1;

            if ($idx >= 0 && $idx < 12) {
                $data[$idx] = (int) $c;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Recibos',
                    'data' => $data,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.65)',
                    'borderColor' => 'rgba(217, 119, 6, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
