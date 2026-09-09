<?php

namespace App\Services\Financeiro;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\Financeiro\CalculadoraFatura;
use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\TipoLancamentoDespesa;
use App\Models\Despesa;
use App\Models\Fatura;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FaturaService
{
    public function __construct(
        private readonly CalculadoraFatura $calculadoraFatura,
        private readonly CalculadoraCompetenciaDespesa $calculadoraDespesa,
    ) {}

    /**
     * Itens que compõem a fatura de um cartão numa competência de vencimento, computados ao
     * vivo — sem tabela de vínculo. Parceladas entram pelo ciclo de fechamento (pagas ou não);
     * única/mensal só entram se já têm movimentação com esse cartão dentro da janela.
     *
     * @return list<array{despesa: Despesa, competencia: Competencia, numeroParcela: int|null, valor: Money, movimentacao: Movimentacao|null}>
     */
    public function itens(FormaPagamento $cartao, Competencia $competenciaVencimento): array
    {
        $cicloFechamento = $this->calculadoraFatura->competenciaFechamento($competenciaVencimento, $cartao->cartaoCredito);
        [$inicio, $fim] = $this->calculadoraFatura->janelaFechamento($competenciaVencimento, $cartao->cartaoCredito);

        $itens = [];

        foreach (Despesa::query()
            ->where('forma_pagamento_id', $cartao->id)
            ->where('tipo_lancamento', TipoLancamentoDespesa::Parcelada->value)
            ->with([
                'categoriaDespesa',
                'movimentacoes',
                'formaPagamento' => fn ($query) => $query->withTrashed(),
            ])
            ->get() as $despesa) {
            $numeroParcela = $this->calculadoraDespesa->numeroParcela($despesa, $cicloFechamento);

            if ($numeroParcela === null) {
                continue;
            }

            $itens[] = [
                'despesa' => $despesa,
                'competencia' => $cicloFechamento,
                'numeroParcela' => $numeroParcela,
                'valor' => $despesa->valor,
                'movimentacao' => $despesa->movimentacoes->first(
                    fn (Movimentacao $m) => $m->competencia?->equals($cicloFechamento)
                ),
            ];
        }

        foreach (Movimentacao::query()
            ->where('forma_pagamento_id', $cartao->id)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->whereHas('despesa', fn ($query) => $query->whereIn('tipo_lancamento', [
                TipoLancamentoDespesa::Unica->value,
                TipoLancamentoDespesa::Mensal->value,
            ]))
            ->with('despesa.categoriaDespesa')
            ->get() as $movimentacao) {
            $despesa = $movimentacao->despesa;
            $competencia = $movimentacao->competencia;

            if ($despesa === null || $competencia === null) {
                continue;
            }

            $itens[] = [
                'despesa' => $despesa,
                'competencia' => $competencia,
                'numeroParcela' => null,
                'valor' => $movimentacao->valor->absolute(),
                'movimentacao' => $movimentacao,
            ];
        }

        return $itens;
    }

    public function buscar(FormaPagamento $cartao, Competencia $competenciaVencimento): ?Fatura
    {
        return Fatura::query()
            ->where('cartao_credito_id', $cartao->id)
            ->where('competencia', $competenciaVencimento->paraData())
            ->first();
    }

    private function calcularValorCentavos(FormaPagamento $cartao, Competencia $competenciaVencimento): int
    {
        $itens = $this->itens($cartao, $competenciaVencimento);

        return array_sum(array_map(fn (array $item) => $item['valor']->cents, $itens));
    }

    public function recalcular(Fatura $fatura): ?Fatura
    {
        if ($fatura->estaPaga()) {
            return $fatura;
        }

        $cartao = FormaPagamento::withTrashed()->findOrFail($fatura->cartao_credito_id);
        $valorCentavos = $this->calcularValorCentavos($cartao, $fatura->competencia);

        if ($valorCentavos === 0) {
            $fatura->delete();

            return null;
        }

        $fatura->update(['valor' => $valorCentavos]);

        return $fatura;
    }

    public function gerar(FormaPagamento $cartao, Competencia $competenciaVencimento): Fatura
    {
        $existente = $this->buscar($cartao, $competenciaVencimento);

        if ($existente !== null && $existente->estaPaga()) {
            throw ValidationException::withMessages([
                'competencia' => 'Essa fatura já está paga e não pode ser regenerada.',
            ]);
        }

        $valorCentavos = $this->calcularValorCentavos($cartao, $competenciaVencimento);

        if ($valorCentavos === 0) {
            throw ValidationException::withMessages([
                'competencia' => 'Não há despesas nesse cartão para essa competência.',
            ]);
        }

        $atributos = [
            'cartao_credito_id' => $cartao->id,
            'competencia' => $competenciaVencimento,
            'data_vencimento' => $this->calculadoraFatura->dataVencimento($competenciaVencimento, $cartao->cartaoCredito),
            'valor' => $valorCentavos,
        ];

        if ($existente !== null) {
            $existente->update($atributos);

            return $existente->refresh();
        }

        return Fatura::create($atributos);
    }

    public function marcarComoPaga(Fatura $fatura, string $formaPagamentoRealId, string $dataPagamento): Movimentacao
    {
        if ($fatura->estaPaga()) {
            throw ValidationException::withMessages([
                'fatura' => 'Essa fatura já está paga.',
            ]);
        }

        $formaPagamentoReal = FormaPagamento::query()->whereKey($formaPagamentoRealId)->whereHas('conta')->first();

        if ($formaPagamentoReal === null) {
            throw ValidationException::withMessages([
                'forma_pagamento_id' => 'A forma de pagamento selecionada é inválida.',
            ]);
        }

        if ($formaPagamentoReal->ehCredito()) {
            throw ValidationException::withMessages([
                'forma_pagamento_id' => 'Fatura não pode ser paga com outro cartão de crédito.',
            ]);
        }

        $cartao = FormaPagamento::withTrashed()->findOrFail($fatura->cartao_credito_id);

        return DB::transaction(function () use ($fatura, $cartao, $formaPagamentoReal, $dataPagamento) {
            foreach ($this->itens($cartao, $fatura->competencia) as $item) {
                if ($item['movimentacao'] !== null) {
                    continue;
                }

                Movimentacao::create([
                    'forma_pagamento_id' => $cartao->id,
                    'valor' => $item['valor']->negated(),
                    'data' => $dataPagamento,
                    'despesa_id' => $item['despesa']->id,
                    'fatura_id' => $fatura->id,
                    'competencia' => $item['competencia']->paraData(),
                ]);
            }

            return Movimentacao::create([
                'forma_pagamento_id' => $formaPagamentoReal->id,
                'valor' => $fatura->valor->negated(),
                'data' => $dataPagamento,
                'fatura_id' => $fatura->id,
            ]);
        });
    }

    public function desfazerPagamento(Fatura $fatura): void
    {
        $fatura->movimentacoes()->delete();
    }
}
