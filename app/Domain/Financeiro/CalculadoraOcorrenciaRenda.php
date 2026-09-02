<?php

namespace App\Domain\Financeiro;

use App\Domain\ValueObjects\Competencia;
use App\Enums\TipoRecorrencia;
use App\Models\Renda;
use Carbon\Carbon;

final class CalculadoraOcorrenciaRenda
{
    public function existeNaCompetencia(Renda $renda, Competencia $competencia): bool
    {
        return match ($renda->tipo_recorrencia) {
            TipoRecorrencia::Unica => Competencia::deData(Carbon::parse($renda->data_recebimento))->equals($competencia),
            TipoRecorrencia::Mensal => $this->existeNaMensal($renda, $competencia),
        };
    }

    public function diaDoEvento(Renda $renda): int
    {
        return match ($renda->tipo_recorrencia) {
            TipoRecorrencia::Unica => Carbon::parse($renda->data_recebimento)->day,
            TipoRecorrencia::Mensal => $renda->dia_recebimento,
        };
    }

    private function existeNaMensal(Renda $renda, Competencia $competencia): bool
    {
        $inicio = Competencia::deData(Carbon::parse($renda->data_inicio));

        if ($competencia->ehAnterior($inicio)) {
            return false;
        }

        if ($renda->data_fim === null) {
            return true;
        }

        return ! $competencia->ehPosterior(Competencia::deData(Carbon::parse($renda->data_fim)));
    }
}
