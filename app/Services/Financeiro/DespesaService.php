<?php

namespace App\Services\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Models\CategoriaDespesa;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;

final class DespesaService
{
    /** @return Collection<int, Despesa> */
    public function listar(): Collection
    {
        return Despesa::query()
            ->with(['formaPagamento.conta.usuario', 'categoriaDespesa'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** @return Collection<int, CategoriaDespesa> */
    public function categoriasDisponiveis(): Collection
    {
        return CategoriaDespesa::query()->orderBy('nome')->get();
    }

    /** @return SupportCollection<int, FormaPagamento> */
    public function formasPagamentoDisponiveis(): SupportCollection
    {
        return FormaPagamento::query()
            ->with(['cartaoCredito', 'conta:id,nome'])
            ->get()
            ->sortBy(fn (FormaPagamento $forma) => "{$forma->conta->nome} $forma->nome")
            ->values();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): Despesa
    {
        return Despesa::create([
            ...$atributos,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(Despesa $despesa, array $atributos): Despesa
    {
        $despesa->update($atributos);

        return $despesa;
    }

    public function excluir(Despesa $despesa): void
    {
        $despesa->delete();
    }

    public function estaPagaNaCompetencia(Despesa $despesa, Competencia $competencia): bool
    {
        return $despesa->movimentacoes()->where('competencia', $competencia->paraData())->exists();
    }

    public function marcarComoPaga(
        Despesa $despesa,
        Competencia $competencia,
        ?string $formaPagamentoId,
        string $dataPagamento,
    ): Movimentacao {
        $formaPagamentoId = $despesa->ehParcelada() ? $despesa->forma_pagamento_id : $formaPagamentoId;

        return Movimentacao::create([
            'forma_pagamento_id' => $formaPagamentoId,
            'valor' => $despesa->valor->negated(),
            'data' => $dataPagamento,
            'despesa_id' => $despesa->id,
            'competencia' => $competencia->paraData(),
        ]);
    }

    public function desfazerPagamento(Despesa $despesa, Competencia $competencia): void
    {
        $despesa->movimentacoes()->where('competencia', $competencia->paraData())->delete();
    }
}
