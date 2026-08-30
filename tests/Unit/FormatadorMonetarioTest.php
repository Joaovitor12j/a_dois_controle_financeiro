<?php

use App\Domain\ValueObjects\Money;
use App\Support\FormatadorMonetario;

it('formata em real brasileiro', function (int $cents, string $esperado) {
    expect(FormatadorMonetario::paraReal(Money::fromCents($cents)))->toBe($esperado);
})->with([
    [0, 'R$ 0,00'],
    [5, 'R$ 0,05'],
    [123456, 'R$ 1.234,56'],
    [-123456, '-R$ 1.234,56'],
    [100000000, 'R$ 1.000.000,00'],
]);
