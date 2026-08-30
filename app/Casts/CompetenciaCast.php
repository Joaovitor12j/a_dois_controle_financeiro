<?php

namespace App\Casts;

use App\Domain\ValueObjects\Competencia;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<Competencia, Competencia|DateTimeInterface|string> */
final class CompetenciaCast implements CastsAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Competencia
    {
        return $value === null ? null : Competencia::deData(new DateTimeImmutable((string) $value));
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $competencia = match (true) {
            $value === null => null,
            $value instanceof Competencia => $value,
            $value instanceof DateTimeInterface => Competencia::deData($value),
            default => $this->deTexto((string) $value),
        };

        return $competencia?->paraData()->format('Y-m-d');
    }

    private function deTexto(string $valor): Competencia
    {
        return preg_match('/^\d{4}-\d{2}$/', trim($valor)) === 1
            ? Competencia::deString($valor)
            : Competencia::deData(new DateTimeImmutable($valor));
    }
}
