<?php

namespace App\Enums;

enum TipoFormaPagamento: string
{
    case Debito = 'debito';
    case Dinheiro = 'dinheiro';
    case Pix = 'pix';

    public function rotulo(): string
    {
        return match ($this) {
            self::Debito => 'Débito',
            self::Dinheiro => 'Dinheiro',
            self::Pix => 'Pix',
        };
    }
}
