<?php

namespace App\Http\Controllers;

use App\Models\CategoriaDespesa;
use App\Models\CategoriaRenda;
use App\Services\Financeiro\CategoriaDespesaService;
use App\Services\Financeiro\CategoriaRendaService;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function __construct(
        private readonly CategoriaRendaService $categoriasRenda,
        private readonly CategoriaDespesaService $categoriasDespesa,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', CategoriaRenda::class);
        $this->authorize('viewAny', CategoriaDespesa::class);

        return Inertia::render('Categorias/Index', [
            'categoriasRenda' => $this->categoriasRenda->listar(),
            'categoriasDespesa' => $this->categoriasDespesa->listar(),
        ]);
    }
}
