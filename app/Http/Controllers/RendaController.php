<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRendaRequest;
use App\Http\Requests\UpdateRendaRequest;
use App\Models\Renda;
use App\Services\Financeiro\RendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class RendaController extends Controller
{
    public function __construct(private readonly RendaService $rendas) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Renda::class);

        return Inertia::render('Rendas/Index', [
            'rendas' => $this->rendas->listar(),
            'contas' => $this->rendas->contasDisponiveis(),
            'categoriasRenda' => $this->rendas->categoriasDisponiveis(),
        ]);
    }

    public function store(StoreRendaRequest $request): RedirectResponse
    {
        $this->authorize('create', Renda::class);

        $this->rendas->criar($request->validated());

        return Redirect::route('rendas.index');
    }

    public function update(UpdateRendaRequest $request, Renda $renda): RedirectResponse
    {
        $this->authorize('update', $renda);

        $this->rendas->atualizar($renda, $request->validated());

        return Redirect::route('rendas.index');
    }

    public function destroy(Renda $renda): RedirectResponse
    {
        $this->authorize('delete', $renda);

        $this->rendas->excluir($renda);

        return Redirect::route('rendas.index');
    }
}
