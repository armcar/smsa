<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocioType extends Model
{
    protected $fillable = [
        'code',      // A | B | C | D
        'nome',      // "Sócio Benfeitor", etc.
        'descricao', // opcional
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class);
    }
}
