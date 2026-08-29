<?php

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('seeder cria exatamente os dois usuários configurados', function () {
    $this->seed(DatabaseSeeder::class);

    $usuarios = Usuario::query()->orderBy('email')->get();

    expect($usuarios)->toHaveCount(2);

    foreach ($usuarios as $usuario) {
        expect(Str::isUuid($usuario->id))->toBeTrue()
            ->and($usuario->password)->not->toBe('')
            ->and(Hash::isHashed($usuario->password))->toBeTrue();
    }

    /** @var array<int, array{email: string}> $iniciais */
    $iniciais = config('usuarios.iniciais');
    $configurados = array_column($iniciais, 'email');
    sort($configurados);

    expect($usuarios->pluck('email')->sort()->values()->all())->toBe($configurados);
});

test('seeder é idempotente', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Usuario::query()->count())->toBe(2);
});

test('seeder não sobrescreve dados editados no painel', function () {
    $this->seed(DatabaseSeeder::class);

    /** @var Usuario $usuario */
    $usuario = Usuario::query()->orderBy('email')->firstOrFail();

    $usuario->update([
        'nome' => 'Nome Editado',
        'email' => 'editado@exemplo.test',
        'password' => 'outra-senha-forte',
    ]);

    $this->seed(DatabaseSeeder::class);

    $usuario->refresh();

    expect(Usuario::query()->count())->toBe(2)
        ->and($usuario->nome)->toBe('Nome Editado')
        ->and($usuario->email)->toBe('editado@exemplo.test')
        ->and(Hash::check('outra-senha-forte', $usuario->password))->toBeTrue();
});

test('senha semeada permite login', function () {
    $this->seed(DatabaseSeeder::class);

    /** @var array<int, array{email: string, senha: string}> $iniciais */
    $iniciais = config('usuarios.iniciais');
    $configurado = $iniciais[0];

    $this->post('/login', [
        'email' => $configurado['email'],
        'password' => $configurado['senha'],
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
