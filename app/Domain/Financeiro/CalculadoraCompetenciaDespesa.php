<?php

namespace App\Domain\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Enums\TipoLancamentoDespesa;
use App\Models\Despesa;
use Carbon\Carbon;

final class CalculadoraCompetenciaDespesa
{
    public function existeNaCompetencia(Despesa $despesa, Competencia $competencia): bool
    {
        return match ($despesa->tipo_lancamento) {
            TipoLancamentoDespesa::Unica => Competencia::deData(Carbon::parse($despesa->data_vencimento))->equals($competencia),
            TipoLancamentoDespesa::Mensal => $this->existeNaMensal($despesa, $competencia),
            TipoLancamentoDespesa::Parcelada => $this->numeroParcela($despesa, $competencia) !== null,
        };
    }

    public function numeroParcela(Despesa $despesa, Competencia $competencia): ?int
    {
        $primeira = $this->competenciaPrimeiraParcela($despesa);

        for ($i = 0; $i < $despesa->numero_parcelas; $i++) {
            if ($primeira->somarMeses($i)->equals($competencia)) {
                return $i + 1;
            }
        }

        return null;
    }

    public function competenciaPrimeiraParcela(Despesa $despesa): Competencia
    {
        $diaFechamento = $despesa->formaPagamento->cartaoCredito->dia_fechamento;
        $dataCompra = Carbon::parse($despesa->data_primeira_parcela);

        return $dataCompra->day <= $diaFechamento
            ? Competencia::deData($dataCompra)
            : Competencia::deData($dataCompra)->somarMeses(1);
    }

    private function existeNaMensal(Despesa $despesa, Competencia $competencia): bool
    {
        $inicio = Competencia::deData(Carbon::parse($despesa->data_inicio));

        if ($competencia->ehAnterior($inicio)) {
            return false;
        }

        if ($despesa->data_fim === null) {
            return true;
        }

        return ! $competencia->ehPosterior(Competencia::deData(Carbon::parse($despesa->data_fim)));
    }
}
