<?php

namespace App\Services\Financeiro;

use App\Models\CartaoCredito;

final class CartaoCreditoService
{
    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): CartaoCredito
    {
        return CartaoCredito::create([
            'conta_id' => $atributos['conta_id'],
            'nome' => $atributos['nome'],
            'limite_total' => $atributos['limite_total'],
            'limite_usado_abertura' => $atributos['limite_usado_abertura'] ?? 0,
            'dia_fechamento' => $atributos['dia_fechamento'],
            'dia_vencimento' => $atributos['dia_vencimento'],
        ]);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(CartaoCredito $cartaoCredito, array $atributos): CartaoCredito
    {
        $cartaoCredito->update([
            'nome' => $atributos['nome'],
            'limite_total' => $atributos['limite_total'],
            'dia_fechamento' => $atributos['dia_fechamento'],
            'dia_vencimento' => $atributos['dia_vencimento'],
        ]);

        return $cartaoCredito;
    }

    public function excluir(CartaoCredito $cartaoCredito): void
    {
        $cartaoCredito->delete();
    }
}
