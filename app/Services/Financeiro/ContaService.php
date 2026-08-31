<?php

namespace App\Services\Financeiro;

use App\Models\Conta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ContaService
{
    /** @return Collection<int, Conta> */
    public function listar(): Collection
    {
        return Conta::query()
            ->with(['formasPagamento.saldoInicial', 'cartoesCredito'])
            ->orderBy('nome')
            ->get();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): Conta
    {
        return Conta::create([
            ...$atributos,
            'usuario_id' => Auth::id(),
        ]);
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(Conta $conta, array $atributos): Conta
    {
        $conta->update($atributos);

        return $conta;
    }

    public function excluir(Conta $conta): void
    {
        DB::transaction(fn () => $conta->delete());
    }
}
