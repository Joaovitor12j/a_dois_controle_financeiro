<?php

namespace App\Services\Financeiro;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\ValueObjects\Competencia;
use App\Enums\ContextoDespesa;
use App\Enums\FiltroStatusPagamento;
use App\Models\Despesa;
use App\Models\Movimentacao;
use App\Models\Scopes\DonoScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class DespesaService
{
    public function __construct(
        private readonly CalculadoraCompetenciaDespesa $calculadora,
    ) {}

    /** @return Collection<int, Despesa> */
    public function listar(): Collection
    {
        return Despesa::query()
            ->with(['formaPagamento.conta.usuario', 'categoriaDespesa'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array{categoria_despesa_id?: string, tipo?: string, forma_pagamento_id?: string, status?: string}  $filtros
     * @return Collection<int, Despesa>
     */
    public function listarNoPeriodo(Competencia $competencia, ContextoDespesa $contexto, array $filtros = []): Collection
    {
        return Despesa::with([
            'formaPagamento' => fn ($query) => $query->withTrashed(),
            'formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
            'formaPagamento.conta.usuario',
            'categoriaDespesa',
            'movimentacoes.formaPagamento' => fn ($query) => $query->withTrashed(),
            'movimentacoes.formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
            'movimentacoes.formaPagamento.conta.usuario',
        ])
            ->where('contexto', $contexto->value)
            ->when(isset($filtros['categoria_despesa_id']), fn ($query) => $query->where('categoria_despesa_id', $filtros['categoria_despesa_id']))
            ->when(isset($filtros['tipo']), fn ($query) => $query->where('tipo_lancamento', $filtros['tipo']))
            ->get()
            ->filter(fn (Despesa $despesa) => $this->calculadora->existeNaCompetencia($despesa, $competencia))
            ->filter(fn (Despesa $despesa) => $this->passaFiltroFormaPagamento($despesa, $competencia, $filtros['forma_pagamento_id'] ?? null))
            ->filter(fn (Despesa $despesa) => $this->passaFiltroStatus($despesa, $competencia, $filtros['status'] ?? null))
            ->values();
    }

    private function passaFiltroFormaPagamento(Despesa $despesa, Competencia $competencia, ?string $formaPagamentoId): bool
    {
        if ($formaPagamentoId === null) {
            return true;
        }

        if ($despesa->ehParcelada()) {
            return $despesa->forma_pagamento_id === $formaPagamentoId;
        }

        return $this->movimentacaoDaCompetencia($despesa, $competencia)?->forma_pagamento_id === $formaPagamentoId;
    }

    private function passaFiltroStatus(Despesa $despesa, Competencia $competencia, ?string $status): bool
    {
        if ($status === null) {
            return true;
        }

        $paga = $this->movimentacaoDaCompetencia($despesa, $competencia) !== null;

        return $status === FiltroStatusPagamento::Paga->value ? $paga : ! $paga;
    }

    private function movimentacaoDaCompetencia(Despesa $despesa, Competencia $competencia): ?Movimentacao
    {
        return $despesa->movimentacoes->first(fn (Movimentacao $movimentacao) => $movimentacao->competencia?->equals($competencia));
    }

    /** @return list<array{id: string, nome: string}> */
    public function opcoesFormaPagamento(Competencia $competencia, ContextoDespesa $contexto): array
    {
        return $this->listarNoPeriodo($competencia, $contexto)
            ->map(fn (Despesa $despesa) => $despesa->ehParcelada()
                ? $despesa->formaPagamento
                : $this->movimentacaoDaCompetencia($despesa, $competencia)?->formaPagamento)
            ->filter()
            ->unique('id')
            ->map(fn ($forma) => ['id' => $forma->id, 'nome' => $forma->nome])
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $atributos */
    public function criar(array $atributos): Despesa
    {
        $paga = (bool) ($atributos['paga'] ?? false);
        $formaPagamentoId = $atributos['forma_pagamento_id'] ?? null;
        $dataPagamento = $atributos['data_pagamento'] ?? null;

        unset($atributos['paga'], $atributos['data_pagamento']);

        if ($paga) {
            unset($atributos['forma_pagamento_id']);
        }

        $despesa = Despesa::create([
            ...$atributos,
            'usuario_id' => Auth::id(),
        ]);

        if ($paga) {
            $this->marcarComoPaga($despesa, Competencia::deData(Carbon::parse($despesa->data_vencimento)), $formaPagamentoId, $dataPagamento);
        }

        return $despesa;
    }

    /** @param array<string, mixed> $atributos */
    public function atualizar(Despesa $despesa, array $atributos): Despesa
    {
        $despesa->update($atributos);

        return $despesa;
    }

    public function excluir(Despesa $despesa): void
    {
        $despesa->delete();
    }

    public function estaPagaNaCompetencia(Despesa $despesa, Competencia $competencia): bool
    {
        return $despesa->movimentacoes()->where('competencia', $competencia->paraData())->exists();
    }

    public function marcarComoPaga(
        Despesa $despesa,
        Competencia $competencia,
        ?string $formaPagamentoId,
        string $dataPagamento,
    ): Movimentacao {
        $formaPagamentoId = $despesa->ehParcelada() ? $despesa->forma_pagamento_id : $formaPagamentoId;

        return Movimentacao::create([
            'forma_pagamento_id' => $formaPagamentoId,
            'valor' => $despesa->valor->negated(),
            'data' => $dataPagamento,
            'despesa_id' => $despesa->id,
            'competencia' => $competencia->paraData(),
        ]);
    }

    public function desfazerPagamento(Despesa $despesa, Competencia $competencia): void
    {
        $despesa->movimentacoes()->where('competencia', $competencia->paraData())->delete();
    }
}
