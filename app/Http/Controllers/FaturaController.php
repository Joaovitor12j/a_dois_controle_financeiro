<?php

namespace App\Http\Controllers;

use App\Domain\ValueObjects\Competencia;
use App\Http\Requests\DesfazerPagamentoFaturaRequest;
use App\Http\Requests\GerarFaturaRequest;
use App\Http\Requests\MarcarComoPagaFaturaRequest;
use App\Models\Conta;
use App\Models\Fatura;
use App\Models\FormaPagamento;
use App\Services\Financeiro\FaturaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class FaturaController extends Controller
{
    public function __construct(
        private readonly FaturaService $faturas,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Fatura::class);

        $faturas = Fatura::query()
            ->whereHas('cartaoCredito.formaPagamento.conta')
            ->with('cartaoCredito.formaPagamento.conta')
            ->orderByDesc('data_vencimento')
            ->get()
            ->map(fn (Fatura $fatura) => $this->faturas->recalcular($fatura))
            ->filter()
            ->map(function (Fatura $fatura) {
                $cartao = $fatura->cartaoCredito->formaPagamento;

                return [
                    'fatura' => $fatura,
                    'cartao' => $cartao,
                    'conta' => $cartao->conta,
                    'paga' => $fatura->estaPaga(),
                    'itens' => array_map(fn (array $item) => [
                        'despesa' => $item['despesa'],
                        'numeroParcela' => $item['numeroParcela'],
                        'valor' => $item['valor']->cents,
                        'paga' => $item['movimentacao'] !== null,
                    ], $this->faturas->itens($cartao, $fatura->competencia)),
                ];
            })
            ->values();

        $formasPagamento = FormaPagamento::whereIn('conta_id', Conta::pluck('id'))
            ->with(['cartaoCredito', 'conta:id,nome'])
            ->get()
            ->sortBy(fn (FormaPagamento $forma) => "{$forma->conta->nome} $forma->nome")
            ->values();

        return Inertia::render('Faturas/Index', [
            'faturas' => $faturas,
            'cartoes' => $formasPagamento->filter(fn (FormaPagamento $forma) => $forma->ehCredito())->values(),
            'formasPagamento' => $formasPagamento->filter(fn (FormaPagamento $forma) => ! $forma->ehCredito())->values(),
        ]);
    }

    public function store(GerarFaturaRequest $request): RedirectResponse
    {
        $this->authorize('create', Fatura::class);

        $cartao = FormaPagamento::findOrFail($request->validated('forma_pagamento_id'));

        $this->faturas->gerar($cartao, Competencia::deString($request->validated('competencia')));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura gerada com sucesso.']);

        return Redirect::route('faturas.index');
    }

    public function marcarComoPaga(MarcarComoPagaFaturaRequest $request, Fatura $fatura): RedirectResponse
    {
        $this->authorize('update', $fatura);

        $this->faturas->marcarComoPaga(
            $fatura,
            $request->validated('forma_pagamento_id'),
            $request->validated('data_pagamento'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fatura marcada como paga.']);

        return Redirect::route('faturas.index');
    }

    public function desfazerPagamento(DesfazerPagamentoFaturaRequest $request, Fatura $fatura): RedirectResponse
    {
        $this->authorize('update', $fatura);

        $this->faturas->desfazerPagamento($fatura);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pagamento desfeito.']);

        return Redirect::route('faturas.index');
    }
}
