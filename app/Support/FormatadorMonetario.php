<?php

namespace App\Support;

use App\Domain\ValueObjects\Money;

final class FormatadorMonetario
{
    public static function paraReal(Money $valor): string
    {
        $sinal = $valor->isNegative() ? '-' : '';
        $absoluto = $valor->absolute()->cents;

        $inteiros = number_format(intdiv($absoluto, 100), 0, ',', '.');
        $centavos = str_pad((string) ($absoluto % 100), 2, '0', STR_PAD_LEFT);

        return $sinal.'R$ '.$inteiros.','.$centavos;
    }
}
