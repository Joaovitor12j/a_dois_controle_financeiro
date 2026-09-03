<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaRendaRequest;
use App\Http\Requests\UpdateCategoriaRendaRequest;
use App\Models\CategoriaRenda;
use App\Services\Financeiro\CategoriaRendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CategoriaRendaController extends Controller
{
    public function __construct(private readonly CategoriaRendaService $categoriasRenda) {}

    public function store(StoreCategoriaRendaRequest $request): RedirectResponse
    {
        $this->authorize('create', CategoriaRenda::class);

        $this->categoriasRenda->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de renda criada com sucesso.']);

        return Redirect::route('categorias.index');
    }

    public function update(UpdateCategoriaRendaRequest $request, CategoriaRenda $categoriaRenda): RedirectResponse
    {
        $this->authorize('update', $categoriaRenda);

        $this->categoriasRenda->atualizar($categoriaRenda, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de renda atualizada com sucesso.']);

        return Redirect::route('categorias.index');
    }

    public function destroy(CategoriaRenda $categoriaRenda): RedirectResponse
    {
        $this->authorize('delete', $categoriaRenda);

        $this->categoriasRenda->excluir($categoriaRenda);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoria de renda excluída com sucesso.']);

        return Redirect::route('categorias.index');
    }
}
