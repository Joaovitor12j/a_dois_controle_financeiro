<?php

use App\Enums\TipoFormaPagamento;

it('cobre exatamente os valores do enum nativo do banco', function () {
    expect(array_column(TipoFormaPagamento::cases(), 'value'))
        ->toBe(['debito', 'dinheiro', 'pix']);
});

it('expõe rótulo para exibição', function (TipoFormaPagamento $tipo, string $esperado) {
    expect($tipo->rotulo())->toBe($esperado);
})->with([
    [TipoFormaPagamento::Debito, 'Débito'],
    [TipoFormaPagamento::Dinheiro, 'Dinheiro'],
    [TipoFormaPagamento::Pix, 'Pix'],
]);
