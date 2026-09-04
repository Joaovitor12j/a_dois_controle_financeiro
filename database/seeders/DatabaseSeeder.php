<?php

namespace Database\Seeders;

use App\Domain\Usuario\CorCasal;
use App\Models\CorUsuario;
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

        $usuariosCriados = [];

        foreach ($iniciais as $usuario) {
            $usuariosCriados[] = Usuario::query()->create([
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'password' => $usuario['senha'],
            ]);
        }

        $corCasal = CorCasal::doPar($iniciais[0]['cor'], $iniciais[1]['cor'])->hex;

        foreach ($usuariosCriados as $indice => $usuarioCriado) {
            CorUsuario::query()->create([
                'usuario_id' => $usuarioCriado->id,
                'cor' => $iniciais[$indice]['cor'],
                'cor_casal' => $corCasal,
            ]);
        }
    }
}
