<?php

namespace App\Enums;

enum TipoFormaPagamento: string
{
    case Debito = 'debito';
    case Dinheiro = 'dinheiro';
    case Pix = 'pix';
    case Credito = 'credito';
    case Vale = 'vale';
    case Beneficio = 'beneficio';

    public function rotulo(): string
    {
        return match ($this) {
            self::Debito => 'Débito',
            self::Dinheiro => 'Dinheiro',
            self::Pix => 'Pix',
            self::Credito => 'Crédito',
            self::Vale => 'Vale',
            self::Beneficio => 'Benefício',
        };
    }
}
