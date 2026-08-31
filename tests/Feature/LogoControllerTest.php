<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('busca na api na primeira vez e serve do cache local depois', function () {
    Storage::fake('local');
    Http::fake([
        'img.logo.dev/*' => Http::response('conteudo-fake-da-logo', 200, ['Content-Type' => 'image/png']),
    ]);

    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)->get(route('logos.show', 'Nubank'))->assertOk();
    $this->actingAs($usuario)->get(route('logos.show', 'Nubank'))->assertOk();

    Http::assertSentCount(1);
});

it('devolve 404 quando a api nao encontra a logo', function () {
    Storage::fake('local');
    Http::fake([
        'img.logo.dev/*' => Http::response('', 404),
    ]);

    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('logos.show', 'MarcaInexistenteXPTO'))
        ->assertNotFound();
});

it('exige autenticacao', function () {
    $this->get(route('logos.show', 'Nubank'))->assertRedirect(route('login'));
});
