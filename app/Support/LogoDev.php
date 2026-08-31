<?php

namespace App\Support;

final class LogoDev
{
    /**
     * @param  array{size?: int, format?: string, theme?: string, greyscale?: bool, fallback?: string}  $options
     */
    public static function getLogoUrlByName(string $name, array $options = []): string
    {
        $query = array_filter([
            'token' => config('services.logodev.token'),
            'size' => $options['size'] ?? null,
            'format' => $options['format'] ?? null,
            'theme' => $options['theme'] ?? null,
            'greyscale' => $options['greyscale'] ?? null,
            'fallback' => $options['fallback'] ?? null,
        ], static fn ($value) => $value !== null);

        return 'https://img.logo.dev/name/'.rawurlencode($name).'?'.http_build_query($query);
    }
}
