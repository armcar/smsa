<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotaYear extends Model
{
    protected $fillable = [
        'ano',
        'valor',
        'ativo',
        'data_inicio',
        'data_fim',
        'nota',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $quotaYear) {
            if ($quotaYear->ativo) {
                self::query()
                    ->whereKeyNot($quotaYear->getKey())
                    ->where('ativo', true)
                    ->update(['ativo' => false]);
            }
        });
    }
}