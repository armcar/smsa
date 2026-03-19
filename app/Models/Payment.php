<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'quota_charge_id',
        'data_pagamento',
        'valor',
        'metodo',
        'documento_tipo',
        'documento_numero',
        'referencia',
        'notas',
        'anulado_em',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
        'anulado_em' => 'datetime',
    ];

    public function quotaCharge(): BelongsTo
    {
        return $this->belongsTo(QuotaCharge::class);
    }
}