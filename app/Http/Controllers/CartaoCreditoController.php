<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartaoCreditoRequest;
use App\Http\Requests\UpdateCartaoCreditoRequest;
use App\Models\CartaoCredito;
use App\Services\Financeiro\CartaoCreditoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class CartaoCreditoController extends Controller
{
    public function __construct(private readonly CartaoCreditoService $cartoesCredito) {}

    public function store(StoreCartaoCreditoRequest $request): RedirectResponse
    {
        $this->cartoesCredito->criar($request->validated());

        return Redirect::route('contas.index');
    }

    public function update(UpdateCartaoCreditoRequest $request, CartaoCredito $cartaoCredito): RedirectResponse
    {
        $this->cartoesCredito->atualizar($cartaoCredito, $request->validated());

        return Redirect::route('contas.index');
    }

    public function destroy(CartaoCredito $cartaoCredito): RedirectResponse
    {
        $this->authorize('delete', $cartaoCredito);

        $this->cartoesCredito->excluir($cartaoCredito);

        return Redirect::route('contas.index');
    }
}
