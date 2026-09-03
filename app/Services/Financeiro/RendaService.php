<?php

namespace App\Services\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Models\CategoriaRenda;
use App\Models\Conta;
use App\Models\Movimentacao;
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

    public function marcarComoRecebida(
        Renda $renda,
        Competencia $competencia,
        ?string $formaPagamentoId,
        string $dataRecebimento,
        int $valor,
    ): Movimentacao {
        $elegiveis = $renda->formasPagamentoElegiveisParaRecebimento();

        return Movimentacao::create([
            'forma_pagamento_id' => $elegiveis->count() === 1 ? $elegiveis->first()->id : $formaPagamentoId,
            'valor' => $valor,
            'data' => $dataRecebimento,
            'renda_id' => $renda->id,
            'competencia' => $competencia->paraData(),
        ]);
    }

    public function desfazerRecebimento(Renda $renda, Competencia $competencia): void
    {
        $renda->movimentacoes()->where('competencia', $competencia->paraData())->delete();
    }
}
