<?php

use App\Models\CategoriaRenda;
use Database\Seeders\CategoriaRendaSeeder;

test('seeder cria as categorias de renda configuradas', function () {
    $this->seed(CategoriaRendaSeeder::class);

    $categorias = CategoriaRenda::query()->get();

    expect($categorias)->toHaveCount(6);

    foreach ($categorias as $categoria) {
        expect($categoria->nome)->not->toBe('')
            ->and($categoria->cor)->toMatch('/^#[0-9A-Fa-f]{6}$/')
            ->and($categoria->icone)->not->toBe('');
    }

    expect($categorias->pluck('nome')->sort()->values()->all())->toBe([
        'Freelance',
        'Investimentos',
        'Outros',
        'Presente',
        'Reembolso',
        'Salário',
    ]);
});

test('seeder é idempotente', function () {
    $this->seed(CategoriaRendaSeeder::class);
    $this->seed(CategoriaRendaSeeder::class);

    expect(CategoriaRenda::query()->count())->toBe(6);
});

test('seeder não sobrescreve categoria editada no painel', function () {
    $this->seed(CategoriaRendaSeeder::class);

    /** @var CategoriaRenda $categoria */
    $categoria = CategoriaRenda::query()->where('nome', 'Salário')->firstOrFail();

    $categoria->update(['nome' => 'Renda fixa', 'cor' => '#000000']);

    $this->seed(CategoriaRendaSeeder::class);

    $categoria->refresh();

    expect(CategoriaRenda::query()->count())->toBe(6)
        ->and($categoria->nome)->toBe('Renda fixa')
        ->and($categoria->cor)->toBe('#000000');
});
