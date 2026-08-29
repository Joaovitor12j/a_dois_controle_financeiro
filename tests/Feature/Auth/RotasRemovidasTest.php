<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

test('não existe rota de cadastro nem de recuperação de senha', function (string $rota) {
    $this->get($rota)->assertNotFound();
})->with([
    '/register',
    '/forgot-password',
    '/reset-password/token-qualquer',
    '/verify-email',
    '/confirm-password',
]);

test('não existe rota para excluir a própria conta', function () {
    $this->actingAs(Usuario::factory()->create())
        ->delete('/profile')
        ->assertStatus(405);
});

test('nenhuma rota nomeada de cadastro ou reset está registrada', function (string $nome) {
    expect(Route::has($nome))->toBeFalse();
})->with([
    'register',
    'password.request',
    'password.email',
    'password.reset',
    'password.store',
    'password.confirm',
    'verification.notice',
    'verification.send',
    'profile.destroy',
]);
