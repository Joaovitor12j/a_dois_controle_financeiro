<?php

namespace App\Http\Controllers;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\ValueObjects\Competencia;
use App\Http\Requests\DesfazerPagamentoDespesaRequest;
use App\Http\Requests\MarcarComoPagaDespesaRequest;
use App\Http\Requests\StoreDespesaRequest;
use App\Http\Requests\UpdateDespesaRequest;
use App\Models\CategoriaDespesa;
use App\Models\Conta;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Models\Scopes\DonoScope;
use App\Services\Financeiro\DespesaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DespesaController extends Controller
{
    public function __construct(
        private readonly DespesaService $despesas,
        private readonly CalculadoraCompetenciaDespesa $calculadora,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Despesa::class);

        $competencia = Competencia::deData(now());

        $ocorrencias = Despesa::with([
            'formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
            'formaPagamento.conta.usuario',
            'categoriaDespesa',
            'movimentacoes.formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
            'movimentacoes.formaPagamento.conta.usuario',
        ])
            ->get()
            ->filter(fn (Despesa $despesa) => $this->calculadora->existeNaCompetencia($despesa, $competencia))
            ->map(function (Despesa $despesa) use ($competencia) {
                $movimentacao = $despesa->movimentacoes->first(
                    fn ($m) => $m->competencia->equals($competencia)
                );

                return [
                    'despesa' => $despesa,
                    'competencia' => (string) $competencia,
                    'paga' => $movimentacao !== null,
                    'numero_parcela' => $despesa->ehParcelada()
                        ? $this->calculadora->numeroParcela($despesa, $competencia)
                        : null,
                    'movimentacao' => $movimentacao,
                ];
            })
            ->values();

        return Inertia::render('Despesas/Index', [
            'ocorrencias' => $ocorrencias,
            'competencia' => (string) $competencia,
            'categoriasDespesa' => CategoriaDespesa::orderBy('nome')->get(),
            'formasPagamento' => FormaPagamento::whereIn('conta_id', Conta::pluck('id'))
                ->with(['cartaoCredito', 'conta:id,nome'])
                ->get()
                ->sortBy(fn (FormaPagamento $forma) => "{$forma->conta->nome} $forma->nome")
                ->values(),
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
            Competencia::deString($request->validated('competencia')),
            $request->validated('forma_pagamento_id'),
            $request->validated('data_pagamento'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Despesa marcada como paga.']);

        return Redirect::route('despesas.index');
    }

    public function desfazerPagamento(DesfazerPagamentoDespesaRequest $request, Despesa $despesa): RedirectResponse
    {
        $this->authorize('update', $despesa);

        $this->despesas->desfazerPagamento($despesa, Competencia::deString($request->validated('competencia')));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

        return Redirect::route('despesas.index');
    }
}
