<?php

namespace App\Models;

use App\Services\WordPressMemberAccessSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\ValidationException;

class Socio extends Model
{
    protected $fillable = [
        'socio_type_id',
        'num_socio',        // "A 001"
        'nome',
        'morada',
        'codigo_postal',
        'localidade',
        'telefone',
        'telemovel',
        'data_nascimento',
        'numero_fiscal',    // 9 algarismos, sem espaços
        'email',
        'wp_user_id',
        'data_socio',
        'estado',
        'is_instrumentista',
        'instrumento',
        'instrumento_desde',
        'instrumento_ate',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_socio'      => 'date',
        'wp_user_id' => 'integer',
        'is_instrumentista' => 'boolean',
        'instrumento_desde' => 'date',
        'instrumento_ate' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Socio $socio): void {
            if ($socio->hasMovimentos()) {
                throw ValidationException::withMessages([
                    'socio' => 'Nao e possivel eliminar socios com movimentos. Inative o socio para manter o historico.',
                ]);
            }

            if ($socio->wp_user_id) {
                app(WordPressMemberAccessSynchronizer::class)->deleteWordPressUser((int) $socio->wp_user_id);
            }
        });

        static::updating(function (Socio $socio): void {
            if (! $socio->isDirty('estado') || ! $socio->wp_user_id) {
                return;
            }

            $shouldHaveMemberRole = $socio->estado === 'ativo';
            app(WordPressMemberAccessSynchronizer::class)->syncSocioMembershipState(
                (int) $socio->wp_user_id,
                $shouldHaveMemberRole
            );
        });

        static::saving(function (Socio $socio) {
            // limpeza automática do NIF
            if (!empty($socio->numero_fiscal)) {
                $socio->numero_fiscal = preg_replace('/\D+/', '', (string) $socio->numero_fiscal);
            }

            // limpeza do código postal (opcional)
            if (!empty($socio->codigo_postal)) {
                $socio->codigo_postal = trim((string) $socio->codigo_postal);
            }

            if ($socio->email !== null) {
                $normalizedEmail = mb_strtolower(trim((string) $socio->email));
                $socio->email = $normalizedEmail !== '' ? $normalizedEmail : null;
            }

            if (!$socio->is_instrumentista) {
                $socio->instrumento = null;
                $socio->instrumento_desde = null;
                $socio->instrumento_ate = null;
            }
        });
    }

    public function socioType(): BelongsTo
    {
        return $this->belongsTo(SocioType::class);
    }

    public function quotaCharges(): HasMany
    {
        return $this->hasMany(QuotaCharge::class, 'socio_id');
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            QuotaCharge::class,
            'socio_id',
            'quota_charge_id',
            'id',
            'id'
        );
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'member_id');
    }

    public function hasQuotaCharges(): bool
    {
        return $this->quotaCharges()->exists();
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function hasReceipts(): bool
    {
        return $this->receipts()->exists();
    }

    public function hasMovimentos(): bool
    {
        return $this->hasQuotaCharges() || $this->hasPayments() || $this->hasReceipts();
    }

    public function isAtivo(): bool
    {
        return $this->estado === 'ativo';
    }

    public function inativar(): void
    {
        if (! $this->isAtivo()) {
            return;
        }

        $this->update(['estado' => 'suspenso']);
    }

    public function reativar(): void
    {
        if ($this->isAtivo()) {
            return;
        }

        $this->update(['estado' => 'ativo']);
    }
}
