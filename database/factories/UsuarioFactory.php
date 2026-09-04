<?php

namespace Database\Factories;

use App\Models\CorUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'remember_token' => str()->random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Usuario $usuario): void {
            CorUsuario::query()->create([
                'usuario_id' => $usuario->id,
                'cor' => fake()->hexColor(),
            ]);
        });
    }

    public function comCor(string $cor): static
    {
        return $this->afterCreating(function (Usuario $usuario) use ($cor): void {
            $usuario->corUsuario()->update(['cor' => $cor]);
        });
    }
}
