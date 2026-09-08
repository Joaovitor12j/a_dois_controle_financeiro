<?php

use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Models\CartaoCredito;
use App\Models\Despesa;
use App\Models\Fatura;
use App\Models\Movimentacao;
use App\Models\Scopes\DespesaScope;
use App\Models\Usuario;
use App\Services\Financeiro\FaturaService;
use Illuminate\Validation\ValidationException;

it('gera fatura agregando parcela do ciclo mesmo sem ela ter sido marcada como paga antes', function () {
    // Reprodução do bug relatado: cartão fecha dia 1, vence dia 10. Parcela com
    // data_primeira_parcela 30/07 cai em agosto (parcela 1) e setembro (parcela 2) — a fatura de
    // setembro precisa agregar a parcela 2 mesmo sem nenhuma movimentação registrada pra ela.
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 1);
    CartaoCredito::where('forma_pagamento_id', $c->cartaoCredito->id)->update(['dia_vencimento' => 10]);

    criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 20000,
        'numero_parcelas' => 12,
        'data_primeira_parcela' => '2026-07-30',
    ]);

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));

    expect($fatura->valor)->toEqual(Money::fromCents(20000))
        ->and(Movimentacao::count())->toBe(0);

    $itens = app(FaturaService::class)->itens($c->cartaoCredito, Competencia::deAnoMes(2026, 9));

    expect($itens)->toHaveCount(1)
        ->and($itens[0]['numeroParcela'])->toBe(2)
        ->and($itens[0]['movimentacao'])->toBeNull();
});

it('gera fatura agregando única paga no cartão dentro da janela de fechamento', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    $unica = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 20000, 'data_vencimento' => '2026-09-08']);
    criarMovimentacaoDespesa($unica, $c->cartaoCredito, '2026-09-01', data: '2026-09-08');

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));

    expect($fatura->valor)->toEqual(Money::fromCents(20000));
});

it('não agrega única fora da janela de fechamento nem de outro cartão', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);
    $outroCartaoForma = formaPagamentoDespesa($c->conta, 'credito', 'Outro cartão');
    cartaoCreditoDespesa($outroCartaoForma, diaFechamento: 10);

    $foraDaJanela = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 10000, 'data_vencimento' => '2026-09-15']);
    criarMovimentacaoDespesa($foraDaJanela, $c->cartaoCredito, '2026-09-01', data: '2026-09-15');

    $outroCartao = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 30000, 'data_vencimento' => '2026-09-05']);
    criarMovimentacaoDespesa($outroCartao, $outroCartaoForma, '2026-09-01', data: '2026-09-05');

    expect(fn () => app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9)))
        ->toThrow(ValidationException::class);
});

it('regenera fatura ainda não paga recalculando o valor sobre a mesma fatura', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 10000,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));

    $unica = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 5000, 'data_vencimento' => '2026-09-02']);
    criarMovimentacaoDespesa($unica, $c->cartaoCredito, '2026-09-01', data: '2026-09-06');

    $regenerada = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));

    expect($regenerada->id)->toBe($fatura->id)
        ->and($regenerada->valor)->toEqual(Money::fromCents(15000))
        ->and(Fatura::count())->toBe(1);
});

it('bloqueia regenerar fatura já paga', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 10000,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));
    $formaReal = formaPagamentoDespesa($c->conta, 'debito', 'Conta corrente');

    app(FaturaService::class)->marcarComoPaga($fatura, $formaReal->id, '2026-09-20');

    expect(fn () => app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9)))
        ->toThrow(ValidationException::class);
});

it('falha ao gerar fatura sem despesas nesse cartão para essa competência', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    expect(fn () => app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9)))
        ->toThrow(ValidationException::class);
});

it('pagar a fatura cria a movimentação de cada parcela pendente do ciclo mais a movimentação da própria fatura', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    $parcelada = criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 20000,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $unica = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 5000, 'data_vencimento' => '2026-09-02']);
    criarMovimentacaoDespesa($unica, $c->cartaoCredito, '2026-09-01', data: '2026-09-06');

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));
    $formaReal = formaPagamentoDespesa($c->conta, 'debito', 'Conta corrente');

    $movimentacaoFatura = app(FaturaService::class)->marcarComoPaga($fatura, $formaReal->id, '2026-09-20');

    expect(Movimentacao::count())->toBe(3)
        ->and($movimentacaoFatura->fatura_id)->toBe($fatura->id)
        ->and($movimentacaoFatura->despesa_id)->toBeNull()
        ->and($movimentacaoFatura->forma_pagamento_id)->toBe($formaReal->id)
        ->and($movimentacaoFatura->valor)->toEqual($fatura->valor->negated());

    $movimentacaoParcela = Movimentacao::where('despesa_id', $parcelada->id)->sole();

    expect($movimentacaoParcela->fatura_id)->toBe($fatura->id)
        ->and($movimentacaoParcela->forma_pagamento_id)->toBe($c->cartaoCredito->id)
        ->and((string) $movimentacaoParcela->competencia)->toBe('2026-09')
        ->and($fatura->estaPaga())->toBeTrue();
});

it('rejeita pagar fatura com outro cartão de crédito', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 10000,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));
    $outroCartao = formaPagamentoDespesa($c->conta, 'credito', 'Outro cartão');
    cartaoCreditoDespesa($outroCartao, diaFechamento: 5);

    expect(fn () => app(FaturaService::class)->marcarComoPaga($fatura, $outroCartao->id, '2026-09-20'))
        ->toThrow(ValidationException::class);
});

it('desfazer pagamento da fatura remove a movimentação própria e as das parcelas que ela criou, sem mexer em única já paga antes', function () {
    $c = novoContextoDespesa();
    cartaoCreditoDespesa($c->cartaoCredito, diaFechamento: 10);

    criarDespesaParcelada($c->usuario, $c->categoria, $c->cartaoCredito, [
        'valor' => 20000,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $unica = criarDespesaUnica($c->usuario, $c->categoria, ['valor' => 5000, 'data_vencimento' => '2026-09-02']);
    criarMovimentacaoDespesa($unica, $c->cartaoCredito, '2026-09-01', data: '2026-09-06');

    $fatura = app(FaturaService::class)->gerar($c->cartaoCredito, Competencia::deAnoMes(2026, 9));
    $formaReal = formaPagamentoDespesa($c->conta, 'debito', 'Conta corrente');

    app(FaturaService::class)->marcarComoPaga($fatura, $formaReal->id, '2026-09-20');
    app(FaturaService::class)->desfazerPagamento($fatura->fresh());

    expect(Movimentacao::count())->toBe(1)
        ->and(Movimentacao::sole()->despesa_id)->toBe($unica->id)
        ->and($fatura->fresh()?->estaPaga())->toBeFalse();
});

it('gera, paga e lista fatura via rota', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartaoForma = formaPagamentoDespesa($conta, 'credito', 'Cartão');
    CartaoCredito::create([
        'forma_pagamento_id' => $cartaoForma->id,
        'limite_total' => Money::fromCents(500000),
        'dia_fechamento' => 10,
        'dia_vencimento' => 20,
    ]);

    criarDespesaParcelada($eu, $categoria, $cartaoForma, [
        'valor' => 12000,
        'numero_parcelas' => 2,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $this->actingAs($eu)
        ->post(route('faturas.store'), [
            'forma_pagamento_id' => $cartaoForma->id,
            'competencia' => '2026-09',
        ])
        ->assertRedirect(route('faturas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Fatura gerada com sucesso.']);

    $fatura = Fatura::sole();
    expect($fatura->valor)->toEqual(Money::fromCents(12000));

    $this->actingAs($eu)
        ->get(route('faturas.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Faturas/Index')
            ->has('faturas', 1)
            ->has('faturas.0.itens', 1)
            ->where('faturas.0.paga', false)
        );

    $formaReal = formaPagamentoDespesa($conta, 'debito', 'Conta corrente');

    $this->actingAs($eu)
        ->patch(route('faturas.marcar-como-paga', $fatura), [
            'forma_pagamento_id' => $formaReal->id,
            'data_pagamento' => '2026-09-20',
        ])
        ->assertRedirect(route('faturas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Fatura marcada como paga.']);

    expect($fatura->fresh()?->estaPaga())->toBeTrue();

    $this->actingAs($eu)
        ->patch(route('faturas.desfazer-pagamento', $fatura))
        ->assertRedirect(route('faturas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

    expect($fatura->fresh()?->estaPaga())->toBeFalse();
});

it('rejeita gerar fatura para forma de pagamento que não é crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $forma = formaPagamentoDespesa($conta);

    $this->actingAs($eu)
        ->post(route('faturas.store'), [
            'forma_pagamento_id' => $forma->id,
            'competencia' => '2026-09',
        ])
        ->assertSessionHasErrors('forma_pagamento_id');
});

it('não alcança fatura de cartão do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $contaParceiro = contaDoUsuarioDespesa($parceiro);
    $categoriaParceiro = categoriaDespesaDeTeste();
    $cartaoParceiro = formaPagamentoDespesa($contaParceiro, 'credito', 'Cartão do parceiro');
    cartaoCreditoDespesa($cartaoParceiro, diaFechamento: 10);

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id,
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoriaParceiro->id,
        'descricao' => 'Despesa do parceiro',
        'valor' => 10000,
        'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $cartaoParceiro->id,
        'numero_parcelas' => 3,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    $fatura = app(FaturaService::class)->gerar($cartaoParceiro, Competencia::deAnoMes(2026, 9));

    $this->actingAs($eu)
        ->patch(route('faturas.marcar-como-paga', $fatura), [
            'forma_pagamento_id' => $cartaoParceiro->id,
            'data_pagamento' => '2026-09-20',
        ])
        ->assertForbidden();
});
