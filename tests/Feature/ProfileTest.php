<?php

use App\Models\Usuario;

test('página de perfil é exibida', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get('/profile')
        ->assertOk();
});

test('nome e e-mail são atualizados', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->patch('/profile', [
            'nome' => 'Nome Novo',
            'email' => 'novo@exemplo.test',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $usuario->refresh();

    expect($usuario->nome)->toBe('Nome Novo')
        ->and($usuario->email)->toBe('novo@exemplo.test');
});

test('e-mail já usado pelo parceiro é rejeitado', function () {
    $parceiro = Usuario::factory()->create();
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->patch('/profile', [
            'nome' => $usuario->nome,
            'email' => $parceiro->email,
        ])
        ->assertInvalid(['email']);
});

test('cor é atualizada sem enviar nome e e-mail', function () {
    $usuario = Usuario::factory()->comCor('#2563EB')->create();
    Usuario::factory()->comCor('#DB2777')->create();

    $this->actingAs($usuario)
        ->patch('/profile', [
            'cor' => '#16A34A',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($usuario->corUsuario->fresh()->cor)->toBe('#16A34A');
});
