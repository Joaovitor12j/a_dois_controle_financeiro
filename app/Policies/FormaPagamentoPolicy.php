<?php

namespace App\Policies;

use App\Models\Conta;
use App\Models\FormaPagamento;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;

class FormaPagamentoPolicy
{
    public function update(Usuario $usuario, FormaPagamento $formaPagamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $formaPagamento);
    }

    public function delete(Usuario $usuario, FormaPagamento $formaPagamento): bool
    {
        return $this->pertenceAoUsuario($usuario, $formaPagamento);
    }

    private function pertenceAoUsuario(Usuario $usuario, FormaPagamento $formaPagamento): bool
    {
        return Conta::withoutGlobalScope(DonoScope::class)
            ->whereKey($formaPagamento->conta_id)
            ->where('usuario_id', $usuario->id)
            ->exists();
    }
}
