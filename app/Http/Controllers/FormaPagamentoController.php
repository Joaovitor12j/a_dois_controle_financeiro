<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormaPagamentoRequest;
use App\Http\Requests\UpdateFormaPagamentoRequest;
use App\Models\FormaPagamento;
use App\Services\Financeiro\FormaPagamentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Throwable;

class FormaPagamentoController extends Controller
{
    public function __construct(private readonly FormaPagamentoService $formasPagamento) {}

    /**
     * @throws Throwable
     */
    public function store(StoreFormaPagamentoRequest $request): RedirectResponse
    {
        $this->authorize('create', FormaPagamento::class);

        $this->formasPagamento->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Forma de pagamento criada com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function update(UpdateFormaPagamentoRequest $request, FormaPagamento $formaPagamento): RedirectResponse
    {
        $this->formasPagamento->atualizar($formaPagamento, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Forma de pagamento atualizada com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function destroy(FormaPagamento $formaPagamento): RedirectResponse
    {
        $this->authorize('delete', $formaPagamento);

        $this->formasPagamento->excluir($formaPagamento);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Forma de pagamento excluída com sucesso.']);

        return Redirect::route('contas.index');
    }
}
