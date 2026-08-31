<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContaRequest;
use App\Http\Requests\UpdateContaRequest;
use App\Models\Conta;
use App\Services\Financeiro\ContaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ContaController extends Controller
{
    public function __construct(private readonly ContaService $contas) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Conta::class);

        return Inertia::render('Contas/Index', [
            'contas' => $this->contas->listar(),
        ]);
    }

    public function store(StoreContaRequest $request): RedirectResponse
    {
        $this->authorize('create', Conta::class);

        $this->contas->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conta criada com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function update(UpdateContaRequest $request, Conta $conta): RedirectResponse
    {
        $this->authorize('update', $conta);

        $this->contas->atualizar($conta, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conta atualizada com sucesso.']);

        return Redirect::route('contas.index');
    }

    public function destroy(Conta $conta): RedirectResponse
    {
        $this->authorize('delete', $conta);

        $this->contas->excluir($conta);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conta excluída com sucesso.']);

        return Redirect::route('contas.index');
    }
}
