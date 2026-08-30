<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormaPagamentoRequest;
use App\Http\Requests\UpdateFormaPagamentoRequest;
use App\Models\FormaPagamento;
use App\Services\Financeiro\FormaPagamentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class FormaPagamentoController extends Controller
{
    public function __construct(private readonly FormaPagamentoService $formasPagamento) {}

    /**
     * @throws Throwable
     */
    public function store(StoreFormaPagamentoRequest $request): RedirectResponse
    {
        $this->formasPagamento->criar($request->validated());

        return Redirect::route('contas.index');
    }

    public function update(UpdateFormaPagamentoRequest $request, FormaPagamento $formaPagamento): RedirectResponse
    {
        $this->formasPagamento->atualizar($formaPagamento, $request->validated());

        return Redirect::route('contas.index');
    }

    public function destroy(FormaPagamento $formaPagamento): RedirectResponse
    {
        $this->authorize('delete', $formaPagamento);

        $this->formasPagamento->excluir($formaPagamento);

        return Redirect::route('contas.index');
    }
}
