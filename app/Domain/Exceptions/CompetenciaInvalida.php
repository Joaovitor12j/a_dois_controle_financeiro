<?php

namespace App\Domain\Exceptions;

use InvalidArgumentException;

final class CompetenciaInvalida extends InvalidArgumentException implements DominioException
{
    public static function paraMes(int $mes): self
    {
        return new self("Mês de competência inválido: {$mes}");
    }

    public static function paraEntrada(string $entrada): self
    {
        return new self("Competência inválida: {$entrada}");
    }
}
