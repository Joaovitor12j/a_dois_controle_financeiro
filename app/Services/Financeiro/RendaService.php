<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaRenda;
use App\Models\Conta;
use App\Models\Renda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class RendaService
{
    /** @return Collection<int, Renda> */
    public function listar(): Collection
    {
        return Renda::query()
            ->with(['categoriaRenda', 'conta'])
            ->orderBy('descricao')
            ->get();
    }

    /** @return Collection<int, Conta> */
    public function contasDisponiveis(): Collection
    {
        return Conta::query()->orderBy('nome')->get(['id', 'nome']);
    }

    /** @return Collection<int, CategoriaRenda> */
    public function categoriasDisponiveis(): Collection
    {
        return CategoriaRenda::query()->orderBy('nome')->get();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): Renda
    {
        return Renda::create([
            ...$atributos,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(Renda $renda, array $atributos): Renda
    {
        $renda->update($atributos);

        return $renda;
    }

    public function excluir(Renda $renda): void
    {
        $renda->delete();
    }
}
