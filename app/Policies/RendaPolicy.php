<?php

namespace App\Policies;

use App\Models\Renda;
use App\Models\Usuario;

class RendaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Renda $renda): bool
    {
        return $usuario->id === $renda->usuario_id;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Renda $renda): bool
    {
        return $usuario->id === $renda->usuario_id;
    }

    public function delete(Usuario $usuario, Renda $renda): bool
    {
        return $usuario->id === $renda->usuario_id;
    }
}
