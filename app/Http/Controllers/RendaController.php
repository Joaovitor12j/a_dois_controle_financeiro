<?php

namespace App\Http\Controllers;

use App\Domain\Financeiro\CalculadoraOcorrenciaRenda;
use App\Domain\ValueObjects\Competencia;
use App\Http\Requests\DesfazerRecebimentoRendaRequest;
use App\Http\Requests\MarcarComoRecebidaRendaRequest;
use App\Http\Requests\StoreRendaRequest;
use App\Http\Requests\UpdateRendaRequest;
use App\Models\Movimentacao;
use App\Models\Renda;
use App\Services\Financeiro\RendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class RendaController extends Controller
{
    public function __construct(
        private readonly RendaService $rendas,
        private readonly CalculadoraOcorrenciaRenda $calculadora,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Renda::class);

        $competencia = Competencia::deData(now());

        $ocorrencias = Renda::with([
            'categoriaRenda',
            'conta.usuario',
            'movimentacoes.formaPagamento' => fn ($query) => $query->withTrashed(),
            'movimentacoes.formaPagamento.conta.usuario',
        ])
            ->get()
            ->filter(fn (Renda $renda) => $this->calculadora->existeNaCompetencia($renda, $competencia))
            ->map(function (Renda $renda) use ($competencia) {
                $movimentacao = $renda->movimentacoes->first(
                    fn (Movimentacao $movimentacao) => $movimentacao->competencia->equals($competencia)
                );

                return [
                    'renda' => $renda,
                    'competencia' => (string) $competencia,
                    'recebida' => $movimentacao !== null,
                    'movimentacao' => $movimentacao,
                    'formas_pagamento_elegiveis' => $renda->formasPagamentoElegiveisParaRecebimento(),
                ];
            })
            ->values();

        return Inertia::render('Rendas/Index', [
            'ocorrencias' => $ocorrencias,
            'competencia' => (string) $competencia,
            'contas' => $this->rendas->contasDisponiveis(),
            'categoriasRenda' => $this->rendas->categoriasDisponiveis(),
        ]);
    }

    public function store(StoreRendaRequest $request): RedirectResponse
    {
        $this->authorize('create', Renda::class);

        $this->rendas->criar($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Renda criada com sucesso.']);

        return Redirect::route('rendas.index');
    }

    public function update(UpdateRendaRequest $request, Renda $renda): RedirectResponse
    {
        $this->authorize('update', $renda);

        $this->rendas->atualizar($renda, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Renda atualizada com sucesso.']);

        return Redirect::route('rendas.index');
    }

    public function destroy(Renda $renda): RedirectResponse
    {
        $this->authorize('delete', $renda);

        $this->rendas->excluir($renda);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Renda excluída com sucesso.']);

        return Redirect::route('rendas.index');
    }

    public function marcarComoRecebida(MarcarComoRecebidaRendaRequest $request, Renda $renda): RedirectResponse
    {
        $this->authorize('update', $renda);

        $this->rendas->marcarComoRecebida(
            $renda,
            Competencia::deString($request->validated('competencia')),
            $request->validated('forma_pagamento_id'),
            $request->validated('data_recebimento'),
            $request->validated('valor'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Renda marcada como recebida.']);

        return Redirect::route('rendas.index');
    }

    public function desfazerRecebimento(DesfazerRecebimentoRendaRequest $request, Renda $renda): RedirectResponse
    {
        $this->authorize('update', $renda);

        $this->rendas->desfazerRecebimento($renda, Competencia::deString($request->validated('competencia')));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Recebimento desfeito.']);

        return Redirect::route('rendas.index');
    }
}
