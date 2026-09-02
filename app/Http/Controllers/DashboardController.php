<?php

namespace App\Http\Controllers;

use App\Domain\ValueObjects\Competencia;
use App\Models\CategoriaDespesa;
use App\Models\Conta;
use App\Models\FormaPagamento;
use App\Services\Financeiro\DashboardService;
use App\Services\Financeiro\RendaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly RendaService $rendas,
    ) {}

    public function index(Request $request): Response
    {
        $modo = $request->query('modo') === 'individual' ? 'individual' : 'casal';

        $competencia = $request->has(['ano', 'mes'])
            ? Competencia::deAnoMes((int) $request->query('ano'), (int) $request->query('mes'))
            : Competencia::deData(now());

        return Inertia::render('Dashboard', [
            ...$this->dashboard->obterResumo($modo, $competencia),
            'categoriasDespesa' => CategoriaDespesa::orderBy('nome')->get(),
            'formasPagamento' => FormaPagamento::whereIn('conta_id', Conta::pluck('id'))
                ->with(['cartaoCredito', 'conta:id,nome'])
                ->get()
                ->sortBy(fn (FormaPagamento $forma) => "{$forma->conta->nome} $forma->nome")
                ->values(),
            'contas' => $this->rendas->contasDisponiveis(),
            'categoriasRenda' => $this->rendas->categoriasDisponiveis(),
        ]);
    }
}
