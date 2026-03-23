<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            self::syncQuotaCharge((int) $payment->quota_charge_id);
        });

        static::updated(function (Payment $payment): void {
            if (! $payment->wasChanged(['quota_charge_id', 'valor', 'anulado_em'])) {
                return;
            }

            $originalQuotaChargeId = (int) ($payment->getOriginal('quota_charge_id') ?? 0);
            $currentQuotaChargeId = (int) ($payment->quota_charge_id ?? 0);

            if ($originalQuotaChargeId > 0) {
                self::syncQuotaCharge($originalQuotaChargeId);
            }

            if ($currentQuotaChargeId > 0 && $currentQuotaChargeId !== $originalQuotaChargeId) {
                self::syncQuotaCharge($currentQuotaChargeId);
            }
        });

        static::deleted(function (Payment $payment): void {
            self::syncQuotaCharge((int) $payment->quota_charge_id);
        });
    }

    private static function syncQuotaCharge(int $quotaChargeId): void
    {
        if ($quotaChargeId <= 0) {
            return;
        }

        QuotaCharge::query()->find($quotaChargeId)?->syncEstadoFromPayments();
    }

    public function quotaCharge(): BelongsTo
    {
        return $this->belongsTo(QuotaCharge::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class, 'payment_id');
    }
}
