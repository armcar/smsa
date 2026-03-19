<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Receipt extends Model
{
    protected $fillable = [
        'numero',
        'ano',
        'sequencia',
        'member_id',
        'quota_year_id',
        'valor',
        'data_pagamento',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor' => 'decimal:2',
        'ano' => 'integer',
        'sequencia' => 'integer',
    ];

    public function member(): BelongsTo
    {
        // member_id aponta para socios.id
        return $this->belongsTo(Socio::class, 'member_id');
    }

    public function quotaYear(): BelongsTo
    {
        return $this->belongsTo(QuotaYear::class);
    }

    public static function gerarNumeroSeguro(int $ano): array
    {
        return DB::transaction(function () use ($ano) {
            $ultimaSequencia = self::where('ano', $ano)
                ->lockForUpdate()
                ->max('sequencia');

            $novaSequencia = $ultimaSequencia ? ($ultimaSequencia + 1) : 1;

            $numero = sprintf('%d/%04d', $ano, $novaSequencia);

            return [
                'numero' => $numero,
                'sequencia' => $novaSequencia,
            ];
        }, 3);
    }
}
