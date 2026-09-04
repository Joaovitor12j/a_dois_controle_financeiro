<?php

namespace App\Domain\Usuario;

final readonly class CorCasal
{
    private function __construct(public string $hex) {}

    public static function doPar(string $hexA, string $hexB): self
    {
        [$rA, $gA, $bA] = self::rgb($hexA);
        [$rB, $gB, $bB] = self::rgb($hexB);

        return new self(sprintf(
            '#%02X%02X%02X',
            (int) round(($rA + $rB) / 2),
            (int) round(($gA + $gB) / 2),
            (int) round(($bA + $bB) / 2),
        ));
    }

    /** @return array{int, int, int} */
    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
