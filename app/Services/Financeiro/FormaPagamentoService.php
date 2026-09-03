<?php

namespace App\Services\Financeiro;

use App\Models\CartaoCredito;
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
                'recebe_renda' => $atributos['recebe_renda'] ?? false,
            ]);

            if ($formaPagamento->ehCredito()) {
                CartaoCredito::create([
                    'forma_pagamento_id' => $formaPagamento->id,
                    'limite_total' => $atributos['limite_total'],
                    'limite_usado_abertura' => $atributos['limite_usado_abertura'] ?? 0,
                    'dia_fechamento' => $atributos['dia_fechamento'],
                    'dia_vencimento' => $atributos['dia_vencimento'],
                ]);
            } elseif (($atributos['saldo_inicial'] ?? null) !== null) {
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
            'recebe_renda' => $atributos['recebe_renda'] ?? false,
        ]);

        if ($formaPagamento->ehCredito()) {
            $formaPagamento->cartaoCredito->update([
                'limite_total' => $atributos['limite_total'],
                'dia_fechamento' => $atributos['dia_fechamento'],
                'dia_vencimento' => $atributos['dia_vencimento'],
            ]);
        }

        return $formaPagamento;
    }

    public function excluir(FormaPagamento $formaPagamento): void
    {
        $formaPagamento->delete();
    }
}
