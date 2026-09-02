<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesfazerPagamentoDespesaRequest;
use App\Http\Requests\MarcarComoPagaDespesaRequest;
use App\Http\Requests\StoreDespesaRequest;
use App\Http\Requests\UpdateDespesaRequest;
use App\Models\Despesa;
use App\Services\Financeiro\DespesaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DespesaController extends Controller
{
    public function __construct(private readonly DespesaService $despesas) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Despesa::class);

        return Inertia::render('Despesas/Index', [
            'despesas' => $this->despesas->listar(),
            'categoriasDespesa' => $this->despesas->categoriasDisponiveis(),
            'formasPagamento' => $this->despesas->formasPagamentoDisponiveis(),
        ]);
    }

    public function store(StoreDespesaRequest $request): RedirectResponse
    {
        $this->authorize('create', Despesa::class);

        $this->despesas->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Despesa criada com sucesso.']);

        return Redirect::route('despesas.index');
    }

    public function update(UpdateDespesaRequest $request, Despesa $despesa): RedirectResponse
    {
        $this->authorize('update', $despesa);

        $this->despesas->atualizar($despesa, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Despesa atualizada com sucesso.']);

        return Redirect::route('despesas.index');
    }

    public function destroy(Despesa $despesa): RedirectResponse
    {
        $this->authorize('delete', $despesa);

        $this->despesas->excluir($despesa);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Despesa excluída com sucesso.']);

        return Redirect::route('despesas.index');
    }

    public function marcarComoPaga(MarcarComoPagaDespesaRequest $request, Despesa $despesa): RedirectResponse
    {
        $this->authorize('update', $despesa);

        $this->despesas->marcarComoPaga(
            $despesa,
            $request->validated('forma_pagamento_id'),
            $request->validated('data_pagamento'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Despesa marcada como paga.']);

        return Redirect::route('despesas.index');
    }

    public function desfazerPagamento(DesfazerPagamentoDespesaRequest $request, Despesa $despesa): RedirectResponse
    {
        $this->authorize('update', $despesa);

        $this->despesas->desfazerPagamento($despesa);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

        return Redirect::route('despesas.index');
    }
}
