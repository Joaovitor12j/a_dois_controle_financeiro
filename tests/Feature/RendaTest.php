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

it('lista apenas as rendas do usuário autenticado', function () {
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
            ->has('rendas', 1)
            ->where('rendas.0.descricao', 'Minha renda')
        );
});
