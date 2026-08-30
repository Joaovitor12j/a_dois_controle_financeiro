<?php

namespace App\Services\Financeiro;

use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use Throwable;

final class FormaPagamentoService
{
    /** @param array<string, mixed> $atributos
     * @throws Throwable
     */
    public function criar(array $atributos): FormaPagamento
    {
        return DB::transaction(static function () use ($atributos): FormaPagamento {
            $formaPagamento = FormaPagamento::create([
                'conta_id' => $atributos['conta_id'],
                'nome' => $atributos['nome'],
                'tipo' => $atributos['tipo'],
            ]);

            if (($atributos['saldo_inicial'] ?? null) !== null) {
                Movimentacao::create([
                    'forma_pagamento_id' => $formaPagamento->id,
                    'valor' => $atributos['saldo_inicial'],
                    'data' => $atributos['data_saldo_inicial'],
                    'is_saldo_inicial' => true,
                ]);
            }

            return $formaPagamento;
        });
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(FormaPagamento $formaPagamento, array $atributos): FormaPagamento
    {
        $formaPagamento->update([
            'nome' => $atributos['nome'],
            'tipo' => $atributos['tipo'],
        ]);

        return $formaPagamento;
    }

    public function excluir(FormaPagamento $formaPagamento): void
    {
        $formaPagamento->delete();
    }
}
