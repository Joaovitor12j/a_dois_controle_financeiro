<?php

use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\CategoriaDespesa;
use App\Models\Conta;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Models\Scopes\DespesaScope;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use App\Services\Financeiro\DespesaService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        'paga' => false,
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
    $despesa = despesaValida($contexto, ['forma_pagamento_id' => $contexto->formaPagamento->id]);

    expect($despesa->usuario->is($contexto->usuario))->toBeTrue()
        ->and($despesa->formaPagamento->is($contexto->formaPagamento))->toBeTrue()
        ->and($despesa->categoriaDespesa->is($contexto->categoria))->toBeTrue();
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

// Constraints: despesas_pagamento_check (corrigida)

it('constraint: despesa única não paga com data_pagamento preenchida lança erro', function () {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, [
        'paga' => false,
        'data_pagamento' => '2026-08-10',
    ]))->toThrow(QueryException::class);
});

it('constraint: despesa única paga sem data_pagamento ou sem forma_pagamento_id lança erro', function (array $sobrescritas) {
    $contexto = novoContextoDespesa();

    expect(fn () => despesaValida($contexto, array_merge([
        'paga' => true,
        'data_pagamento' => '2026-08-10',
        'forma_pagamento_id' => $contexto->formaPagamento->id,
    ], $sobrescritas)))->toThrow(QueryException::class);
})->with([
    'sem data_pagamento' => [['data_pagamento' => null]],
    'sem forma_pagamento_id' => [['forma_pagamento_id' => null]],
]);

it('constraint corrigida: despesa única não paga aceita forma_pagamento_id informada com antecedência', function () {
    $contexto = novoContextoDespesa();

    $despesa = despesaValida($contexto, [
        'paga' => false,
        'forma_pagamento_id' => $contexto->formaPagamento->id,
    ]);

    expect($despesa->fresh()?->forma_pagamento_id)->toBe($contexto->formaPagamento->id)
        ->and($despesa->fresh()?->paga)->toBeFalse();
});

it('constraint: despesa única paga com data_pagamento e forma_pagamento_id preenchidos é aceita', function () {
    $contexto = novoContextoDespesa();

    $despesa = despesaValida($contexto, [
        'paga' => true,
        'data_pagamento' => '2026-08-10',
        'forma_pagamento_id' => $contexto->formaPagamento->id,
    ]);

    expect($despesa->fresh()?->paga)->toBeTrue();
});

// DespesaScope

it('escopo: despesa individual só aparece pro dono', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Minha', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-10', 'paga' => false,
    ]);
    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Do parceiro', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-10', 'paga' => false,
    ]);

    Auth::login($eu);

    expect(Despesa::query()->pluck('descricao')->all())->toBe(['Minha']);
});

it('escopo: despesa conjunta aparece pros dois usuários', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'conjunta', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Conjunta', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-10', 'paga' => false,
    ]);

    Auth::login($eu);
    expect(Despesa::query()->count())->toBe(1);

    Auth::login($parceiro);
    expect(Despesa::query()->count())->toBe(1);
});

it('escopo: sem usuário autenticado nenhuma despesa é retornada', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'conjunta', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Conjunta', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-10', 'paga' => false,
    ]);

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
        ->and($despesa->getRawOriginal('data_inicio'))->toBe('2026-01-01')
        ->and($despesa->paga)->toBeFalse();
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

it('cria despesa já paga inline quando o lançamento é único', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaUnica($categoria, [
            'paga' => true,
            'data_pagamento' => '2026-08-10',
            'forma_pagamento_id' => $forma->id,
        ]))
        ->assertRedirect(route('despesas.index'));

    $despesa = Despesa::sole();

    expect($despesa->paga)->toBeTrue()
        ->and($despesa->forma_pagamento_id)->toBe($forma->id);
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

it('falha ao criar despesa mensal com forma_pagamento_id preenchida', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);

    $this->actingAs($eu)
        ->post(route('despesas.store'), payloadDespesaMensal($categoria, ['forma_pagamento_id' => $forma->id]))
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Despesa::count())->toBe(0);
});

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

it('atualiza a própria despesa única sem alterar pagamento', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Antigo', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaUnica($categoria, ['descricao' => 'Novo'])))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa atualizada com sucesso.']);

    expect($despesa->fresh()?->descricao)->toBe('Novo');
});

it('rejeita tipo_lancamento no payload de atualização', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Antigo', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadDespesaUnica($categoria, ['tipo_lancamento' => 'mensal']))
        ->assertSessionHasErrors('tipo_lancamento');

    expect($despesa->fresh()?->tipo_lancamento)->toBe(TipoLancamentoDespesa::Unica);
});

it('ignora paga e data_pagamento enviados no update geral (não fazem parte do payload)', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Antigo', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaUnica($categoria, [
            'paga' => true,
            'data_pagamento' => '2026-08-10',
        ])))
        ->assertRedirect(route('despesas.index'));

    $despesa->refresh();

    expect($despesa->paga)->toBeFalse()
        ->and($despesa->data_pagamento)->toBeNull();
});

it('rejeita forma_pagamento_id no update geral de despesa única', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Antigo', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

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
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Geladeira', 'valor' => 50000, 'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $cartaoAntigo->id, 'numero_parcelas' => 10, 'data_primeira_parcela' => '2026-09-01',
    ]);

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
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Geladeira', 'valor' => 50000, 'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $cartaoProprio->id, 'numero_parcelas' => 10, 'data_primeira_parcela' => '2026-09-01',
    ]);

    $this->actingAs($eu)
        ->put(route('despesas.update', $despesa), payloadAtualizacaoDespesa(payloadDespesaParcelada($categoria, $cartaoDoParceiro)))
        ->assertSessionHasErrors('forma_pagamento_id');
});

it('não alcança a despesa individual do parceiro', function (string $metodo, string $rota) {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Do parceiro', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

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
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'conjunta', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Conjunta', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

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
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->delete(route('despesas.destroy', $despesa))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa excluída com sucesso.']);

    expect(Despesa::withoutGlobalScope(DespesaScope::class)->find($despesa->id))->toBeNull();
});

// index() ainda não tem view TSX (frontend fora do escopo desta parte) — a listagem em si já é
// coberta pelos testes de DespesaScope acima; aqui testamos DespesaService::listar diretamente.
it('DespesaService::listar retorna despesas visíveis ao usuário autenticado (individual própria + conjuntas)', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Minha', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);
    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Do parceiro', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);
    Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'conjunta', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Conjunta', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    Auth::login($eu);

    $resultado = (new DespesaService)->listar();

    expect($resultado)->toHaveCount(2)
        ->and($resultado->pluck('descricao')->sort()->values()->all())->toBe(['Conjunta', 'Minha']);
});

it('exige autenticação nas rotas de despesa', function () {
    $this->get(route('despesas.index'))->assertRedirect(route('login'));
});

// Controller: marcarComoPaga

it('marca despesa única não paga como paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Despesa marcada como paga.']);

    $despesa->refresh();

    expect($despesa->paga)->toBeTrue()
        ->and($despesa->forma_pagamento_id)->toBe($forma->id)
        ->and($despesa->getRawOriginal('data_pagamento'))->toBe('2026-08-05');
});

it('rejeita marcar como paga despesa mensal', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Aluguel', 'valor' => 100000, 'tipo_lancamento' => 'mensal',
        'dia_vencimento' => 10, 'data_inicio' => '2026-01-01',
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertSessionHasErrors('tipo_lancamento');

    expect($despesa->fresh()?->paga)->toBeFalse();
});

it('rejeita marcar como paga despesa parcelada', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $cartao = formaPagamentoDespesa($conta, 'credito', 'Cartão');
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Geladeira', 'valor' => 50000, 'tipo_lancamento' => 'parcelada',
        'forma_pagamento_id' => $cartao->id, 'numero_parcelas' => 10, 'data_primeira_parcela' => '2026-09-01',
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'forma_pagamento_id' => $cartao->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertSessionHasErrors('tipo_lancamento');

    expect($despesa->fresh()?->paga)->toBeFalse();
});

it('rejeita marcar como paga despesa já paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01',
        'paga' => true, 'forma_pagamento_id' => $forma->id, 'data_pagamento' => '2026-08-02',
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'forma_pagamento_id' => $forma->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertSessionHasErrors('paga');
});

it('rejeita marcar como paga com forma_pagamento_id de conta do parceiro', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $contaParceiro = contaDoUsuarioDespesa($parceiro);
    $categoria = categoriaDespesaDeTeste();
    $formaDoParceiro = formaPagamentoDespesa($contaParceiro, 'debito', 'Débito do parceiro');
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.marcar-como-paga', $despesa), [
            'forma_pagamento_id' => $formaDoParceiro->id,
            'data_pagamento' => '2026-08-05',
        ])
        ->assertSessionHasErrors('forma_pagamento_id');

    expect($despesa->fresh()?->paga)->toBeFalse();
});

// Controller: desfazerPagamento

it('desfaz pagamento de despesa única paga', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioDespesa($eu);
    $categoria = categoriaDespesaDeTeste();
    $forma = formaPagamentoDespesa($conta);
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01',
        'paga' => true, 'forma_pagamento_id' => $forma->id, 'data_pagamento' => '2026-08-02',
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.desfazer-pagamento', $despesa))
        ->assertRedirect(route('despesas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

    $despesa->refresh();

    expect($despesa->paga)->toBeFalse()
        ->and($despesa->forma_pagamento_id)->toBeNull()
        ->and($despesa->data_pagamento)->toBeNull();
});

it('rejeita desfazer pagamento de despesa não paga', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.desfazer-pagamento', $despesa))
        ->assertSessionHasErrors('paga');
});

it('rejeita desfazer pagamento de despesa mensal', function () {
    $eu = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();
    $despesa = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $eu->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Aluguel', 'valor' => 100000, 'tipo_lancamento' => 'mensal',
        'dia_vencimento' => 10, 'data_inicio' => '2026-01-01',
    ]);

    $this->actingAs($eu)
        ->patch(route('despesas.desfazer-pagamento', $despesa))
        ->assertSessionHasErrors('tipo_lancamento');
});

// Policy

it('nega a policy sobre despesa individual do parceiro e permite sobre despesa conjunta', function () {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaDespesaDeTeste();

    $individualDoParceiro = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'individual', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Do parceiro', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    $conjunta = Despesa::withoutGlobalScope(DespesaScope::class)->create([
        'usuario_id' => $parceiro->id, 'contexto' => 'conjunta', 'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Conjunta', 'valor' => 10000, 'tipo_lancamento' => 'unica', 'data_vencimento' => '2026-08-01', 'paga' => false,
    ]);

    expect($eu->can('view', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('update', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('delete', $individualDoParceiro))->toBeFalse()
        ->and($eu->can('view', $conjunta))->toBeTrue()
        ->and($eu->can('update', $conjunta))->toBeTrue()
        ->and($eu->can('delete', $conjunta))->toBeTrue();
});
