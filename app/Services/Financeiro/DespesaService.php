<?php

namespace App\Services\Financeiro;

use App\Models\CategoriaDespesa;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class DespesaService
{
    /** @return Collection<int, Despesa> */
    public function listar(): Collection
    {
        return Despesa::query()
            ->with(['formaPagamento', 'categoriaDespesa'])
            ->orderByDesc('created_at')
            ->get();
    }

    /** @return Collection<int, CategoriaDespesa> */
    public function categoriasDisponiveis(): Collection
    {
        return CategoriaDespesa::query()->orderBy('nome')->get();
    }

    /** @return Collection<int, FormaPagamento> */
    public function formasPagamentoDisponiveis(): Collection
    {
        return FormaPagamento::query()->with('cartaoCredito')->orderBy('nome')->get();
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

    public function marcarComoPaga(Despesa $despesa, string $formaPagamentoId, string $dataPagamento): Despesa
    {
        $despesa->update([
            'paga' => true,
            'forma_pagamento_id' => $formaPagamentoId,
            'data_pagamento' => $dataPagamento,
        ]);

        return $despesa;
    }

    public function desfazerPagamento(Despesa $despesa): Despesa
    {
        $despesa->update([
            'paga' => false,
            'forma_pagamento_id' => null,
            'data_pagamento' => null,
        ]);

        return $despesa;
    }
}
