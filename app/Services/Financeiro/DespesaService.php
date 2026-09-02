<?php

namespace App\Services\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Models\Despesa;
use App\Models\Movimentacao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): Despesa
    {
        $paga = (bool) ($atributos['paga'] ?? false);
        $formaPagamentoId = $atributos['forma_pagamento_id'] ?? null;
        $dataPagamento = $atributos['data_pagamento'] ?? null;

        unset($atributos['paga'], $atributos['data_pagamento']);

        if ($paga) {
            unset($atributos['forma_pagamento_id']);
        }

        $despesa = Despesa::create([
            ...$atributos,
            'usuario_id' => Auth::id(),
        ]);

        if ($paga) {
            $this->marcarComoPaga($despesa, Competencia::deData(Carbon::parse($despesa->data_vencimento)), $formaPagamentoId, $dataPagamento);
        }

        return $despesa;
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
