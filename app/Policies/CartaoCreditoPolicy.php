<?php

namespace App\Policies;

use App\Models\CartaoCredito;
use App\Models\Conta;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;

class CartaoCreditoPolicy
{
    public function update(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $this->pertenceAoUsuario($usuario, $cartaoCredito);
    }

    public function delete(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $this->pertenceAoUsuario($usuario, $cartaoCredito);
    }

    private function pertenceAoUsuario(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return Conta::withoutGlobalScope(DonoScope::class)
            ->whereKey($cartaoCredito->conta_id)
            ->where('usuario_id', $usuario->id)
            ->exists();
    }
}
