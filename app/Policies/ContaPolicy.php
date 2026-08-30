<?php

namespace App\Policies;

use App\Models\Conta;
use App\Models\Usuario;

class ContaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Conta $conta): bool
    {
        return $usuario->id === $conta->usuario_id;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Conta $conta): bool
    {
        return $usuario->id === $conta->usuario_id;
    }

    public function delete(Usuario $usuario, Conta $conta): bool
    {
        return $usuario->id === $conta->usuario_id;
    }
}
