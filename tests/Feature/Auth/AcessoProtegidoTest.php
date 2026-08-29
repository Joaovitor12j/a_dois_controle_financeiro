<?php

use App\Models\Usuario;

test('visitante é levado ao login', function (string $rota) {
    $this->get($rota)->assertRedirect(route('login'));
})->with(['/', '/dashboard', '/profile']);

test('usuário autenticado na raiz vai para a visão geral', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('usuário autenticado acessa a visão geral e o perfil', function (string $rota) {
    $this->actingAs(Usuario::factory()->create())
        ->get($rota)
        ->assertOk();
})->with(['/dashboard', '/profile']);
