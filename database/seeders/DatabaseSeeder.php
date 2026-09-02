<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategoriaRendaSeeder::class);
        $this->call(CategoriaDespesaSeeder::class);

        if (Usuario::query()->exists()) {
            return;
        }

        /** @var array<int, array{nome: string, email: string, senha: string, cor: string}> $iniciais */
        $iniciais = config('usuarios.iniciais');

        foreach ($iniciais as $usuario) {
            Usuario::query()->create([
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'password' => $usuario['senha'],
                'cor' => $usuario['cor'],
            ]);
        }
    }
}
