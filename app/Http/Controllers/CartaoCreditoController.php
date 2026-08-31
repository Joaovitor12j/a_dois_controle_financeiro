<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartaoCreditoRequest;
use App\Http\Requests\UpdateCartaoCreditoRequest;
use App\Models\CartaoCredito;
use App\Services\Financeiro\CartaoCreditoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CartaoCreditoController extends Controller
{
    public function __construct(private readonly CartaoCreditoService $cartoesCredito) {}

    public function store(StoreCartaoCreditoRequest $request): RedirectResponse
    {
        $this->authorize('create', CartaoCredito::class);

        $this->cartoesCredito->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cartão de crédito criado com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function update(UpdateCartaoCreditoRequest $request, CartaoCredito $cartaoCredito): RedirectResponse
    {
        $this->cartoesCredito->atualizar($cartaoCredito, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cartão de crédito atualizado com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function destroy(CartaoCredito $cartaoCredito): RedirectResponse
    {
        $this->authorize('delete', $cartaoCredito);

        $this->cartoesCredito->excluir($cartaoCredito);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cartão de crédito excluído com sucesso.']);

        return Redirect::route('contas.index');
    }
}
