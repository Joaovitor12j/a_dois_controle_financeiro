<?php

use App\Domain\Exceptions\CompetenciaInvalida;
use App\Domain\ValueObjects\Competencia;

it('normaliza para o primeiro dia do mês', function () {
    expect(Competencia::deAnoMes(2026, 8)->paraData()->format('Y-m-d'))->toBe('2026-08-01');
});

it('descarta o dia ao construir a partir de uma data', function () {
    $competencia = Competencia::deData(new DateTimeImmutable('2026-08-30 23:59:59'));

    expect($competencia->paraData()->format('Y-m-d'))->toBe('2026-08-01')
        ->and($competencia->ano)->toBe(2026)
        ->and($competencia->mes)->toBe(8);
});

it('constrói a partir de ano-mês em texto', function () {
    expect((string) Competencia::deString('2026-08'))->toBe('2026-08');
});

it('rejeita mês fora do intervalo', function (int $mes) {
    expect(fn () => Competencia::deAnoMes(2026, $mes))->toThrow(CompetenciaInvalida::class);
})->with([0, 13, -1]);

it('rejeita texto malformado', function (string $entrada) {
    expect(fn () => Competencia::deString($entrada))->toThrow(CompetenciaInvalida::class);
})->with(['2026', '2026-13', '26-08', 'agosto', '', '2026-08-01']);

it('avança e retrocede atravessando a virada de ano', function () {
    expect((string) Competencia::deAnoMes(2026, 12)->proxima())->toBe('2027-01')
        ->and((string) Competencia::deAnoMes(2026, 1)->anterior())->toBe('2025-12')
        ->and((string) Competencia::deAnoMes(2026, 8)->somarMeses(6))->toBe('2027-02')
        ->and((string) Competencia::deAnoMes(2026, 8)->somarMeses(-8))->toBe('2025-12');
});

it('é imutável ao navegar entre meses', function () {
    $original = Competencia::deAnoMes(2026, 8);
    $original->proxima();

    expect((string) $original)->toBe('2026-08');
});

it('compara por valor, não por identidade', function () {
    expect(Competencia::deAnoMes(2026, 8)->equals(Competencia::deAnoMes(2026, 8)))->toBeTrue()
        ->and(Competencia::deAnoMes(2026, 8)->equals(Competencia::deAnoMes(2026, 9)))->toBeFalse();
});

it('ordena competências', function () {
    $agosto = Competencia::deAnoMes(2026, 8);
    $janeiro = Competencia::deAnoMes(2027, 1);

    expect($agosto->ehAnterior($janeiro))->toBeTrue()
        ->and($janeiro->ehPosterior($agosto))->toBeTrue()
        ->and($agosto->ehAnterior($agosto))->toBeFalse()
        ->and($agosto->ehPosterior($agosto))->toBeFalse();
});

it('serializa em json como ano-mês', function () {
    expect(json_encode(['competencia' => Competencia::deAnoMes(2026, 8)]))->toBe('{"competencia":"2026-08"}');
});
