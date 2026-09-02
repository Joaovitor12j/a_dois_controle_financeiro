<?php

namespace App\Policies;

use App\Models\Despesa;
use App\Models\Usuario;

class DespesaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Despesa $despesa): bool
    {
        return $despesa->ehConjunta() || $despesa->usuario_id === $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario, Despesa $despesa): bool
    {
        return $despesa->ehConjunta() || $despesa->usuario_id === $usuario->id;
    }

    public function delete(Usuario $usuario, Despesa $despesa): bool
    {
        return $despesa->ehConjunta() || $despesa->usuario_id === $usuario->id;
    }
}
