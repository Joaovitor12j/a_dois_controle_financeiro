<?php

namespace App\Services\Usuario;

use App\Domain\Usuario\CorCasal;
use App\Models\CorUsuario;
use App\Models\Usuario;

class CorUsuarioService
{
    public function atualizar(Usuario $usuario, string $cor): void
    {
        $corUsuario = CorUsuario::query()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            ['cor' => $cor],
        );

        $parceiro = Usuario::query()->where('id', '!=', $usuario->id)->first();
        $corParceiro = $parceiro?->corUsuario;

        if ($corParceiro === null) {
            return;
        }

        $corCasal = CorCasal::doPar($corUsuario->cor, $corParceiro->cor)->hex;

        $corUsuario->update(['cor_casal' => $corCasal]);
        $corParceiro->update(['cor_casal' => $corCasal]);
    }
}
