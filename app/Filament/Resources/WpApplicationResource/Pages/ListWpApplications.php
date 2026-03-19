<?php

namespace App\Filament\Resources\WpApplicationResource\Pages;

use App\Filament\Resources\WpApplicationResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWpApplications extends ListRecords
{
    protected static string $resource = WpApplicationResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'escola' => Tab::make('Pedidos de Novos Alunos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kind', 'escola')),
            'socio' => Tab::make('Pedidos de Novos Socios')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kind', 'socio')),
        ];
    }
}
