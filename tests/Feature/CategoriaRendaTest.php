<?php

use App\Domain\ValueObjects\Money;
use App\Enums\TipoRecorrencia;
use App\Models\CategoriaRenda;
use App\Models\Conta;
use App\Models\Renda;
use App\Models\Usuario;

function criarCategoriaRenda(string $nome = 'Salário', string $icone = 'home'): CategoriaRenda
{
    return CategoriaRenda::create(['nome' => $nome, 'cor' => '#2F6F5E', 'icone' => $icone]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadCategoriaRenda(array $overrides = []): array
{
    return array_merge([
        'nome' => 'Salário',
        'cor' => '#2F6F5E',
        'icone' => 'home',
    ], $overrides);
}

it('grava a categoria de renda', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-renda.store'), payloadCategoriaRenda())
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de renda criada com sucesso.']);

    $categoria = CategoriaRenda::sole();

    expect($categoria->nome)->toBe('Salário')
        ->and($categoria->cor)->toBe('#2F6F5E')
        ->and($categoria->icone)->toBe('home');
});

it('exige nome, cor e ícone ao criar categoria de renda', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-renda.store'), ['nome' => '', 'cor' => '', 'icone' => ''])
        ->assertSessionHasErrors(['nome', 'cor', 'icone']);
});

it('rejeita ícone fora do catálogo fixo', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-renda.store'), payloadCategoriaRenda(['icone' => 'nao-existe']))
        ->assertSessionHasErrors('icone');
});

it('rejeita cor fora do formato hexadecimal', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-renda.store'), payloadCategoriaRenda(['cor' => 'verde']))
        ->assertSessionHasErrors('cor');
});

it('impede nome duplicado entre categorias de renda', function () {
    $eu = Usuario::factory()->create();
    criarCategoriaRenda('Salário');

    $this->actingAs($eu)
        ->post(route('categorias-renda.store'), payloadCategoriaRenda(['nome' => 'Salário']))
        ->assertSessionHasErrors('nome');

    expect(CategoriaRenda::count())->toBe(1);
});

it('atualiza a categoria de renda', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaRenda('Salário');

    $this->actingAs($eu)
        ->put(route('categorias-renda.update', $categoria), payloadCategoriaRenda(['nome' => 'Salário CLT', 'icone' => 'plane']))
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de renda atualizada com sucesso.']);

    expect($categoria->fresh()?->nome)->toBe('Salário CLT');
});

it('não bloqueia a unicidade contra o próprio registro na edição', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaRenda('Salário');

    $this->actingAs($eu)
        ->put(route('categorias-renda.update', $categoria), payloadCategoriaRenda(['nome' => 'Salário']))
        ->assertSessionDoesntHaveErrors('nome');
});

it('exclui a categoria de renda sem uso', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaRenda();

    $this->actingAs($eu)
        ->delete(route('categorias-renda.destroy', $categoria))
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de renda excluída com sucesso.']);

    expect(CategoriaRenda::count())->toBe(0);
});

it('bloqueia a exclusão de categoria de renda em uso', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaRenda();

    $conta = Conta::create(['usuario_id' => $eu->id, 'nome' => 'Conta Principal']);
    Renda::create([
        'usuario_id' => $eu->id,
        'conta_id' => $conta->id,
        'categoria_renda_id' => $categoria->id,
        'descricao' => 'Salário',
        'valor' => Money::fromCents(500000),
        'tipo_recorrencia' => TipoRecorrencia::Unica,
        'data_recebimento' => '2026-08-05',
    ]);

    $this->actingAs($eu)
        ->delete(route('categorias-renda.destroy', $categoria))
        ->assertRedirect()
        ->assertSessionHasErrors('categoria');

    expect(CategoriaRenda::find($categoria->id))->not->toBeNull();
});

it('exige autenticação nas rotas de categoria de renda', function () {
    $categoria = criarCategoriaRenda();

    $this->post(route('categorias-renda.store'), payloadCategoriaRenda())->assertRedirect(route('login'));
    $this->put(route('categorias-renda.update', $categoria), payloadCategoriaRenda())->assertRedirect(route('login'));
    $this->delete(route('categorias-renda.destroy', $categoria))->assertRedirect(route('login'));
});
