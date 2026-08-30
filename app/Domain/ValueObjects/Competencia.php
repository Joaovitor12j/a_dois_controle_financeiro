<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\CompetenciaInvalida;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use Stringable;

final readonly class Competencia implements JsonSerializable, Stringable
{
    private function __construct(public int $ano, public int $mes) {}

    public static function deAnoMes(int $ano, int $mes): self
    {
        if ($mes < 1 || $mes > 12) {
            throw CompetenciaInvalida::paraMes($mes);
        }

        return new self($ano, $mes);
    }

    public static function deData(DateTimeInterface $data): self
    {
        return new self((int) $data->format('Y'), (int) $data->format('n'));
    }

    public static function deString(string $anoMes): self
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', trim($anoMes), $partes) !== 1) {
            throw CompetenciaInvalida::paraEntrada($anoMes);
        }

        return self::deAnoMes((int) $partes[1], (int) $partes[2]);
    }

    public function somarMeses(int $meses): self
    {
        $total = ($this->ano * 12) + ($this->mes - 1) + $meses;

        return new self(intdiv($total, 12), ($total % 12) + 1);
    }

    public function proxima(): self
    {
        return $this->somarMeses(1);
    }

    public function anterior(): self
    {
        return $this->somarMeses(-1);
    }

    public function paraData(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->ano, $this->mes));
    }

    public function equals(self $outra): bool
    {
        return $this->ano === $outra->ano && $this->mes === $outra->mes;
    }

    public function ehAnterior(self $outra): bool
    {
        return $this->ordinal() < $outra->ordinal();
    }

    public function ehPosterior(self $outra): bool
    {
        return $this->ordinal() > $outra->ordinal();
    }

    public function __toString(): string
    {
        return sprintf('%04d-%02d', $this->ano, $this->mes);
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    private function ordinal(): int
    {
        return ($this->ano * 12) + $this->mes;
    }
}
