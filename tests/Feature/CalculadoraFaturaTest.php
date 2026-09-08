<?php

use App\Domain\Financeiro\CalculadoraFatura;
use App\Domain\ValueObjects\Competencia;
use App\Models\CartaoCredito;

function cartaoEmMemoria(int $diaFechamento, int $diaVencimento): CartaoCredito
{
    $cartao = new CartaoCredito;
    $cartao->forceFill([
        'dia_fechamento' => $diaFechamento,
        'dia_vencimento' => $diaVencimento,
    ]);

    return $cartao;
}

function calculadoraFatura(): CalculadoraFatura
{
    return new CalculadoraFatura;
}

it('vencimento no mesmo mês do fechamento quando dia de vencimento é depois do dia de fechamento', function () {
    $cartao = cartaoEmMemoria(diaFechamento: 5, diaVencimento: 12);

    expect(calculadoraFatura()->dataVencimento(Competencia::deAnoMes(2026, 9), $cartao)->toDateString())
        ->toBe('2026-09-12');

    [$inicio, $fim] = calculadoraFatura()->janelaFechamento(Competencia::deAnoMes(2026, 9), $cartao);

    expect($inicio->toDateString())->toBe('2026-08-06')
        ->and($fim->toDateString())->toBe('2026-09-05');
});

it('vencimento no mês seguinte ao fechamento quando dia de vencimento é igual ou antes do dia de fechamento', function () {
    $cartao = cartaoEmMemoria(diaFechamento: 25, diaVencimento: 5);

    expect(calculadoraFatura()->dataVencimento(Competencia::deAnoMes(2026, 10), $cartao)->toDateString())
        ->toBe('2026-10-05');

    [$inicio, $fim] = calculadoraFatura()->janelaFechamento(Competencia::deAnoMes(2026, 10), $cartao);

    expect($inicio->toDateString())->toBe('2026-08-26')
        ->and($fim->toDateString())->toBe('2026-09-25');
});

it('capa o dia de fechamento e de vencimento ao último dia do mês quando o mês é mais curto', function () {
    $cartao = cartaoEmMemoria(diaFechamento: 31, diaVencimento: 31);

    expect(calculadoraFatura()->dataVencimento(Competencia::deAnoMes(2026, 2), $cartao)->toDateString())
        ->toBe('2026-02-28');
});
