<?php

namespace App\Enums;

enum TipoLancamentoDespesa: string
{
    case Unica = 'unica';
    case Mensal = 'mensal';
    case Parcelada = 'parcelada';
}
