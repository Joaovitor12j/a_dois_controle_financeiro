<?php

use App\Models\CategoriaDespesa;
use App\Models\CategoriaRenda;
use App\Models\Usuario;
use Inertia\Testing\AssertableInertia;

it('lista as categorias de renda e de despesa na página de categorias', function () {
    $eu = Usuario::factory()->create();

    CategoriaRenda::create(['nome' => 'Salário', 'cor' => '#2F6F5E', 'icone' => 'home']);
    CategoriaDespesa::create(['nome' => 'Mercado', 'cor' => '#D9A441', 'icone' => 'utensils']);

    $this->actingAs($eu)
        ->get(route('categorias.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $pagina) => $pagina
            ->component('Categorias/Index')
            ->has('categoriasRenda', 1)
            ->has('categoriasDespesa', 1)
            ->where('categoriasRenda.0.nome', 'Salário')
            ->where('categoriasDespesa.0.nome', 'Mercado')
        );
});

it('exige autenticação na página de categorias', function () {
    $this->get(route('categorias.index'))->assertRedirect(route('login'));
});
