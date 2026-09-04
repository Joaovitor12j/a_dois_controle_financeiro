<?php

namespace App\Http\Controllers;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\ValueObjects\Competencia;
use App\Enums\ContextoDespesa;
use App\Http\Requests\DesfazerPagamentoDespesaRequest;
use App\Http\Requests\FiltrosDespesaRequest;
use App\Http\Requests\MarcarComoPagaDespesaRequest;
use App\Http\Requests\StoreDespesaRequest;
use App\Http\Requests\UpdateDespesaRequest;
use App\Models\CategoriaDespesa;
use App\Models\Conta;
use App\Models\Despesa;
use App\Models\FormaPagamento;
use App\Services\Financeiro\DespesaService;
use Carbon\Carbon;
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

    public function index(FiltrosDespesaRequest $request): Response
    {
        $this->authorize('viewAny', Despesa::class);

        $competencia = $request->has(['ano', 'mes'])
            ? Competencia::deAnoMes((int) $request->query('ano'), (int) $request->query('mes'))
            : Competencia::deData(now());

        $contexto = $request->query('contexto') === 'individual'
            ? ContextoDespesa::Individual
            : ContextoDespesa::Conjunta;

        $hoje = Carbon::today();
        $filtros = $request->validated();

        $ocorrencias = $this->despesas->listarNoPeriodo($competencia, $contexto, $filtros)
            ->map(function (Despesa $despesa) use ($competencia, $hoje) {
                $movimentacao = $despesa->movimentacoes->first(
                    fn ($m) => $m->competencia->equals($competencia)
                );
                $paga = $movimentacao !== null;

                $status = match (true) {
                    $despesa->ehParcelada() => $paga ? 'paga' : 'pendente',
                    $paga => 'paga',
                    $this->calculadora->vencimento($despesa, $competencia)->lt($hoje) => 'vencida',
                    default => 'pendente',
                };

                return [
                    'despesa' => $despesa,
                    'competencia' => (string) $competencia,
                    'paga' => $paga,
                    'status' => $status,
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
            'contexto' => $contexto->value,
            'filtros' => $filtros,
            'categoriasDespesa' => CategoriaDespesa::orderBy('nome')->get(),
            'formasPagamento' => FormaPagamento::whereIn('conta_id', Conta::pluck('id'))
                ->with(['cartaoCredito', 'conta:id,nome'])
                ->get()
                ->sortBy(fn (FormaPagamento $forma) => "{$forma->conta->nome} $forma->nome")
                ->values(),
            'formasPagamentoFiltro' => $this->despesas->opcoesFormaPagamento($competencia, $contexto),
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

        $this->despesas->atualizar($despesa, $request->safe()->except('tipo_lancamento'));

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
