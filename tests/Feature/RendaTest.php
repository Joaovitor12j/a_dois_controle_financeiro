<?php

use App\Domain\ValueObjects\Money;
use App\Enums\TipoRecorrencia;
use App\Models\CategoriaRenda;
use App\Models\Conta;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Renda;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function novoContextoRenda(): object
{
    $usuario = Usuario::factory()->create();
    Auth::login($usuario);

    $conta = Conta::create(['usuario_id' => $usuario->id, 'nome' => 'Conta Principal']);
    $categoria = CategoriaRenda::create(['nome' => 'Salário', 'cor' => '#4caf50', 'icone' => 'wallet']);

    return (object) compact('usuario', 'conta', 'categoria');
}

/** @param array<string, mixed> $overrides */
function rendaValida(object $contexto, array $overrides = []): Renda
{
    return Renda::create(array_merge([
        'usuario_id' => $contexto->usuario->id,
        'conta_id' => $contexto->conta->id,
        'categoria_renda_id' => $contexto->categoria->id,
        'descricao' => 'Salário',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-05',
    ], $overrides));
}

function contaDoUsuarioRenda(Usuario $usuario): Conta
{
    return Conta::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $usuario->id,
        'nome' => 'Nubank',
    ]);
}

function categoriaRendaDeTeste(): CategoriaRenda
{
    return CategoriaRenda::create(['nome' => 'Salário', 'cor' => '#4caf50', 'icone' => 'wallet']);
}

function formaPagamentoRenda(Conta $conta, string $nome = 'Conta corrente', bool $recebeRenda = true): FormaPagamento
{
    return FormaPagamento::create([
        'conta_id' => $conta->id,
        'nome' => $nome,
        'tipo' => 'pix',
        'recebe_renda' => $recebeRenda,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadRendaUnica(Conta $conta, CategoriaRenda $categoria, array $overrides = []): array
{
    return array_merge([
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Salário',
        'valor' => 500000,
        'tipo_recorrencia' => 'unica',
        'data_recebimento' => '2026-08-05',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadRendaMensal(Conta $conta, CategoriaRenda $categoria, array $overrides = []): array
{
    return array_merge([
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Salário mensal',
        'valor' => 500000,
        'tipo_recorrencia' => 'mensal',
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ], $overrides);
}

// Model

it('faz cast de valor para o Value Object Money', function () {
    $renda = rendaValida(novoContextoRenda(), ['valor' => Money::fromCents(123456)]);

    expect($renda->fresh()?->valor)->toEqual(Money::fromCents(123456));
});

it('faz cast de tipo_recorrencia para o enum TipoRecorrencia', function () {
    $renda = rendaValida(novoContextoRenda(), [
        'tipo_recorrencia' => 'mensal',
        'data_recebimento' => null,
        'dia_recebimento' => 10,
        'data_inicio' => '2026-01-01',
    ]);

    expect($renda->fresh()?->tipo_recorrencia)->toBe(TipoRecorrencia::Mensal);
});

it('relações belongsTo retornam os models corretos', function () {
    $contexto = novoContextoRenda();
    $renda = rendaValida($contexto);

    expect($renda->usuario->is($contexto->usuario))->toBeTrue()
        ->and($renda->conta->is($contexto->conta))->toBeTrue()
        ->and($renda->categoriaRenda->is($contexto->categoria))->toBeTrue();
});

it('relação hasMany movimentacoes retorna as movimentações da renda', function () {
    $contexto = novoContextoRenda();
    $renda = rendaValida($contexto);

    $formaPagamento = FormaPagamento::create([
        'conta_id' => $contexto->conta->id,
        'nome' => 'Pix',
        'tipo' => 'pix',
    ]);

    $movimentacao = Movimentacao::forceCreate([
        'forma_pagamento_id' => $formaPagamento->id,
        'valor' => 50000,
        'data' => '2026-08-05',
        'renda_id' => $renda->id,
        'competencia' => '2026-08',
    ]);

    expect($renda->movimentacoes->pluck('id')->all())->toBe([$movimentacao->id]);
});

it('constraint: renda única com dia_recebimento, data_inicio ou data_fim preenchidos lança erro', function () {
    $contexto = novoContextoRenda();

    expect(fn () => rendaValida($contexto, [
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-05',
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
        'data_fim' => '2026-12-31',
    ]))->toThrow(QueryException::class);
});

it('constraint: renda única sem data_recebimento lança erro', function () {
    $contexto = novoContextoRenda();

    expect(fn () => rendaValida($contexto, [
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => null,
    ]))->toThrow(QueryException::class);
});

it('constraint: renda mensal sem dia_recebimento ou sem data_inicio lança erro', function (array $sobrescritas) {
    $contexto = novoContextoRenda();

    expect(fn () => rendaValida($contexto, array_merge([
        'tipo_recorrencia' => TipoRecorrencia::Mensal,
        'data_recebimento' => null,
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ], $sobrescritas)))->toThrow(QueryException::class);
})->with([
    'sem dia_recebimento' => [['dia_recebimento' => null]],
    'sem data_inicio' => [['data_inicio' => null]],
]);

it('constraint: renda mensal com data_recebimento preenchido lança erro', function () {
    $contexto = novoContextoRenda();

    expect(fn () => rendaValida($contexto, [
        'tipo_recorrencia' => TipoRecorrencia::Mensal,
        'data_recebimento' => '2026-08-05',
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ]))->toThrow(QueryException::class);
});

// Controller

it('cria renda com sucesso quando a recorrência é única', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaUnica($conta, $categoria))
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Renda criada com sucesso.']);

    $renda = Renda::sole();

    expect($renda->descricao)->toBe('Salário')
        ->and($renda->valor)->toEqual(Money::fromCents(500000))
        ->and($renda->tipo_recorrencia)->toBe(TipoRecorrencia::Unica)
        ->and($renda->getRawOriginal('data_recebimento'))->toBe('2026-08-05')
        ->and($renda->usuario_id)->toBe($eu->id);
});

it('cria renda com sucesso quando a recorrência é mensal', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaMensal($conta, $categoria))
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Renda criada com sucesso.']);

    $renda = Renda::sole();

    expect($renda->tipo_recorrencia)->toBe(TipoRecorrencia::Mensal)
        ->and($renda->dia_recebimento)->toBe(5)
        ->and($renda->getRawOriginal('data_inicio'))->toBe('2026-01-01')
        ->and($renda->usuario_id)->toBe($eu->id);
});

it('falha ao criar renda única sem data_recebimento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaUnica($conta, $categoria, ['data_recebimento' => null]))
        ->assertSessionHasErrors('data_recebimento');

    expect(Renda::count())->toBe(0);
});

it('falha ao criar renda mensal sem dia_recebimento', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaMensal($conta, $categoria, ['dia_recebimento' => null]))
        ->assertSessionHasErrors('dia_recebimento');

    expect(Renda::count())->toBe(0);
});

it('falha ao criar renda mensal sem data_inicio', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaMensal($conta, $categoria, ['data_inicio' => null]))
        ->assertSessionHasErrors('data_inicio');

    expect(Renda::count())->toBe(0);
});

// dia_recebimento só tem <InputError> no ramo "mensal" do formulário — este erro cai num campo
// escondido quando a recorrência é "única", motivo pelo qual FormErrorSummary existe (ADR 0008).
it('falha ao criar renda única com dia_recebimento preenchido', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaUnica($conta, $categoria, ['dia_recebimento' => 10]))
        ->assertSessionHasErrors('dia_recebimento');

    expect(Renda::count())->toBe(0);
});

it('falha ao criar renda mensal com data_recebimento preenchido', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaMensal($conta, $categoria, ['data_recebimento' => '2026-08-05']))
        ->assertSessionHasErrors('data_recebimento');

    expect(Renda::count())->toBe(0);
});

it('falha ao criar renda com data_fim anterior a data_inicio', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaMensal($conta, $categoria, [
            'data_inicio' => '2026-01-10',
            'data_fim' => '2026-01-01',
        ]))
        ->assertSessionHasErrors('data_fim');

    expect(Renda::count())->toBe(0);
});

it('falha ao criar renda com conta_id ou categoria_renda_id inexistentes', function (string $campo) {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();

    $this->actingAs($eu)
        ->post(route('rendas.store'), payloadRendaUnica($conta, $categoria, [$campo => (string) Str::uuid()]))
        ->assertSessionHasErrors($campo);

    expect(Renda::count())->toBe(0);
})->with(['conta_id', 'categoria_renda_id']);

it('atualiza a própria renda', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $renda = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $eu->id,
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Antigo',
        'valor' => 100000,
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-01',
    ]);

    $this->actingAs($eu)
        ->put(route('rendas.update', $renda), payloadRendaUnica($conta, $categoria, ['descricao' => 'Novo']))
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Renda atualizada com sucesso.']);

    expect($renda->fresh()?->descricao)->toBe('Novo');
});

it('não alcança a renda do parceiro', function (string $metodo, string $rota) {
    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($parceiro);
    $categoria = categoriaRendaDeTeste();
    $renda = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $parceiro->id,
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Do parceiro',
        'valor' => 100000,
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-01',
    ]);

    $this->actingAs($eu)
        ->{$metodo}(route($rota, $renda), payloadRendaUnica($conta, $categoria))
        ->assertNotFound();

    $intacta = Renda::withoutGlobalScope(DonoScope::class)->find($renda->id);

    expect($intacta?->descricao)->toBe('Do parceiro');
})->with([
    ['put', 'rendas.update'],
    ['delete', 'rendas.destroy'],
]);

it('exclui a própria renda', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $renda = Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $eu->id,
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Salário',
        'valor' => 100000,
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-01',
    ]);

    $this->actingAs($eu)
        ->delete(route('rendas.destroy', $renda))
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Renda excluída com sucesso.']);

    expect(Renda::withoutGlobalScope(DonoScope::class)->find($renda->id))->toBeNull();
});

it('lista apenas as ocorrências das rendas do usuário autenticado na competência atual', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $parceiro = Usuario::factory()->create();
    $categoria = categoriaRendaDeTeste();

    $minhaConta = contaDoUsuarioRenda($eu);
    $contaParceiro = contaDoUsuarioRenda($parceiro);

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $eu->id,
        'conta_id' => $minhaConta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Minha renda',
        'valor' => 100000,
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-01',
    ]);

    Renda::withoutGlobalScope(DonoScope::class)->create([
        'usuario_id' => $parceiro->id,
        'conta_id' => $contaParceiro->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Renda do parceiro',
        'valor' => 100000,
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-01',
    ]);

    $this->actingAs($eu)
        ->get(route('rendas.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Rendas/Index')
            ->has('ocorrencias', 1)
            ->where('ocorrencias.0.renda.descricao', 'Minha renda')
            ->where('ocorrencias.0.recebida', false)
        );

    Carbon::setTestNow();
});

// Controller: marcarComoRecebida

it('marca renda como recebida derivando a única forma de pagamento elegível da conta', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $forma = formaPagamentoRenda($conta);
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'data_recebimento' => '2026-08-05',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'forma_pagamento_id' => '',
            'data_recebimento' => '2026-08-05',
            'valor' => 500000,
        ])
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Renda marcada como recebida.']);

    $movimentacao = Movimentacao::sole();

    expect($movimentacao->renda_id)->toBe($renda->id)
        ->and($movimentacao->forma_pagamento_id)->toBe($forma->id)
        ->and((string) $movimentacao->competencia)->toBe('2026-08')
        ->and($movimentacao->valor)->toEqual($renda->valor);

    Carbon::setTestNow();
});

it('aceita valor recebido diferente do valor programado da renda', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    formaPagamentoRenda($conta);
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'valor' => Money::fromCents(500000),
        'data_recebimento' => '2026-08-05',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'forma_pagamento_id' => '',
            'data_recebimento' => '2026-08-05',
            'valor' => 480000,
        ])
        ->assertRedirect(route('rendas.index'));

    expect(Movimentacao::sole()->valor)->toEqual(Money::fromCents(480000))
        ->and($renda->fresh()?->valor)->toEqual(Money::fromCents(500000));

    Carbon::setTestNow();
});

it('pede forma_pagamento_id ao marcar como recebida quando a conta tem mais de uma forma elegível', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    formaPagamentoRenda($conta, 'Conta corrente');
    $valeAlimentacao = formaPagamentoRenda($conta, 'Vale-alimentação');
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'data_recebimento' => '2026-08-05',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'data_recebimento' => '2026-08-05',
            'valor' => 500000,
        ])
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Movimentacao::count())->toBe(0);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'forma_pagamento_id' => $valeAlimentacao->id,
            'data_recebimento' => '2026-08-05',
            'valor' => 500000,
        ])
        ->assertRedirect(route('rendas.index'));

    expect(Movimentacao::sole()->forma_pagamento_id)->toBe($valeAlimentacao->id);

    Carbon::setTestNow();
});

it('rejeita marcar como recebida quando nenhuma forma de pagamento da conta recebe renda', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    formaPagamentoRenda($conta, 'Conta corrente', recebeRenda: false);
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'data_recebimento' => '2026-08-05',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'data_recebimento' => '2026-08-05',
            'valor' => 500000,
        ])
        ->assertSessionHasErrors('forma_pagamento_id');

    expect(Movimentacao::count())->toBe(0);

    Carbon::setTestNow();
});

it('rejeita marcar renda como recebida numa competência já recebida', function () {
    Carbon::setTestNow('2026-08-15');

    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $forma = formaPagamentoRenda($conta);
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'tipo_recorrencia' => TipoRecorrencia::Mensal,
        'data_recebimento' => null,
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ]);

    Movimentacao::forceCreate([
        'forma_pagamento_id' => $forma->id,
        'valor' => 500000,
        'data' => '2026-08-05',
        'renda_id' => $renda->id,
        'competencia' => '2026-08',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.marcar-como-recebida', $renda), [
            'competencia' => '2026-08',
            'data_recebimento' => '2026-08-05',
            'valor' => 500000,
        ])
        ->assertSessionHasErrors('competencia');

    expect(Movimentacao::count())->toBe(1);

    Carbon::setTestNow();
});

// Controller: desfazerRecebimento

it('desfaz recebimento de uma competência recebida', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $forma = formaPagamentoRenda($conta);
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'tipo_recorrencia' => TipoRecorrencia::Mensal,
        'data_recebimento' => null,
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ]);

    Movimentacao::forceCreate([
        'forma_pagamento_id' => $forma->id,
        'valor' => 500000,
        'data' => '2026-08-05',
        'renda_id' => $renda->id,
        'competencia' => '2026-08',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.desfazer-recebimento', $renda), ['competencia' => '2026-08'])
        ->assertRedirect(route('rendas.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Recebimento desfeito.']);

    expect(Movimentacao::count())->toBe(0);
});

it('rejeita desfazer recebimento de competência não recebida', function () {
    $eu = Usuario::factory()->create();
    $conta = contaDoUsuarioRenda($eu);
    $categoria = categoriaRendaDeTeste();
    $renda = rendaValida((object) ['usuario' => $eu, 'conta' => $conta, 'categoria' => $categoria], [
        'tipo_recorrencia' => TipoRecorrencia::Mensal,
        'data_recebimento' => null,
        'dia_recebimento' => 5,
        'data_inicio' => '2026-01-01',
    ]);

    $this->actingAs($eu)
        ->patch(route('rendas.desfazer-recebimento', $renda), ['competencia' => '2026-08'])
        ->assertSessionHasErrors('competencia');
});
