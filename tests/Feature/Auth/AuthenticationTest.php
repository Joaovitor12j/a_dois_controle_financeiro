<?php

use App\Models\Usuario;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

test('a tela de login é exibida', function () {
    $this->get('/login')->assertOk();
});

test('usuário autentica com credenciais válidas', function () {
    $usuario = Usuario::factory()->create();

    $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($usuario);
});

test('usuário não autentica com senha inválida', function () {
    $usuario = Usuario::factory()->create();

    $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'senha-errada',
    ])->assertInvalid(['email']);

    $this->assertGuest();
});

test('login é bloqueado após cinco tentativas falhas', function () {
    Event::fake([Lockout::class]);

    $usuario = Usuario::factory()->create();

    foreach (range(1, 5) as $tentativa) {
        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'senha-errada',
        ]);
    }

    $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ])->assertInvalid(['email']);

    $this->assertGuest();

    Event::assertDispatched(Lockout::class);
});

test('lembrar-me grava o cookie de sessão persistente', function () {
    $usuario = Usuario::factory()->create();

    $resposta = $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    /** @var SessionGuard $guard */
    $guard = Auth::guard('web');

    $resposta->assertCookie($guard->getRecallerName());

    $this->assertAuthenticatedAs($usuario);
});

test('usuário sai e volta para o login', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
