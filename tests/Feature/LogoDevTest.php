<?php

use App\Support\LogoDev;

it('monta a url base com token sempre presente', function () {
    config(['services.logodev.token' => 'pk_teste']);

    $url = LogoDev::getLogoUrlByName('Stripe');

    expect($url)->toBe('https://img.logo.dev/name/Stripe?token=pk_teste');
});

it('url-encoda nomes com espaco e acento', function () {
    config(['services.logodev.token' => 'pk_teste']);

    $url = LogoDev::getLogoUrlByName('The Home Depot');

    expect($url)->toContain('img.logo.dev/name/The%20Home%20Depot?');
});

it('inclui opcoes suportadas na query apenas quando informadas', function () {
    config(['services.logodev.token' => 'pk_teste']);

    $url = LogoDev::getLogoUrlByName('Apple', [
        'size' => 128,
        'format' => 'png',
        'theme' => 'dark',
        'greyscale' => true,
        'fallback' => 'monogram',
    ]);

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toBe([
        'token' => 'pk_teste',
        'size' => '128',
        'format' => 'png',
        'theme' => 'dark',
        'greyscale' => '1',
        'fallback' => 'monogram',
    ]);
});

it('nao envia opcoes nao informadas', function () {
    config(['services.logodev.token' => 'pk_teste']);

    $url = LogoDev::getLogoUrlByName('Google', ['size' => 64]);

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toBe([
        'token' => 'pk_teste',
        'size' => '64',
    ]);
});
