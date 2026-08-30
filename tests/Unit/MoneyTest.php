<?php

use App\Domain\ValueObjects\Money;

it('cria a partir de centavos', function () {
    expect(Money::fromCents(1234)->cents)->toBe(1234);
});

it('zero vale zero', function () {
    expect(Money::zero()->isZero())->toBeTrue();
});

it('converte string com vírgula decimal', function (string $entrada, int $esperado) {
    expect(Money::fromString($entrada)->cents)->toBe($esperado);
})->with([
    ['1234,56', 123456],
    ['1.234,56', 123456],
    ['0,05', 5],
    ['10', 1000],
    ['10.5', 1050],
    ['-1.234,56', -123456],
    [' 7,10 ', 710],
]);

it('rejeita string malformada', function (string $entrada) {
    expect(fn () => Money::fromString($entrada))->toThrow(InvalidArgumentException::class);
})->with([
    ['abc'],
    [''],
    ['1,234'],
    ['R$ 10,00'],
    ['1..2'],
]);

it('soma sem mutar as instâncias originais', function () {
    $a = Money::fromCents(100);
    $b = Money::fromCents(250);

    $soma = $a->plus($b);

    expect($soma->cents)->toBe(350)
        ->and($a->cents)->toBe(100)
        ->and($b->cents)->toBe(250)
        ->and($soma)->not->toBe($a);
});

it('subtrai e permite resultado negativo', function () {
    expect(Money::fromCents(100)->minus(Money::fromCents(250))->cents)->toBe(-150);
});

it('multiplica por escalar inteiro', function () {
    expect(Money::fromCents(333)->times(3)->cents)->toBe(999);
});

it('nega e absolutiza', function () {
    expect(Money::fromCents(-500)->negated()->cents)->toBe(500)
        ->and(Money::fromCents(-500)->absolute()->cents)->toBe(500)
        ->and(Money::fromCents(500)->absolute()->cents)->toBe(500);
});

it('classifica sinal', function () {
    expect(Money::fromCents(-1)->isNegative())->toBeTrue()
        ->and(Money::fromCents(1)->isPositive())->toBeTrue()
        ->and(Money::zero()->isNegative())->toBeFalse()
        ->and(Money::zero()->isPositive())->toBeFalse();
});

it('compara por valor, não por identidade', function () {
    expect(Money::fromCents(42)->equals(Money::fromCents(42)))->toBeTrue()
        ->and(Money::fromCents(42)->equals(Money::fromCents(43)))->toBeFalse();
});

it('formata em real brasileiro', function (int $cents, string $esperado) {
    expect(Money::fromCents($cents)->format())->toBe($esperado);
})->with([
    [0, 'R$ 0,00'],
    [5, 'R$ 0,05'],
    [123456, 'R$ 1.234,56'],
    [-123456, '-R$ 1.234,56'],
    [100000000, 'R$ 1.000.000,00'],
]);

it('serializa em json como centavos', function () {
    expect(json_encode(['valor' => Money::fromCents(1234)]))->toBe('{"valor":1234}');
});
