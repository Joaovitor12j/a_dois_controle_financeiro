<?php

namespace App\Domain\Usuario;

enum CorIdentidade: string
{
    case Azul = '#2563EB';
    case Verde = '#16A34A';
    case Roxo = '#7C3AED';
    case Laranja = '#EA580C';
    case Rosa = '#DB2777';
    case Ciano = '#0891B2';
    case Ambar = '#D97706';
    case Indigo = '#4F46E5';

    public function nome(): string
    {
        return match ($this) {
            self::Azul => 'Azul',
            self::Verde => 'Verde',
            self::Roxo => 'Roxo',
            self::Laranja => 'Laranja',
            self::Rosa => 'Rosa',
            self::Ciano => 'Ciano',
            self::Ambar => 'Âmbar',
            self::Indigo => 'Índigo',
        };
    }

    /** @return list<self> */
    public function compativeis(): array
    {
        $incompativeis = match ($this) {
            self::Azul => [self::Ciano, self::Indigo],
            self::Ciano => [self::Azul],
            self::Roxo => [self::Indigo],
            self::Indigo => [self::Azul, self::Roxo],
            self::Laranja => [self::Ambar],
            self::Ambar => [self::Laranja],
            default => [],
        };

        return array_values(array_filter(
            self::cases(),
            fn (self $opcao): bool => $opcao !== $this && ! in_array($opcao, $incompativeis, true),
        ));
    }
}
