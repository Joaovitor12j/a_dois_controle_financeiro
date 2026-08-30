<?php

namespace App\Policies;

use App\Models\CartaoCredito;
use App\Models\Usuario;

class CartaoCreditoPolicy
{
    public function update(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $usuario->id === $cartaoCredito->conta->usuario_id;
    }

    public function delete(Usuario $usuario, CartaoCredito $cartaoCredito): bool
    {
        return $usuario->id === $cartaoCredito->conta->usuario_id;
    }
}
