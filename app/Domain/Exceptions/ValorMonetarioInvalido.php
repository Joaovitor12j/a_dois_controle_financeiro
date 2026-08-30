<?php

namespace App\Domain\Exceptions;

use InvalidArgumentException;

final class ValorMonetarioInvalido extends InvalidArgumentException implements DominioException
{
    public static function paraEntrada(string $entrada): self
    {
        return new self("Valor monetário inválido: {$entrada}");
    }
}
