<?php

namespace App\Policies;

use App\Models\FormaPagamento;
use App\Models\Usuario;

class FormaPagamentoPolicy
{
    public function update(Usuario $usuario, FormaPagamento $formaPagamento): bool
    {
        return $usuario->id === $formaPagamento->conta->usuario_id;
    }

    public function delete(Usuario $usuario, FormaPagamento $formaPagamento): bool
    {
        return $usuario->id === $formaPagamento->conta->usuario_id;
    }
}
