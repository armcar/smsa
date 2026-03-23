<?php

namespace App\Services;

use App\Models\Socio;

class SocioNumberService
{
    public function getNextNumberForType(int $socioTypeId): int
    {
        $max = (int) Socio::query()
            ->where('socio_type_id', $socioTypeId)
            ->lockForUpdate()
            ->max('num_socio');

        return max($max + 1, 1);
    }
}

