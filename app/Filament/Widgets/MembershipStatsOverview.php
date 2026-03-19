<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WpApplicationResource;
use App\Models\Socio;
use App\Models\SocioType;
use App\Models\WpApplication;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MembershipStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Gestão de membros';

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $socioRequests = WpApplication::query()
            ->where('kind', 'socio')
            ->where('status', 'pendente')
            ->count();

        $alunoRequests = WpApplication::query()
            ->where('kind', 'escola')
            ->where('status', 'pendente')
            ->count();

        $stats = [
            Stat::make('Pedidos novos sócios', $socioRequests)
                ->url(WpApplicationResource::getUrl('index', ['activeTab' => 'socio'])),
            Stat::make('Pedidos novos alunos', $alunoRequests)
                ->url(WpApplicationResource::getUrl('index', ['activeTab' => 'escola'])),
        ];

        SocioType::query()
            ->orderBy('code')
            ->get()
            ->each(function (SocioType $type) use (&$stats): void {
                $count = Socio::query()
                    ->where('socio_type_id', $type->id)
                    ->count();

                $stats[] = Stat::make("Sócios {$type->code}", $count);
            });

        $musicians = Socio::query()
            ->where('is_instrumentista', true)
            ->where('estado', 'ativo')
            ->count();

        $stats[] = Stat::make('Músicos ativos', $musicians);

        return $stats;
    }
}
