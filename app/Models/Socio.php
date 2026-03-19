<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_instrumentista' => 'boolean',
        'instrumento_desde' => 'date',
        'instrumento_ate' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Socio $socio) {
            // limpeza automática do NIF
            if (!empty($socio->numero_fiscal)) {
                $socio->numero_fiscal = preg_replace('/\D+/', '', (string) $socio->numero_fiscal);
            }

            // limpeza do código postal (opcional)
            if (!empty($socio->codigo_postal)) {
                $socio->codigo_postal = trim((string) $socio->codigo_postal);
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
}
