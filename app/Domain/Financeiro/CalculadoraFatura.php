<?php

namespace App\Domain\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Models\CartaoCredito;
use Carbon\Carbon;

final class CalculadoraFatura
{
    public function dataVencimento(Competencia $competenciaVencimento, CartaoCredito $cartao): Carbon
    {
        return $this->diaDoMes($competenciaVencimento, $cartao->dia_vencimento);
    }

    /**
     * Competência do ciclo de fechamento que gera a fatura de $competenciaVencimento — o mesmo
     * "mês de fechamento" usado por CalculadoraCompetenciaDespesa::competenciaPrimeiraParcela()
     * para rotular em que ciclo uma parcela cai. É por essa competência (não pela data de uma
     * movimentação qualquer) que despesa parcelada é agregada à fatura — ver FaturaService::itens().
     */
    public function competenciaFechamento(Competencia $competenciaVencimento, CartaoCredito $cartao): Competencia
    {
        return $competenciaVencimento->somarMeses(-$this->offsetVencimento($cartao));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function janelaFechamento(Competencia $competenciaVencimento, CartaoCredito $cartao): array
    {
        $competenciaFechamentoAtual = $this->competenciaFechamento($competenciaVencimento, $cartao);
        $competenciaFechamentoAnterior = $competenciaFechamentoAtual->anterior();

        $fim = $this->diaDoMes($competenciaFechamentoAtual, $cartao->dia_fechamento);
        $inicio = $this->diaDoMes($competenciaFechamentoAnterior, $cartao->dia_fechamento)->addDay();

        return [$inicio, $fim];
    }

    private function offsetVencimento(CartaoCredito $cartao): int
    {
        return $cartao->dia_vencimento <= $cartao->dia_fechamento ? 1 : 0;
    }

    private function diaDoMes(Competencia $competencia, int $dia): Carbon
    {
        return Carbon::create(
            $competencia->ano,
            $competencia->mes,
            min($dia, Carbon::create($competencia->ano, $competencia->mes, 1)->daysInMonth),
        );
    }
}
