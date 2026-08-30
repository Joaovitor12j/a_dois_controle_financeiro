<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValorMonetarioInvalido;
use JsonSerializable;

final readonly class Money implements JsonSerializable
{
    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromString(string $amount): self
    {
        $trimmed = trim($amount);

        $normalized = str_contains($trimmed, ',')
            ? str_replace(',', '.', str_replace('.', '', $trimmed))
            : $trimmed;

        if (preg_match('/^-?\d+(\.\d{1,2})?$/', $normalized) !== 1) {
            throw ValorMonetarioInvalido::paraEntrada($amount);
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized), 2, '0');

        $cents = abs((int) $whole) * 100 + (int) str_pad($fraction, 2, '0');

        return new self(str_starts_with($whole, '-') ? -$cents : $cents);
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function times(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function negated(): self
    {
        return new self(-$this->cents);
    }

    public function absolute(): self
    {
        return new self(abs($this->cents));
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function jsonSerialize(): int
    {
        return $this->cents;
    }
}
