<?php

use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Models\CategoriaDespesa;
use App\Models\CategoriaRenda;
use App\Models\Despesa;
use App\Models\Usuario;

function criarCategoriaDespesa(string $nome = 'Mercado', string $icone = 'utensils'): CategoriaDespesa
{
    return CategoriaDespesa::create(['nome' => $nome, 'cor' => '#D9A441', 'icone' => $icone]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function payloadCategoriaDespesa(array $overrides = []): array
{
    return array_merge([
        'nome' => 'Mercado',
        'cor' => '#D9A441',
        'icone' => 'utensils',
    ], $overrides);
}

it('grava a categoria de despesa', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-despesa.store'), payloadCategoriaDespesa())
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de despesa criada com sucesso.']);

    $categoria = CategoriaDespesa::sole();

    expect($categoria->nome)->toBe('Mercado')
        ->and($categoria->cor)->toBe('#D9A441')
        ->and($categoria->icone)->toBe('utensils');
});

it('exige nome, cor e ícone ao criar categoria de despesa', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-despesa.store'), ['nome' => '', 'cor' => '', 'icone' => ''])
        ->assertSessionHasErrors(['nome', 'cor', 'icone']);
});

it('rejeita ícone fora do catálogo fixo na categoria de despesa', function () {
    $eu = Usuario::factory()->create();

    $this->actingAs($eu)
        ->post(route('categorias-despesa.store'), payloadCategoriaDespesa(['icone' => 'nao-existe']))
        ->assertSessionHasErrors('icone');
});

it('impede nome duplicado entre categorias de despesa', function () {
    $eu = Usuario::factory()->create();
    criarCategoriaDespesa('Mercado');

    $this->actingAs($eu)
        ->post(route('categorias-despesa.store'), payloadCategoriaDespesa(['nome' => 'Mercado']))
        ->assertSessionHasErrors('nome');

    expect(CategoriaDespesa::count())->toBe(1);
});

it('permite o mesmo nome em categoria de renda e de despesa', function () {
    $eu = Usuario::factory()->create();
    CategoriaRenda::create(['nome' => 'Presente', 'cor' => '#7B3F55', 'icone' => 'gift']);

    $this->actingAs($eu)
        ->post(route('categorias-despesa.store'), payloadCategoriaDespesa(['nome' => 'Presente']))
        ->assertSessionDoesntHaveErrors('nome');
});

it('atualiza a categoria de despesa', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaDespesa('Mercado');

    $this->actingAs($eu)
        ->put(route('categorias-despesa.update', $categoria), payloadCategoriaDespesa(['nome' => 'Supermercado', 'icone' => 'shopping-bag']))
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de despesa atualizada com sucesso.']);

    expect($categoria->fresh()?->nome)->toBe('Supermercado');
});

it('exclui a categoria de despesa sem uso', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaDespesa();

    $this->actingAs($eu)
        ->delete(route('categorias-despesa.destroy', $categoria))
        ->assertRedirect(route('categorias.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Categoria de despesa excluída com sucesso.']);

    expect(CategoriaDespesa::count())->toBe(0);
});

it('bloqueia a exclusão de categoria de despesa em uso', function () {
    $eu = Usuario::factory()->create();
    $categoria = criarCategoriaDespesa();

    Despesa::create([
        'usuario_id' => $eu->id,
        'contexto' => ContextoDespesa::Individual,
        'categoria_despesa_id' => $categoria->id,
        'descricao' => 'Mercado',
        'valor' => Money::fromCents(15000),
        'tipo_lancamento' => TipoLancamentoDespesa::Unica,
        'data_vencimento' => '2026-08-10',
    ]);

    $this->actingAs($eu)
        ->delete(route('categorias-despesa.destroy', $categoria))
        ->assertRedirect()
        ->assertSessionHasErrors('categoria');

    expect(CategoriaDespesa::find($categoria->id))->not->toBeNull();
});

it('exige autenticação nas rotas de categoria de despesa', function () {
    $categoria = criarCategoriaDespesa();

    $this->post(route('categorias-despesa.store'), payloadCategoriaDespesa())->assertRedirect(route('login'));
    $this->put(route('categorias-despesa.update', $categoria), payloadCategoriaDespesa())->assertRedirect(route('login'));
    $this->delete(route('categorias-despesa.destroy', $categoria))->assertRedirect(route('login'));
});
