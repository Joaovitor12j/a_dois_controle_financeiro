<?php

use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\CartaoCredito;
use App\Models\CategoriaDespesa;
use App\Models\Conta;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Scopes\DespesaScope;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use App\Services\Financeiro\DespesaService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function novoContextoDespesa(): object
{
    $usuario = Usuario::factory()->create();
    Auth::login($usuario);

    $conta = Conta::create(['usuario_id' => $usuario->id, 'nome' => 'Conta Principal']);
    $categoria = CategoriaDespesa::create(['nome' => 'Mercado', 'cor' => '#f44336', 'icone' => 'cart']);
    $formaPagamento = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Débito', 'tipo' => 'debito']);
    $cartaoCredito = FormaPagamento::create(['conta_id' => $conta->id, 'nome' => 'Cartão', 'tipo' => 'credito']);

    return (object) compact('usuario', 'conta', 'categoria', 'formaPagamento', 'cartaoCredito');
}

/** @param array<string, mixed> $overrides */
function despesaValida(object $contexto, array $overrides = []): Despesa
{
    return Despesa::create(array_merge([
        'usuario_id' => $contexto->usuario->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $contexto->categoria->id,
        'descricao' => 'Mercado',
        'valor' => Money::fromCents(15000),
        'tipo_lancamento' => TipoLancamentoDespesa::Unica,
        'data_vencimento' => '2026-08-10',
    ], $overrides));
}

function contaDoUsuarioDespesa(Usuario $usuario): Conta
{
    return Conta::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $usuario->id,
        'nome' => 'Nubank',
    ]);
}

function categoriaDespesaDeTeste(): CategoriaDespesa
{
    return CategoriaDespesa::create(['nome' => 'Mercado', 'cor' => '#f44336', 'icone' => 'cart']);
}

function formaPagamentoDespesa(Conta $conta, string $tipo = 'debito', string $nome = 'Débito'): FormaPagamento
{
    return FormaPagamento::create(['conta_id' => $conta->id, 'nome' => $nome, 'tipo' => $tipo]);
}

function cartaoCreditoDespesa(FormaPagamento $formaPagamento, int $diaFechamento = 20): CartaoCredito
{
    return CartaoCredito::create([
        'forma_pagamento_id' => $formaPagamento->id,
        'limite_total' => Money::fromCents(500000),
        'limite_usado_abertura' => Money::zero(),
        'dia_fechamento' => $diaFechamento,
        'dia_vencimento' => $diaFechamento >= 21 ? $diaFechamento - 21 : $diaFechamento + 7,
    ]);
}

/** @param array<string, mixed> $overrides */
function criarDespesaUnica(Usuario $usuario, CategoriaDespesa $categoria, array $overrides = []): Despesa
{
    return Despesa::withoutGlobalScope(DespesaScope::class)->create(array_merge([
        'usuario_id' => $usuario->id,
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado',
        'valor' => 10000,
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-08-01',
    ], $overrides));
}

/** @param array<string, mixed> $overrides */
function criarDespesaMensal(Usuario $usuario, CategoriaDespesa $categoria, array $overrides = []): Despesa
{
    return Despesa::withoutGlobalScope(DespesaScope::class)->create(array_merge([
        'usuario_id' => $usuario->id,
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Aluguel',
        'valor' => 100000,
        'tipo_lancamento' => 'mensal',
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01',
    ], $overrides));
}

/** @param array<string, mixed> $overrides */
function criarDespesaParcelada(Usuario $usuario, CategoriaDespesa $categoria, FormaPagamento $cartao, array $overrides = []): Despesa
{
    return Despesa::withoutGlobalScope(DespesaScope::class)->create(array_merge([
        'usuario_id' => $usuario->id,
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Geladeira',
        'valor' => 50000,
        'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $cartao->id,
        'numero_parcelas' => 10,
        'data_primeira_parcela' => '2026-09-01',
    ], $overrides));
}

function criarMovimentacaoDespesa(Despesa $despesa, FormaPagamento $forma, string $competencia, string $data = '2026-08-05'): Movimentacao
{
    return Movimentacao::create([
        'forma_pagamento_id' => $forma->id,
        'valor' => $despesa->valor->negated(),
        'data' => $data,
        'despesa_id' => $despesa->id,
        'competencia' => $competencia,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadDespesaUnica(CategoriaDespesa $categoria, array $overrides = []): array
{
    return array_merge([
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado',
        'valor' => 15000,
        'tipo_lancamento' => 'unica',
        'data_vencimento' => '2026-08-10',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadDespesaMensal(CategoriaDespesa $categoria, array $overrides = []): array
{
    return array_merge([
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Aluguel',
        'valor' => 150000,
        'tipo_lancamento' => 'mensal',
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadDespesaParcelada(CategoriaDespesa $categoria, FormaPagamento $formaPagamento, array $overrides = []): array
{
    return array_merge([
        'contexto' => 'individual',
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Geladeira',
        'valor' => 50000,
        'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $formaPagamento->id,
        'numero_parcelas' => 10,
        'data_primeira_parcela' => '2026-09-01',
    ], $overrides);
}

/**
 * tipo_lancamento é imutável: o update geral rejeita o campo mesmo com o mesmo valor persistido.
 *
 * @param  array<string, mixed>  $payload
 * @return array<string, mixed>
 */
function payloadAtualizacaoDespesa(array $payload): array
{
    unset($payload['tipo_lancamento']);

    return $payload;
}

// Model

it('faz cast de valor, tipo_lancamento e contexto', function () {
    $despesa = despesaValida(novoContextoDespesa(), [
        'valor' => Money::fromCents(99900),
        'contexto' => ContextoDespesa::Conjunta,
    ]);

    expect($despesa->fresh()?->valor)->toEqual(Money::fromCents(99900))
        ->and($despesa->fresh()?->tipo_lancamento)->toBe(TipoLancamentoDespesa::Unica)
        ->and($despesa->fresh()?->contexto)->toBe(ContextoDespesa::Conjunta);
});

it('relações belongsTo retornam os models corretos', function () {
    $contexto = novoContextoDespesa();
    $despesa = despesaValida($contexto, [
        'descricao' => 'Geladeira',
        'tipo_lancamento' => TipoLancamentoDespesa::Parcelada,
        'data_vencimento' => null,
        'forma_pagamento_id' => $contexto->cartaoCredito->id,
        'numero_parcelas' => 5,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    expect($despesa->usuario->is($contexto->usuario))->toBeTrue()
        ->and($despesa->formaPagamento->is($contexto->cartaoCredito))->toBeTrue()
        ->and($despesa->categoriaDespesa->is($contexto->categoria))->toBeTrue();
});

it('relação hasMany movimentacoes retorna as movimentações da despesa', function () {
    $contexto = novoContextoDespesa();
    $despesa = despesaValida($contexto);

    $movimentacao = criarMovimentacaoDespesa($despesa, $contexto->formaPagamento, '2026-08');

    expect($despesa->movimentacoes->pluck('id')->all())->toBe([$movimentacao->id]);
});

it('ehUnica, ehMensal, ehParcelada e ehConjunta refletem os campos', function () {
    $contexto = novoContextoDespesa();

    $unica = despesaValida($contexto);
    $mensal = despesaValida($contexto, [
        'descricao' => 'Aluguel',
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_vencimento' => null,
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01',
    ]);
    $conjunta = despesaValida($contexto, ['descricao' => 'Conjunta', 'contexto' => ContextoDespesa::Conjunta]);

    expect($unica->ehUnica())->toBeTrue()
        ->and($unica->ehMensal())->toBeFalse()
        ->and($unica->ehParcelada())->toBeFalse()
        ->and($mensal->ehMensal())->toBeTrue()
        ->and($mensal->ehUnica())->toBeFalse()
        ->and($conjunta->ehConjunta())->toBeTrue()
        ->and($unica->ehConjunta())->toBeFalse();
});

it('valorTotal multiplica valor por numero_parcelas só quando parcelada', function () {
    $contexto = novoContextoDespesa();

    $unica = despesaValida($contexto, ['valor' => Money::fromCents(10000)]);
    $parcelada = despesaValida($contexto, [
        'descricao' => 'Geladeira',
        'valor' => Money::fromCents(20000),
        'tipo_lancamento' => TipoLancamentoDespesa::Parcelada,
        'data_vencimento' => null,
        'forma_pagamento_id' => $contexto->cartaoCredito->id,
        'numero_parcelas' => 5,
        'data_primeira_parcela' => '2026-09-01',
    ]);

    expect($unica->valorTotal())->toEqual(Money::fromCents(10000))
        ->and($parcelada->valorTotal())->toEqual(Money::fromCents(100000));
});

// Constraints: despesas_campos_por_tipo_check

it('constraint: despesa única sem data_vencimento lança erro', function () {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, ['data_vencimento' => null]))->toThrow(QueryException::class);
});

it('constraint: despesa única com campo de mensal ou parcelada preenchido lança erro', function (array $sobrescritas) {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, $sobrescritas))->toThrow(QueryException::class);
})->with([
    'dia_vencimento' => [['dia_vencimento' => 10]],
    'data_inicio' => [['data_inicio' => '2026-01-01']],
    'data_fim' => [['data_fim' => '2026-12-31']],
    'numero_parcelas' => [['numero_parcelas' => 3]],
    'data_primeira_parcela' => [['data_primeira_parcela' => '2026-09-01']],
]);

it('constraint: despesa mensal sem dia_vencimento ou sem data_inicio lança erro', function (array $sobrescritas) {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, array_merge([
        'descricao' => 'Aluguel',
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_vencimento' => null,
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01',
    ], $sobrescritas)))->toThrow(QueryException::class);
})->with([
    'sem dia_vencimento' => [['dia_vencimento' => null]],
    'sem data_inicio' => [['data_inicio' => null]],
]);

it('constraint: despesa mensal com data_vencimento preenchida lança erro', function () {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, [
        'descricao' => 'Aluguel',
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_vencimento' => '2026-08-10',
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01',
    ]))->toThrow(QueryException::class);
});

it('constraint: despesa mensal com data_inicio fora do primeiro dia do mês lança erro', function () {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, [
        'descricao' => 'Aluguel',
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_vencimento' => null,
        'dia_vencimento' => 10,
        'data_inicio' => '2026-01-15',
    ]))->toThrow(QueryException::class);
});

it('constraint: despesa parcelada sem forma_pagamento_id, numero_parcelas ou data_primeira_parcela lança erro', function (array $sobrescritas) {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, array_merge([
        'descricao' => 'Geladeira',
        'tipo_lancamento' => TipoLancamentoDespesa::Parcelada,
        'data_vencimento' => null,
        'forma_pagamento_id' => $contexto->cartaoCredito->id,
        'numero_parcelas' => 5,
        'data_primeira_parcela' => '2026-09-01',
    ], $sobrescritas)))->toThrow(QueryException::class);
})->with([
    'sem forma_pagamento_id' => [['forma_pagamento_id' => null]],
    'sem numero_parcelas' => [['numero_parcelas' => null]],
    'sem data_primeira_parcela' => [['data_primeira_parcela' => null]],
]);

it('constraint: despesa parcelada com dia_vencimento, data_inicio ou data_fim preenchidos lança erro', function (array $sobrescritas) {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, array_merge([
        'descricao' => 'Geladeira',
        'tipo_lancamento' => TipoLancamentoDespesa::Parcelada,
        'data_vencimento' => null,
        'forma_pagamento_id' => $contexto->cartaoCredito->id,
        'numero_parcelas' => 5,
        'data_primeira_parcela' => '2026-09-01',
    ], $sobrescritas)))->toThrow(QueryException::class);
})->with([
    'dia_vencimento' => [['dia_vencimento' => 10]],
    'data_inicio' => [['data_inicio' => '2026-01-01']],
    'data_fim' => [['data_fim' => '2026-12-31']],
]);

// DespesaScope

it('escopo: despesa individual só aparece pro dono', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    criarDespesaUnica($eu, $categoria, ['descricao' => 'Minha']);
    criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Do parceiro']);

    Auth::login($eu);

    expect(Despesa::query()->pluck('descricao')->all())->toBe(['Minha']);
});

it('escopo: despesa conjunta aparece pros dois usuários', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    criarDespesaUnica($eu, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta']);

    Auth::login($eu);
    expect(Despesa::query()->count())->toBe(1);

    Auth::login($parceiro);
    expect(Despesa::query()->count())->toBe(1);
});

it('escopo: sem usuário autenticado nenhuma despesa é retornada', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    criarDespesaUnica($eu, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta']);

    Auth::logout();

    expect(Despesa::query()->count())->toBe(0);
});

// Controller: store

it('cria despesa com sucesso quando o lançamento é único', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa criada com sucesso.']);

    $despesa = Despesa::sole();

    expect($despesa->descricao)->toBe('Mercado')
        ->and($despesa->valor)->toEqual(Money::fromCents(15000))
        ->and($despesa->tipo_lancamento)->toBe(TipoLancamentoDespesa::Unica)
        ->and($despesa->contexto)->toBe(ContextoDespesa::Individual)
        ->and($despesa->getRawOriginal('data_vencimento'))->toBe('2026-08-10')
        ->and($despesa->usuario_id)->toBe($eu->id);
});

it('cria despesa única já paga e a movimentação nasce na competência da data de vencimento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria, [
            'data_vencimento' => '2026-08-10',
            'paga' => true,
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
        ]))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa criada com sucesso.']);

    $despesa = Despesa::sole();

    expect($despesa->forma_pagamento_id)->toBeNull();

    $movimentacao = Movimentacao::sole();

    expect($movimentacao->despesa_id)->toBe($despesa->id)
        ->and($movimentacao->forma_pagamento_id)->toBe($forma->id)
        ->and($movimentacao->getRawOriginal('data'))->toBe('2026-08-05')
        ->and((string) $movimentacao->competencia)->toBe('2026-08');
});

it('falha ao criar despesa única paga sem forma_pagamento_id ou data_pagamento', function (string $campo) {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria, [
            'paga' => true,
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
            $campo => null,
        ]))
        ->assertSessionHasErrors($campo);

    expect(Despesa::count())->toBe(0);
})->with(['forma_pagamento_id', 'data_pagamento']);

it('falha ao criar despesa mensal ou parcelada com paga preenchido', function (string $tipo) {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');

    $payload = $tipo === 'mensal'
        ? payloadDespesaMensal($categoria, ['paga' => true])
        : payloadDespesaParcelada($categoria, $cartao, ['paga' => true]);

    $this->actingAs($eu)
        ->post(route('despesas.store'), $payload)
        ->assertSessionHasErrors('paga');

    expect(Despesa::count())->toBe(0);
})->with(['mensal', 'parcelada']);

it('cria despesa com sucesso quando o lançamento é mensal', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaMensal($categoria))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa criada com sucesso.']);

    $despesa = Despesa::sole();

    expect($despesa->tipo_lancamento)->toBe(TipoLancamentoDespesa::Mensal)
        ->and($despesa->dia_vencimento)->toBe(10)
        ->and($despesa->getRawOriginal('data_inicio'))->toBe('2026-01-01');
});

it('cria despesa com sucesso quando o lançamento é parcelada', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaParcelada($categoria, $cartao))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa criada com sucesso.']);

    $despesa = Despesa::sole();

    expect($despesa->tipo_lancamento)->toBe(TipoLancamentoDespesa::Parcelada)
        ->and($despesa->forma_pagamento_id)->toBe($cartao->id)
        ->and($despesa->numero_parcelas)->toBe(10);
});

it('falha ao criar despesa única sem data_vencimento', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria, ['data_vencimento' => null]))
        ->assertSessionHasErrors('data_vencimento');

    expect(Despesa::count())->toBe(0);
});

it('falha ao criar despesa mensal sem dia_vencimento ou data_inicio', function (string $campo) {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaMensal($categoria, [$campo => null]))
        ->assertSessionHasErrors($campo);

    expect(Despesa::count())->toBe(0);
})->with(['dia_vencimento', 'data_inicio']);

it('falha ao criar despesa mensal com data_inicio fora do primeiro dia do mês', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaMensal($categoria, ['data_inicio' => '2026-01-15']))
        ->assertSessionHasErrors('data_inicio');

    expect(Despesa::count())->toBe(0);
});

it('falha ao criar despesa mensal com data_fim anterior a data_inicio', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaMensal($categoria, [
            'data_inicio' => '2026-01-10',
            'data_fim' => '2026-01-01',
        ]))
        ->assertSessionHasErrors('data_fim');

    expect(Despesa::count())->toBe(0);
});

it('falha ao criar despesa única ou mensal com forma_pagamento_id preenchida', function (string $tipo) {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);

    $payload = $tipo === 'unica'
        ? payloadDespesaUnica($categoria, ['forma_pagamento_id' => $forma->id])
        : payloadDespesaMensal($categoria, ['forma_pagamento_id' => $forma->id]);

    $this->actingAs($eu)
        ->post(route('despesas.store'), $payload)
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Despesa::count())->toBe(0);
})->with(['unica', 'mensal']);

it('falha ao criar despesa parcelada sem forma_pagamento_id, numero_parcelas ou data_primeira_parcela', function (string $campo) {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaParcelada($categoria, $cartao, [$campo => null]))
        ->assertSessionHasErrors($campo);

    expect(Despesa::count())->toBe(0);
})->with(['forma_pagamento_id', 'numero_parcelas', 'data_primeira_parcela']);

it('falha ao criar despesa parcelada com forma de pagamento que não é crédito', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $debito = formaPagamentoDespesa($conta, 'debito', 'Débito');

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaParcelada($categoria, $debito))
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Despesa::count())->toBe(0);
});

it('falha ao criar despesa parcelada com forma de pagamento de conta do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $contaParceiro = contaDoUsuarioDespesa($parceiro);
    $categoria = categoriaDespesaDeTeste();
    $cartaoDoParceiro = formaPagamentoDespesa($contaParceiro, 'credito', 'Cartão do parceiro');

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaParcelada($categoria, $cartaoDoParceiro))
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Despesa::count())->toBe(0);
});

it('falha ao criar despesa com categoria_despesa_id inexistente', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria, ['categoria_despesa_id' => (string) Str::uuid()]))
        ->assertSessionHasErrors('categoria_despesa_id');

    expect(Despesa::count())->toBe(0);
});

// Controller: update

it('atualiza a própria despesa única', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaUnica($eu, $categoria, ['descricao' => 'Antigo']);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaUnica($categoria, ['descricao' => 'Novo'])))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa atualizada com sucesso.']);

    expect($despesa->fresh()?->descricao)->toBe('Novo');
});

it('rejeita tipo_lancamento no payload de atualização', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaUnica($eu, $categoria, ['descricao' => 'Antigo']);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadDespesaUnica($categoria, ['tipo_lancamento' => 'mensal']))
        ->assertSessionHasErrors('tipo_lancamento');

    expect($despesa->fresh()?->tipo_lancamento)->toBe(TipoLancamentoDespesa::Unica);
});

it('rejeita forma_pagamento_id no update geral de despesa única', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaUnica($eu, $categoria, ['descricao' => 'Antigo']);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaUnica($categoria, ['forma_pagamento_id' => $forma->id])))
        ->assertSessionHasErrors('forma_pagamento_id');
});

it('aceita trocar forma_pagamento_id no update geral de despesa parcelada', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartaoAntigo = formaPagamentoDespesa($conta, 'credito', 'Cartão antigo');
    $cartaoNovo = formaPagamentoDespesa($conta, 'credito', 'Cartão novo');
    $despesa = criarDespesaParcelada($eu, $categoria, $cartaoAntigo);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaParcelada($categoria, $cartaoNovo)))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa atualizada com sucesso.']);

    expect($despesa->fresh()?->forma_pagamento_id)->toBe($cartaoNovo->id);
});

it('rejeita trocar forma_pagamento_id no update de despesa parcelada por forma de pagamento do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $contaParceiro = contaDoUsuarioDespesa($parceiro);
    $categoria = categoriaDespesaDeTeste();
    $cartaoProprio = formaPagamentoDespesa($conta, 'credito', 'Cartão próprio');
    $cartaoDoParceiro = formaPagamentoDespesa($contaParceiro, 'credito', 'Cartão do parceiro');
    $despesa = criarDespesaParcelada($eu, $categoria, $cartaoProprio);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaParcelada($categoria, $cartaoDoParceiro)))
        ->assertSessionHasErrors('forma_pagamento_id');
});

it('bloqueia encerrar despesa mensal antes de uma competência já paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    criarMovimentacaoDespesa($despesa, $forma, '2026-06');

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaMensal($categoria, [
            'data_fim' => '2026-05-01',
        ])))
        ->assertSessionHasErrors('data_fim');

    expect($despesa->fresh()?->data_fim)->toBeNull();
});

it('permite encerrar despesa mensal depois da última competência paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    criarMovimentacaoDespesa($despesa, $forma, '2026-06');

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaMensal($categoria, [
            'data_fim' => '2026-07-01',
        ])))
        ->assertRedirect(route('despesas.index'));

    expect($despesa->fresh()?->getRawOriginal('data_fim'))->toBe('2026-07-01');
});

it('não alcança a despesa individual do parceiro', function (string $metodo, string $rota) {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Do parceiro']);

    $this->actingAs($eu)
        ->{$metodo}(route($rota, $despesa), payloadDespesaUnica($categoria))
        ->assertNotFound();

    $intacta = Despesa::withoutGlobalScope(DespesaScope::class)->find($despesa->id);

    expect($intacta?->descricao)->toBe('Do parceiro');
})->with([
    ['put', 'despesas.update'],
    ['delete', 'despesas.destroy'],
]);

it('parceiro edita e exclui despesa conjunta', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaUnica($eu, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta']);

    $this->actingAs($parceiro)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaUnica($categoria, [
            'contexto' => 'conjunta',
            'descricao' => 'Editada pelo parceiro',
        ])))
        ->assertRedirect(route('despesas.index'));

    expect($despesa->fresh()?->descricao)->toBe('Editada pelo parceiro');

    $this->actingAs($parceiro)
        ->delete(route('despesas.destroy', $despesa))
        ->assertRedirect(route('despesas.index'));

    expect(Despesa::withoutGlobalScope(DespesaScope::class)->find($despesa->id))->toBeNull();
});

it('exclui a própria despesa', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaUnica($eu, $categoria);

    $this->actingAs($eu)
        ->delete(route('despesas.destroy', $despesa))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa excluída com sucesso.']);

    expect(Despesa::withoutGlobalScope(DespesaScope::class)->find($despesa->id))->toBeNull();
});

it('DespesaService::listar retorna despesas visíveis ao usuário autenticado (individual própria + conjuntas)', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    criarDespesaUnica($eu, $categoria, ['descricao' => 'Minha']);
    criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Do parceiro']);
    criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta']);

    Auth::login($eu);

    $resultado = app(DespesaService::class)->listar();

    expect($resultado)->toHaveCount(2)
        ->and($resultado->pluck('descricao')->sort()->values()->all())->toBe(['Conjunta', 'Minha']);
});

it('exige autenticação nas rotas de despesa', function () {
    $this->get(route('despesas.index'))->assertRedirect(route('login'));
});

// Controller: marcarComoPaga

it('marca despesa única como paga na sua competência', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaUnica($eu, $categoria, ['data_vencimento' => '2026-08-10']);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-08',
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa marcada como paga.']);

    $movimentacao = Movimentacao::sole();

    expect($movimentacao->despesa_id)->toBe($despesa->id)
        ->and($movimentacao->forma_pagamento_id)->toBe($forma->id)
        ->and((string) $movimentacao->competencia)->toBe('2026-08')
        ->and($movimentacao->valor)->toEqual($despesa->valor->negated())
        ->and($movimentacao->getRawOriginal('data'))->toBe('2026-08-05');
});

it('marca despesa mensal como paga numa competência do período', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-03',
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-03-10',
        ])
        ->assertRedirect(route('despesas.index'));

    expect(Movimentacao::sole()->despesa_id)->toBe($despesa->id);
});

it('marca despesa parcelada como paga sem pedir forma_pagamento_id, usando a do cartão da compra', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');
    cartaoCreditoDespesa($cartao, 20);
    $despesa = criarDespesaParcelada($eu, $categoria, $cartao, ['data_primeira_parcela' => '2026-09-01']);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-09',
            'forma_pagamento_id' => '',
            'data_pagamento' => '2026-09-15',
        ])
        ->assertRedirect(route('despesas.index'));

    $movimentacao = Movimentacao::sole();

    expect($movimentacao->forma_pagamento_id)->toBe($cartao->id)
        ->and((string) $movimentacao->competencia)->toBe('2026-09');
});

it('rejeita enviar forma_pagamento_id ao marcar despesa parcelada como paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');
    cartaoCreditoDespesa($cartao, 20);
    $despesa = criarDespesaParcelada($eu, $categoria, $cartao, ['data_primeira_parcela' => '2026-09-01']);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-09',
            'forma_pagamento_id' => $cartao->id,
            'data_pagamento' => '2026-09-15',
        ])
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Movimentacao::count())->toBe(0);
});

it('rejeita marcar como paga competência sem ocorrência da despesa', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaUnica($eu, $categoria, ['data_vencimento' => '2026-08-10']);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-09',
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-09-05',
        ])
        ->assertSessionHasErrors('competencia');

    expect(Movimentacao::count())->toBe(0);
});

it('rejeita marcar como paga uma competência já paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    criarMovimentacaoDespesa($despesa, $forma, '2026-03');

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-03',
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-03-10',
        ])
        ->assertSessionHasErrors('competencia');

    expect(Movimentacao::count())->toBe(1);
});

it('rejeita marcar como paga com forma_pagamento_id de conta do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $contaParceiro = contaDoUsuarioDespesa($parceiro);
    $categoria = categoriaDespesaDeTeste();
    $formaDoParceiro = formaPagamentoDespesa($contaParceiro, 'debito', 'Débito do parceiro');
    $despesa = criarDespesaUnica($eu, $categoria, ['data_vencimento' => '2026-08-10']);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'competencia' => '2026-08',
            'forma_pagamento_id' => $formaDoParceiro->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Movimentacao::count())->toBe(0);
});

// Controller: desfazerPagamento

it('desfaz pagamento de uma competência paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    criarMovimentacaoDespesa($despesa, $forma, '2026-03');

    $this->actingAs($eu)
        ->patch(route('despesas.desfazer-pagamento', $despesa), ['competencia' => '2026-03'])
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

    expect(Movimentacao::count())->toBe(0);
});

it('rejeita desfazer pagamento de competência não paga', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = criarDespesaMensal($eu, $categoria);

    $this->actingAs($eu)
        ->patch(route('despesas.desfazer-pagamento', $despesa), ['competencia' => '2026-03'])
        ->assertSessionHasErrors('competencia');
});

// Service

it('DespesaService::estaPagaNaCompetencia reflete a existência da movimentação', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = criarDespesaMensal($eu, $categoria);

    $competencia = Competencia::deString('2026-03');
    $service = app(DespesaService::class);

    expect($service->estaPagaNaCompetencia($despesa, $competencia))->toBeFalse();

    criarMovimentacaoDespesa($despesa, $forma, '2026-03');

    expect($service->estaPagaNaCompetencia($despesa, $competencia))->toBeTrue();
});

// Index

it('resolve quem pagou uma despesa conjunta mesmo quando foi o parceiro', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create(['nome' => 'Parceiro']);
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    $contaParceiro = Conta::withoutGlobalScope(DonoScope::class)->create(['usuario_id' => $parceiro->id, 'nome' => 'Conta do parceiro']);
    $formaParceiro = formaPagamentoDespesa($contaParceiro);

    $conjunta = criarDespesaUnica($eu, $categoria, ['descricao' => 'Aluguel', 'contexto' => 'conjunta', 'data_vencimento' => '2026-08-10']);
    criarMovimentacaoDespesa($conjunta, $formaParceiro, '2026-08-01');

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Despesas/Index')
            ->where('ocorrencias.0.movimentacao.forma_pagamento.conta.usuario.nome', 'Parceiro')
        );

    Carbon::setTestNow();
});

it('resolve o cartão de uma despesa parcelada criada pelo parceiro', function () {
    Carbon::setTestNow('2026-09-15');

    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create(['nome' => 'Parceiro']);
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    $contaParceiro = Conta::withoutGlobalScope(DonoScope::class)->create(['usuario_id' => $parceiro->id, 'nome' => 'Conta do parceiro']);
    $cartaoParceiro = formaPagamentoDespesa($contaParceiro, 'credito', 'Cartão do parceiro');
    cartaoCreditoDespesa($cartaoParceiro);

    criarDespesaParcelada($eu, $categoria, $cartaoParceiro, ['contexto' => 'conjunta']);

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Despesas/Index')
            ->where('ocorrencias.0.despesa.forma_pagamento.conta.usuario.nome', 'Parceiro')
        );

    Carbon::setTestNow();
});

it('marca despesa única como vencida quando o vencimento já passou sem pagamento, e pendente quando ainda não chegou', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    criarDespesaUnica($eu, $categoria, ['descricao' => 'Vencida', 'contexto' => 'conjunta', 'data_vencimento' => '2026-08-10']);
    criarDespesaUnica($eu, $categoria, ['descricao' => 'A vencer', 'contexto' => 'conjunta', 'data_vencimento' => '2026-08-20']);

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Despesas/Index')
            ->has('ocorrencias', 2)
            ->where('ocorrencias.0.status', 'vencida')
            ->where('ocorrencias.1.status', 'pendente')
        );

    Carbon::setTestNow();
});

it('despesa única e mensal ficam pagas quando há movimentação na competência', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    $conta = contaDoUsuarioDespesa($eu);
    $forma = formaPagamentoDespesa($conta);

    $paga = criarDespesaUnica($eu, $categoria, ['contexto' => 'conjunta', 'data_vencimento' => '2026-08-05']);
    criarMovimentacaoDespesa($paga, $forma, '2026-08');

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Despesas/Index')
            ->where('ocorrencias.0.status', 'paga')
        );

    Carbon::setTestNow();
});

it('despesa parcelada nunca fica vencida, mesmo com o dia da parcela já passado no mês', function () {
    Carbon::setTestNow('2026-09-25');

    $eu = Usuario::factory()->create();
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    $conta = contaDoUsuarioDespesa($eu);
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');
    cartaoCreditoDespesa($cartao);

    criarDespesaParcelada($eu, $categoria, $cartao, ['contexto' => 'conjunta', 'data_primeira_parcela' => '2026-09-01']);

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Despesas/Index')
            ->where('ocorrencias.0.status', 'pendente')
        );

    Carbon::setTestNow();
});

it('filtra despesa por contexto: individual só traz despesa individual do usuário, conjunta só traz despesa conjunta', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    criarDespesaUnica($eu, $categoria, ['descricao' => 'Individual', 'contexto' => 'individual', 'data_vencimento' => '2026-08-10']);
    criarDespesaUnica($eu, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta', 'data_vencimento' => '2026-08-10']);

    $this->actingAs($eu)
        ->get(route('despesas.index', ['contexto' => 'individual']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('contexto', 'individual')
            ->has('ocorrencias', 1)
            ->where('ocorrencias.0.despesa.descricao', 'Individual')
        );

    $this->actingAs($eu)
        ->get(route('despesas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('contexto', 'conjunta')
            ->has('ocorrencias', 1)
            ->where('ocorrencias.0.despesa.descricao', 'Conjunta')
        );

    Carbon::setTestNow();
});

it('navega para outra competência via mes/ano e resolve a ocorrência do período informado', function () {
    $eu = Usuario::factory()->create();
    Auth::login($eu);

    $categoria = categoriaDespesaDeTeste();
    criarDespesaMensal($eu, $categoria, ['contexto' => 'conjunta', 'dia_vencimento' => 10, 'data_inicio' => '2026-01-01']);

    $this->actingAs($eu)
        ->get(route('despesas.index', ['ano' => 2026, 'mes' => 3]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('competencia', '2026-03')
            ->has('ocorrencias', 1)
            ->where('ocorrencias.0.competencia', '2026-03')
        );

    $this->actingAs($eu)
        ->get(route('despesas.index', ['ano' => 2025, 'mes' => 12]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->where('competencia', '2025-12')
            ->has('ocorrencias', 0)
        );
});

// Policy

it('nega a policy sobre despesa individual do parceiro e permite sobre despesa conjunta', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $individualDoParceiro = criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Do parceiro']);
    $conjunta = criarDespesaUnica($parceiro, $categoria, ['descricao' => 'Conjunta', 'contexto' => 'conjunta']);

    expect($eu->can('view', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('update', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('delete', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('view', $conjunta))->toBeTrue()
        ->and($eu->can('update', $conjunta))->toBeTrue()
        ->and($eu->can('delete', $conjunta))->toBeTrue();
});
