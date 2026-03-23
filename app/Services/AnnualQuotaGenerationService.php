<?php

namespace App\Services;

use App\Models\QuotaCharge;
use App\Models\QuotaYear;
use App\Models\Socio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnnualQuotaGenerationService
{
    /**
     * @return array{eligible:int, created:int}
     */
    public function generateForQuotaYear(QuotaYear $quotaYear): array
    {
        $eligibleQuery = Socio::query()
            ->where('estado', 'ativo')
            ->whereHas('socioType', fn (Builder $q) => $q->where('code', 'B'));

        $eligible = (clone $eligibleQuery)->count();
        $created = 0;

        DB::transaction(function () use ($quotaYear, $eligibleQuery, &$created): void {
            $eligibleQuery
                ->select(['id', 'socio_type_id'])
                ->orderBy('id')
                ->chunkById(200, function ($socios) use ($quotaYear, &$created): void {
                    foreach ($socios as $socio) {
                        $charge = QuotaCharge::query()->firstOrCreate(
                            [
                                'socio_id' => $socio->id,
                                'quota_year_id' => $quotaYear->id,
                            ],
                            [
                                'socio_type_id' => $socio->socio_type_id,
                                'valor' => $quotaYear->valor,
                                'estado' => 'pendente',
                                'emitido_em' => now()->toDateString(),
                                'vencimento_em' => optional($quotaYear->data_fim)->toDateString(),
                                'observacoes' => null,
                            ]
                        );

                        if ($charge->wasRecentlyCreated) {
                            $created++;
                        }
                    }
                });
        });

        return [
            'eligible' => $eligible,
            'created' => $created,
        ];
    }
}

