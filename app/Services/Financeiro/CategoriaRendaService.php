<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaRenda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class CategoriaRendaService
{
    /** @return Collection<int, CategoriaRenda> */
    public function listar(): Collection
    {
        return CategoriaRenda::query()->orderBy('nome')->get();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): CategoriaRenda
    {
        return CategoriaRenda::create($atributos);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(CategoriaRenda $categoriaRenda, array $atributos): CategoriaRenda
    {
        $categoriaRenda->update($atributos);

        return $categoriaRenda;
    }

    public function excluir(CategoriaRenda $categoriaRenda): void
    {
        if ($categoriaRenda->rendas()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => 'Esta categoria está em uso em uma ou mais rendas e não pode ser excluída.',
            ]);
        }

        $categoriaRenda->delete();
    }
}
