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

    protected static function booted(): void
    {
        static::created(function (Payment $payment): void {
            $payment->quotaCharge?->syncEstadoFromPayments();
        });

        static::updated(function (Payment $payment): void {
            $originalQuotaChargeId = $payment->getOriginal('quota_charge_id');
            if ($originalQuotaChargeId && (int) $originalQuotaChargeId !== (int) $payment->quota_charge_id) {
                QuotaCharge::query()->find($originalQuotaChargeId)?->syncEstadoFromPayments();
            }

            $payment->quotaCharge?->syncEstadoFromPayments();
        });

        static::deleted(function (Payment $payment): void {
            QuotaCharge::query()->find($payment->quota_charge_id)?->syncEstadoFromPayments();
        });
    }

    public function quotaCharge(): BelongsTo
    {
        return $this->belongsTo(QuotaCharge::class);
    }
}
