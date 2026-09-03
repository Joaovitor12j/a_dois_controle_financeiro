<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaDespesaRequest;
use App\Http\Requests\UpdateCategoriaDespesaRequest;
use App\Models\CategoriaDespesa;
use App\Services\Financeiro\CategoriaDespesaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CategoriaDespesaController extends Controller
{
    public function __construct(private readonly CategoriaDespesaService $categoriasDespesa) {}

    public function store(StoreCategoriaDespesaRequest $request): RedirectResponse
    {
        $this->authorize('create', CategoriaDespesa::class);

        $this->categoriasDespesa->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de despesa criada com sucesso.']);

        return Redirect::route('categorias.index');
    }

    public function update(UpdateCategoriaDespesaRequest $request, CategoriaDespesa $categoriaDespesa): RedirectResponse
    {
        $this->authorize('update', $categoriaDespesa);

        $this->categoriasDespesa->atualizar($categoriaDespesa, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de despesa atualizada com sucesso.']);

        return Redirect::route('categorias.index');
    }

    public function destroy(CategoriaDespesa $categoriaDespesa): RedirectResponse
    {
        $this->authorize('delete', $categoriaDespesa);

        $this->categoriasDespesa->excluir($categoriaDespesa);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de despesa excluída com sucesso.']);

        return Redirect::route('categorias.index');
    }
}
