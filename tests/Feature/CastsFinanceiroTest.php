<?php

use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\TipoFormaPagamento;
use App\Models\CartaoCredito;
use App\Models\Conta;
use App\Models\Fatura;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function contaDeTeste(): Conta
{
    $usuario = Usuario::factory()->create();
    Auth::login($usuario);

    return Conta::create([
        'usuario_id' => $usuario->id,
        'nome' => 'Conta Principal',
    ]);
}

it('persiste e recupera o enum nativo de tipo de forma de pagamento', function (TipoFormaPagamento $tipo) {
    $forma = FormaPagamento::create([
        'conta_id' => contaDeTeste()->id,
        'nome' => 'Forma',
        'tipo' => $tipo,
    ]);

    expect($forma->fresh()?->tipo)->toBe($tipo);
})->with([
    TipoFormaPagamento::Debito,
    TipoFormaPagamento::Dinheiro,
    TipoFormaPagamento::Pix,
    TipoFormaPagamento::Credito,
]);

it('aceita o valor bruto do enum vindo de request', function () {
    $forma = FormaPagamento::create([
        'conta_id' => contaDeTeste()->id,
        'nome' => 'Forma',
        'tipo' => 'pix',
    ]);

    expect($forma->fresh()?->tipo)->toBe(TipoFormaPagamento::Pix);
});

it('recupera limites do cartão como Money', function () {
    $forma = FormaPagamento::create([
        'conta_id' => contaDeTeste()->id,
        'nome' => 'Cartão',
        'tipo' => TipoFormaPagamento::Credito,
    ]);

    $cartao = CartaoCredito::create([
        'forma_pagamento_id' => $forma->id,
        'limite_total' => Money::fromString('5.000,00'),
        'limite_usado_abertura' => Money::fromCents(12345),
        'dia_fechamento' => 10,
        'dia_vencimento' => 17,
    ]);

    $recarregado = $cartao->fresh();

    expect($recarregado?->limite_total)->toEqual(Money::fromCents(500000));
    expect($recarregado?->limite_usado_abertura)->toEqual(Money::fromCents(12345));
});

it('grava valor de movimentação em centavos e o devolve como Money', function () {
    $forma = FormaPagamento::create([
        'conta_id' => contaDeTeste()->id,
        'nome' => 'Débito',
        'tipo' => TipoFormaPagamento::Debito,
    ]);

    $movimentacao = Movimentacao::create([
        'forma_pagamento_id' => $forma->id,
        'valor' => Money::fromString('-149,90'),
        'data' => '2026-08-30',
    ]);

    expect((int) DB::table('movimentacoes')->where('id', $movimentacao->id)->value('valor'))->toBe(-14990);
    expect($movimentacao->fresh()?->valor)->toEqual(Money::fromCents(-14990));
});

it('encadeia as relations do módulo financeiro', function () {
    $conta = contaDeTeste();

    $formaCredito = FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => 'Cartão',
        'tipo' => TipoFormaPagamento::Credito,
    ]);

    $cartao = CartaoCredito::create([
        'forma_pagamento_id' => $formaCredito->id,
        'limite_total' => Money::fromCents(100000),
        'dia_fechamento' => 5,
        'dia_vencimento' => 12,
    ]);

    $fatura = Fatura::create([
        'cartao_credito_id' => $cartao->forma_pagamento_id,
        'competencia' => '2026-09-01',
    ]);

    $forma = FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => 'Débito',
        'tipo' => TipoFormaPagamento::Debito,
    ]);

    $movimentacao = Movimentacao::create([
        'forma_pagamento_id' => $forma->id,
        'fatura_id' => $fatura->id,
        'valor' => Money::fromCents(25000),
        'data' => '2026-09-12',
    ]);

    expect($movimentacao->formaPagamento->is($forma))->toBeTrue();
    expect($movimentacao->fatura->is($fatura))->toBeTrue();
    expect($fatura->cartaoCredito->formaPagamento->conta->usuario->is($conta->usuario))->toBeTrue();
    expect($conta->formasPagamento->pluck('id')->sort()->values()->all())
        ->toBe(collect([$formaCredito->id, $forma->id])->sort()->values()->all());
    expect($formaCredito->cartaoCredito->is($cartao))->toBeTrue();
    expect($cartao->faturas->pluck('id')->all())->toBe([$fatura->id]);
    expect($forma->movimentacoes->pluck('id')->all())->toBe([$movimentacao->id]);
});

it('grava competência de fatura como primeiro dia do mês e a devolve como Competencia', function () {
    $forma = FormaPagamento::create([
        'conta_id' => contaDeTeste()->id,
        'nome' => 'Cartão',
        'tipo' => TipoFormaPagamento::Credito,
    ]);

    $cartao = CartaoCredito::create([
        'forma_pagamento_id' => $forma->id,
        'limite_total' => Money::fromCents(100000),
        'dia_fechamento' => 5,
        'dia_vencimento' => 12,
    ]);

    $fatura = Fatura::create([
        'cartao_credito_id' => $cartao->forma_pagamento_id,
        'competencia' => '2026-09',
    ]);

    expect(DB::table('faturas')->where('id', $fatura->id)->value('competencia'))->toBe('2026-09-01');
    expect($fatura->fresh()?->competencia)->toEqual(Competencia::deAnoMes(2026, 9));
});
