<?php

namespace App\Policies;

use App\Models\Usuario;

class CategoriaDespesaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function update(Usuario $usuario): bool
    {
        return true;
    }

    public function delete(Usuario $usuario): bool
    {
        return true;
    }
}
