<?php

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\ValueObjects\Competencia;
use App\Enums\TipoLancamentoDespesa;
use App\Models\CartaoCredito;
use App\Models\Despesa;
use App\Models\FormaPagamento;

/** @param array<string, mixed> $atributos */
function despesaEmMemoria(array $atributos): Despesa
{
    $despesa = new Despesa;
    $despesa->forceFill($atributos);

    return $despesa;
}

function despesaParceladaEmMemoria(string $dataPrimeiraParcela, int $numeroParcelas, int $diaFechamento): Despesa
{
    $despesa = despesaEmMemoria([
        'tipo_lancamento' => TipoLancamentoDespesa::Parcelada,
        'data_primeira_parcela' => $dataPrimeiraParcela,
        'numero_parcelas' => $numeroParcelas,
    ]);

    $cartao = new CartaoCredito;
    $cartao->forceFill(['dia_fechamento' => $diaFechamento]);

    $formaPagamento = new FormaPagamento;
    $formaPagamento->setRelation('cartaoCredito', $cartao);

    $despesa->setRelation('formaPagamento', $formaPagamento);

    return $despesa;
}

function calculadora(): CalculadoraCompetenciaDespesa
{
    return new CalculadoraCompetenciaDespesa;
}

// Única

it('única existe apenas na competência da data de vencimento', function () {
    $despesa = despesaEmMemoria([
        'tipo_lancamento' => TipoLancamentoDespesa::Unica,
        'data_vencimento' => '2026-08-10',
    ]);

    expect(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 8)))->toBeTrue()
        ->and(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 9)))->toBeFalse();
});

// Mensal

it('mensal não existe antes de data_inicio', function () {
    $despesa = despesaEmMemoria([
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_inicio' => '2026-03-01',
        'data_fim' => null,
    ]);

    expect(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 2)))->toBeFalse()
        ->and(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 3)))->toBeTrue();
});

it('mensal sem data_fim existe indefinidamente a partir de data_inicio', function () {
    $despesa = despesaEmMemoria([
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_inicio' => '2026-03-01',
        'data_fim' => null,
    ]);

    expect(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2030, 1)))->toBeTrue();
});

it('mensal com data_fim não existe depois dela', function () {
    $despesa = despesaEmMemoria([
        'tipo_lancamento' => TipoLancamentoDespesa::Mensal,
        'data_inicio' => '2026-03-01',
        'data_fim' => '2026-06-01',
    ]);

    expect(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 6)))->toBeTrue()
        ->and(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 7)))->toBeFalse();
});

// Parcelada

it('parcelada: compra até o dia de fechamento cai na competência do próprio mês', function () {
    $despesa = despesaParceladaEmMemoria('2026-09-15', 3, diaFechamento: 20);

    expect((string) calculadora()->competenciaPrimeiraParcela($despesa))->toBe('2026-09');
});

it('parcelada: compra após o dia de fechamento cai na competência do mês seguinte', function () {
    $despesa = despesaParceladaEmMemoria('2026-09-25', 3, diaFechamento: 20);

    expect((string) calculadora()->competenciaPrimeiraParcela($despesa))->toBe('2026-10');
});

it('parcelada: numeroParcela numera sequencialmente a partir da primeira competência', function () {
    $despesa = despesaParceladaEmMemoria('2026-09-15', 3, diaFechamento: 20);

    expect(calculadora()->numeroParcela($despesa, Competencia::deAnoMes(2026, 9)))->toBe(1)
        ->and(calculadora()->numeroParcela($despesa, Competencia::deAnoMes(2026, 10)))->toBe(2)
        ->and(calculadora()->numeroParcela($despesa, Competencia::deAnoMes(2026, 11)))->toBe(3);
});

it('parcelada: numeroParcela retorna null fora do intervalo de parcelas', function () {
    $despesa = despesaParceladaEmMemoria('2026-09-15', 3, diaFechamento: 20);

    expect(calculadora()->numeroParcela($despesa, Competencia::deAnoMes(2026, 8)))->toBeNull()
        ->and(calculadora()->numeroParcela($despesa, Competencia::deAnoMes(2026, 12)))->toBeNull();
});

it('parcelada: existeNaCompetencia reflete numeroParcela', function () {
    $despesa = despesaParceladaEmMemoria('2026-09-15', 3, diaFechamento: 20);

    expect(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 11)))->toBeTrue()
        ->and(calculadora()->existeNaCompetencia($despesa, Competencia::deAnoMes(2026, 12)))->toBeFalse();
});
