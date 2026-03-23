<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
        'payment_id',
        'valor',
        'data_pagamento',
        'anulado_em',
        'motivo_anulacao',
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'anulado_em' => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->whereNull('anulado_em');
    }

    public function scopeAnulados(Builder $query): Builder
    {
        return $query->whereNotNull('anulado_em');
    }

    public function isAnulado(): bool
    {
        return $this->anulado_em !== null;
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
