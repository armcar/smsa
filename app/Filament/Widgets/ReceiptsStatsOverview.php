<?php

namespace App\Filament\Widgets;

use App\Models\Receipt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReceiptsStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $ano = (int) now()->format('Y');

        $countAno = Receipt::where('ano', $ano)->count();
        $sumAno = (float) Receipt::where('ano', $ano)->sum('valor');

        $countTotal = Receipt::count();
        $sumTotal = (float) Receipt::sum('valor');

        return [
            Stat::make("Recibos ($ano)", $countAno),
            Stat::make("Total € ($ano)", number_format($sumAno, 2, ',', '.') . ' €'),
            Stat::make('Recibos (total)', $countTotal),
            Stat::make('Total € (total)', number_format($sumTotal, 2, ',', '.') . ' €'),
        ];
    }
}
