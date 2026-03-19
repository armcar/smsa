<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuotaCharge extends Model
{
    protected $table = 'quota_charges';

    protected $fillable = [
        'socio_id',
        'quota_year_id',
        'socio_type_id',
        'valor',
        'estado',
        'emitido_em',
        'vencimento_em',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'emitido_em' => 'date',
        'vencimento_em' => 'date',
    ];

    /**
     * Relações
     */
    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class, 'socio_id');
    }

    public function quotaYear(): BelongsTo
    {
        return $this->belongsTo(QuotaYear::class, 'quota_year_id');
    }

    public function socioType(): BelongsTo
    {
        return $this->belongsTo(SocioType::class, 'socio_type_id');
    }

    /**
     * Pode existir histórico de pagamentos para a mesma quota.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'quota_charge_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'quota_charge_id')
            ->whereNull('anulado_em')
            ->latestOfMany('data_pagamento');
    }

    /**
     * Scopes (para queries limpas)
     */
    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('estado', 'pendente');
    }

    public function scopePagas(Builder $query): Builder
    {
        return $query->where('estado', 'pago');
    }

    /**
     * Sócios Benfeitores (classe B).
     * Aqui usamos o socio_type.code = 'B' (conforme a tua tabela socio_types).
     */
    public function scopeBenfeitores(Builder $query): Builder
    {
        return $query->whereHas('socioType', fn(Builder $q) => $q->where('code', 'B'));
    }

    /**
     * Helpers
     */
    public function isPaga(): bool
    {
        return $this->estado === 'pago';
    }

    public function paymentAtivo(): ?Payment
    {
        return $this->payments()
            ->whereNull('anulado_em')
            ->latest('data_pagamento')
            ->first();
    }

    /**
     * Comandos de estado (úteis para Actions/Observers)
     */
    public function marcarComoPaga(): void
    {
        $this->update(['estado' => 'pago']);
    }

    public function marcarComoPendente(): void
    {
        $this->update(['estado' => 'pendente']);
    }

    public function syncEstadoFromPayments(): void
    {
        $hasActivePayment = $this->payments()->whereNull('anulado_em')->exists();
        $expected = $hasActivePayment ? 'pago' : 'pendente';

        if ($this->estado !== $expected) {
            $this->update(['estado' => $expected]);
        }
    }
}
