<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PortugueseNif implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nif = preg_replace('/\D+/', '', (string) $value);

        if ($nif === '' || strlen($nif) !== 9) {
            $fail('O NIF deve ter 9 algarismos.');
            return;
        }

        // não permitir todos zeros
        if ($nif === '000000000') {
            $fail('NIF inválido.');
            return;
        }

        $digits = array_map('intval', str_split($nif));

        // validar primeiro dígito (regra básica)
        if (!in_array($digits[0], [1, 2, 3, 5, 6, 7, 8, 9])) {
            $fail('NIF inválido (prefixo incorreto).');
            return;
        }

        // cálculo módulo 11
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += $digits[$i] * (9 - $i);
        }

        $check = 11 - ($sum % 11);
        if ($check >= 10) {
            $check = 0;
        }

        if ($digits[8] !== $check) {
            $fail('NIF inválido.');
        }
    }
}
