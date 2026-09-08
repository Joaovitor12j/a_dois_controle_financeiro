<?php

namespace App\Policies;

use App\Models\Fatura;
use App\Models\Usuario;

class FaturaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    public function create(Usuario $usuario): bool
    {
        return true;
    }

    public function view(Usuario $usuario, Fatura $fatura): bool
    {
        return $this->dono($fatura) === $usuario->id;
    }

    public function update(Usuario $usuario, Fatura $fatura): bool
    {
        return $this->dono($fatura) === $usuario->id;
    }

    private function dono(Fatura $fatura): ?string
    {
        return $fatura->cartaoCredito?->formaPagamento?->conta?->usuario_id;
    }
}
