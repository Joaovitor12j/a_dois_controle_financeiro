<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

test('senha é trocada com a senha atual correta', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'senha-nova-forte',
            'password_confirmation' => 'senha-nova-forte',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect(Hash::check('senha-nova-forte', $usuario->refresh()->password))->toBeTrue();
});

test('senha não é trocada com a senha atual incorreta', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'senha-errada',
            'password' => 'senha-nova-forte',
            'password_confirmation' => 'senha-nova-forte',
        ])
        ->assertInvalid(['current_password'])
        ->assertRedirect('/profile');

    expect(Hash::check('password', $usuario->refresh()->password))->toBeTrue();
});
