<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaDespesa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class CategoriaDespesaService
{
    /** @return Collection<int, CategoriaDespesa> */
    public function listar(): Collection
    {
        return CategoriaDespesa::query()->orderBy('nome')->get();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): CategoriaDespesa
    {
        return CategoriaDespesa::create($atributos);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(CategoriaDespesa $categoriaDespesa, array $atributos): CategoriaDespesa
    {
        $categoriaDespesa->update($atributos);

        return $categoriaDespesa;
    }

    public function excluir(CategoriaDespesa $categoriaDespesa): void
    {
        if ($categoriaDespesa->despesas()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => 'Esta categoria está em uso em uma ou mais despesas e não pode ser excluída.',
            ]);
        }

        $categoriaDespesa->delete();
    }
}
