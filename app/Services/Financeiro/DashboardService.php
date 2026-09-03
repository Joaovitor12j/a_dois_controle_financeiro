<?php

namespace App\Services\Financeiro;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\Financeiro\CalculadoraOcorrenciaRenda;
use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\ContextoDespesa;
use App\Enums\TipoLancamentoDespesa;
use App\Enums\TipoRecorrencia;
use App\Models\Despesa;
use App\Models\Renda;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class DashboardService
{
    public function __construct(
        private readonly CalculadoraCompetenciaDespesa $calculadoraDespesa,
        private readonly CalculadoraOcorrenciaRenda $calculadoraRenda,
    ) {}

    /** @return array<string, mixed> */
    public function obterResumo(string $modo, Competencia $competencia): array
    {
        $usuarios = $this->usuariosDoEscopo($modo);

        $rendas = $this->rendasNoPeriodo($modo, $usuarios, $competencia);
        $despesas = $this->despesasNoPeriodo($modo, $competencia);

        $atual = $this->resumirPeriodo($rendas, $despesas, $competencia);
        $anterior = $this->resumirPeriodo(
            $this->rendasNoPeriodo($modo, $usuarios, $competencia->anterior()),
            $this->despesasNoPeriodo($modo, $competencia->anterior()),
            $competencia->anterior(),
        );

        return [
            'modo' => $modo,
            'competencia' => (string) $competencia,
            'despesaRotulo' => $modo === 'casal' ? 'Despesa conjunta' : 'Despesa',
            'resumo' => [
                'saldo' => $atual['saldo']->cents,
                'saldoDeltaPct' => $this->variacaoPercentual($atual['saldo'], $anterior['saldo']),
                'receita' => $atual['receita']->cents,
                'receitaDeltaPct' => $this->variacaoPercentual($atual['receita'], $anterior['receita']),
                'despesa' => $atual['despesa']->cents,
                'despesaDeltaPct' => $this->variacaoPercentual($atual['despesa'], $anterior['despesa']),
                'resultado' => $atual['resultado']->cents,
                'resultadoDeltaPct' => $this->variacaoPercentual($atual['resultado'], $anterior['resultado']),
            ],
            'serieSaldo' => $atual['serie'],
            'despesaPorCategoria' => $this->agruparPorCategoriaDespesa($despesas),
            'receitaPorCategoria' => $this->agruparPorCategoriaRenda($rendas),
            'pendencias' => $this->pendencias($despesas, $rendas, $competencia),
            'alertas' => $this->alertas($despesas, $rendas, $competencia),
            'contribuicao' => $modo === 'casal' ? $this->contribuicaoPorPessoa($usuarios, $rendas, $despesas, $competencia) : null,
        ];
    }

    /** @return Collection<int, Usuario> */
    private function usuariosDoEscopo(string $modo): Collection
    {
        if ($modo !== 'casal') {
            return Usuario::query()->where('id', Auth::id())->get();
        }

        /** @var array<int, array{email: string}> $iniciais */
        $iniciais = config('usuarios.iniciais');

        return Usuario::query()
            ->whereIn('email', array_column($iniciais, 'email'))
            ->get();
    }

    /** @param Collection<int, Usuario> $usuarios
     * @return Collection<int, Renda> */
    private function rendasNoPeriodo(string $modo, Collection $usuarios, Competencia $competencia): Collection
    {
        $query = $modo === 'casal'
            ? Renda::withoutGlobalScope(DonoScope::class)->whereIn('usuario_id', $usuarios->pluck('id'))
            : Renda::query();

        return $query->with(['categoriaRenda', 'movimentacoes'])
            ->get()
            ->filter(fn (Renda $renda) => $this->calculadoraRenda->existeNaCompetencia($renda, $competencia))
            ->values();
    }

    /** @return Collection<int, Despesa> */
    private function despesasNoPeriodo(string $modo, Competencia $competencia): Collection
    {
        $despesas = Despesa::with([
            'categoriaDespesa',
            'movimentacoes.formaPagamento' => fn ($query) => $query->withTrashed(),
            'movimentacoes.formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
        ])
            ->get()
            ->filter(fn (Despesa $despesa) => $this->calculadoraDespesa->existeNaCompetencia($despesa, $competencia));

        if ($modo === 'casal') {
            $despesas = $despesas->filter(fn (Despesa $despesa) => $despesa->ehConjunta());
        }

        return $despesas->values();
    }

    private function movimentacaoDaCompetencia(Despesa|Renda $entidade, Competencia $competencia): ?object
    {
        return $entidade->movimentacoes->first(
            fn ($movimentacao) => $movimentacao->competencia?->equals($competencia)
        );
    }

    /**
     * @param  Collection<int, Renda>  $rendas
     * @param  Collection<int, Despesa>  $despesas
     * @return array{receita: Money, despesa: Money, resultado: Money, saldo: Money, serie: list<array{dia: int, valor: int, tipo: string}>}
     */
    private function resumirPeriodo(Collection $rendas, Collection $despesas, Competencia $competencia): array
    {
        $receita = $rendas->reduce(fn (Money $carry, Renda $renda) => $carry->plus($renda->valor), Money::zero());
        $despesaTotal = $despesas->reduce(fn (Money $carry, Despesa $despesa) => $carry->plus($despesa->valor), Money::zero());

        $ultimoDia = Carbon::create($competencia->ano, $competencia->mes, 1)->daysInMonth;
        $corte = $this->diaDeCorte($competencia, $ultimoDia);

        $certos = [];
        $projetados = [];

        foreach ($rendas as $renda) {
            $movimentacao = $this->movimentacaoDaCompetencia($renda, $competencia);

            if ($movimentacao !== null) {
                $certos[] = ['dia' => min(Carbon::parse($movimentacao->data)->day, $ultimoDia), 'valor' => $renda->valor];

                continue;
            }

            $dia = min($this->calculadoraRenda->diaDoEvento($renda), $ultimoDia);
            $projetados[] = ['dia' => $dia, 'valor' => $renda->valor];
        }

        foreach ($despesas as $despesa) {
            if ($despesa->tipo_lancamento === TipoLancamentoDespesa::Parcelada) {
                continue;
            }

            $movimentacao = $this->movimentacaoDaCompetencia($despesa, $competencia);
            $valorNegativo = $despesa->valor->negated();

            if ($movimentacao !== null) {
                $certos[] = ['dia' => min(Carbon::parse($movimentacao->data)->day, $ultimoDia), 'valor' => $valorNegativo];

                continue;
            }

            $diaVencimento = $despesa->tipo_lancamento === TipoLancamentoDespesa::Unica
                ? Carbon::parse($despesa->data_vencimento)->day
                : $despesa->dia_vencimento;

            $projetados[] = ['dia' => min($diaVencimento, $ultimoDia), 'valor' => $valorNegativo];
        }

        [$serieRealizada, $saldoNoCorte] = $this->serieAcumulada($certos, min(1, $corte), $corte, Money::zero());
        [$serieProjetada] = $this->serieAcumulada($projetados, $corte, $ultimoDia, $saldoNoCorte);

        $serie = [
            ...array_map(fn (array $ponto) => [...$ponto, 'tipo' => 'realizado'], $serieRealizada),
            ...array_map(fn (array $ponto) => [...$ponto, 'tipo' => 'projetado'], $serieProjetada),
        ];

        return [
            'receita' => $receita,
            'despesa' => $despesaTotal,
            'resultado' => $receita->minus($despesaTotal),
            'saldo' => $saldoNoCorte,
            'serie' => $serie,
        ];
    }

    private function diaDeCorte(Competencia $competencia, int $ultimoDia): int
    {
        $hoje = Competencia::deData(now());

        if ($competencia->equals($hoje)) {
            return now()->day;
        }

        return $competencia->ehAnterior($hoje) ? $ultimoDia : 0;
    }

    /**
     * @param  list<array{dia: int, valor: Money}>  $eventos
     * @return array{0: list<array{dia: int, valor: int}>, 1: Money}
     */
    private function serieAcumulada(array $eventos, int $diaInicial, int $diaFinal, Money $partidaDe): array
    {
        usort($eventos, fn (array $a, array $b) => max($a['dia'], $diaInicial) <=> max($b['dia'], $diaInicial));

        $acumulado = $partidaDe;
        $pontos = [['dia' => $diaInicial, 'valor' => $acumulado->cents]];

        foreach ($eventos as $evento) {
            $acumulado = $acumulado->plus($evento['valor']);
            $pontos[] = ['dia' => max($evento['dia'], $diaInicial), 'valor' => $acumulado->cents];
        }

        if ($pontos[count($pontos) - 1]['dia'] !== $diaFinal) {
            $pontos[] = ['dia' => $diaFinal, 'valor' => $acumulado->cents];
        }

        return [$pontos, $acumulado];
    }

    private function variacaoPercentual(Money $atual, Money $anterior): ?float
    {
        if ($anterior->isZero()) {
            return null;
        }

        return (($atual->cents - $anterior->cents) / abs($anterior->cents)) * 100;
    }

    /** @param Collection<int, Despesa> $despesas
     * @return list<array{nome: string, cor: string, valor: int}> */
    private function agruparPorCategoriaDespesa(Collection $despesas): array
    {
        return $despesas
            ->groupBy('categoria_despesa_id')
            ->map(fn (Collection $grupo) => [
                'nome' => $grupo->first()->categoriaDespesa->nome,
                'cor' => $grupo->first()->categoriaDespesa->cor,
                'valor' => $grupo->reduce(fn (Money $carry, Despesa $d) => $carry->plus($d->valor), Money::zero())->cents,
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /** @param Collection<int, Renda> $rendas
     * @return list<array{nome: string, cor: string, valor: int}> */
    private function agruparPorCategoriaRenda(Collection $rendas): array
    {
        return $rendas
            ->groupBy('categoria_renda_id')
            ->map(fn (Collection $grupo) => [
                'nome' => $grupo->first()->categoriaRenda->nome,
                'cor' => $grupo->first()->categoriaRenda->cor,
                'valor' => $grupo->reduce(fn (Money $carry, Renda $r) => $carry->plus($r->valor), Money::zero())->cents,
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Despesa>  $despesas
     * @param  Collection<int, Renda>  $rendas
     * @return list<array{id: string, tipo: string, descricao: string, contexto: string|null, data: string, valor: int}>
     */
    private function pendencias(Collection $despesas, Collection $rendas, Competencia $competencia): array
    {
        $itensDespesa = $despesas
            ->filter(fn (Despesa $d) => $d->tipo_lancamento !== TipoLancamentoDespesa::Parcelada)
            ->filter(fn (Despesa $d) => $this->movimentacaoDaCompetencia($d, $competencia) === null)
            ->map(fn (Despesa $d) => [
                'id' => $d->id,
                'tipo' => 'despesa',
                'descricao' => $d->descricao,
                'contexto' => $d->contexto->value,
                'data' => $this->vencimentoDespesa($d, $competencia),
                'valor' => $d->valor->cents,
            ]);

        $itensRenda = $rendas
            ->filter(fn (Renda $r) => $this->movimentacaoDaCompetencia($r, $competencia) === null)
            ->map(fn (Renda $r) => [
                'id' => $r->id,
                'tipo' => 'renda',
                'descricao' => $r->descricao,
                'contexto' => null,
                'data' => $this->dataOcorrenciaRenda($r, $competencia),
                'valor' => $r->valor->cents,
            ]);

        return $itensDespesa->concat($itensRenda)
            ->sortBy('data')
            ->values()
            ->all();
    }

    private function vencimentoDespesa(Despesa $despesa, Competencia $competencia): string
    {
        if ($despesa->tipo_lancamento === TipoLancamentoDespesa::Unica) {
            return Carbon::parse($despesa->data_vencimento)->toDateString();
        }

        $ultimoDia = Carbon::create($competencia->ano, $competencia->mes, 1)->daysInMonth;

        return Carbon::create($competencia->ano, $competencia->mes, min($despesa->dia_vencimento, $ultimoDia))->toDateString();
    }

    private function dataOcorrenciaRenda(Renda $renda, Competencia $competencia): string
    {
        if ($renda->tipo_recorrencia === TipoRecorrencia::Unica) {
            return Carbon::parse($renda->data_recebimento)->toDateString();
        }

        $ultimoDia = Carbon::create($competencia->ano, $competencia->mes, 1)->daysInMonth;

        return Carbon::create($competencia->ano, $competencia->mes, min($renda->dia_recebimento, $ultimoDia))->toDateString();
    }

    /**
     * @param  Collection<int, Despesa>  $despesas
     * @param  Collection<int, Renda>  $rendas
     * @return list<array{titulo: string, detalhe: string, valor: int, nivel: string}>
     */
    private function alertas(Collection $despesas, Collection $rendas, Competencia $competencia): array
    {
        $hoje = Carbon::today();

        return collect($this->pendencias($despesas, $rendas, $competencia))
            ->map(fn (array $item) => [...$item, 'dias' => $hoje->diffInDays(Carbon::parse($item['data']), false)])
            ->filter(fn (array $item) => $item['dias'] <= 7)
            ->sortBy('dias')
            ->map(function (array $item) {
                $ehRenda = $item['tipo'] === 'renda';

                $titulo = match (true) {
                    $ehRenda && $item['dias'] < 0 => "{$item['descricao']} deveria ter sido recebida há ".abs((int) $item['dias']).' dia(s)',
                    $ehRenda => "{$item['descricao']} a receber em {$item['dias']} dia(s)",
                    $item['dias'] < 0 => "{$item['descricao']} venceu há ".abs((int) $item['dias']).' dia(s)',
                    default => "{$item['descricao']} vence em {$item['dias']} dia(s)",
                };

                $detalhe = $ehRenda
                    ? 'Renda a receber'
                    : ($item['contexto'] === ContextoDespesa::Conjunta->value ? 'Despesa conjunta' : 'Despesa individual');

                return [
                    'titulo' => $titulo,
                    'detalhe' => $detalhe.' · '.Carbon::parse($item['data'])->format('d/m'),
                    'valor' => $item['valor'],
                    'nivel' => $item['dias'] < 0 || $item['dias'] <= 3 ? 'vinho' : 'ouro',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Usuario>  $usuarios
     * @param  Collection<int, Renda>  $rendas
     * @param  Collection<int, Despesa>  $despesas
     * @return array{receita: list<array{usuarioId: string, nome: string, cor: string, valor: int}>, despesa: list<array{usuarioId: string, nome: string, cor: string, valor: int}>}
     */
    private function contribuicaoPorPessoa(Collection $usuarios, Collection $rendas, Collection $despesas, Competencia $competencia): array
    {
        $rendasRecebidasPorUsuario = $rendas
            ->filter(fn (Renda $r) => $this->movimentacaoDaCompetencia($r, $competencia) !== null)
            ->groupBy('usuario_id');

        $despesasPagasPorUsuario = $despesas
            ->map(fn (Despesa $d) => ['despesa' => $d, 'movimentacao' => $this->movimentacaoDaCompetencia($d, $competencia)])
            ->filter(fn (array $item) => $item['movimentacao'] !== null)
            ->groupBy(fn (array $item) => (string) $item['movimentacao']->formaPagamento->conta->usuario_id);

        return [
            'receita' => $usuarios->map(fn (Usuario $usuario) => [
                'usuarioId' => $usuario->id,
                'nome' => $usuario->nome,
                'cor' => $usuario->cor,
                'valor' => $rendasRecebidasPorUsuario->get($usuario->id, collect())
                    ->reduce(fn (Money $carry, Renda $r) => $carry->plus($r->valor), Money::zero())->cents,
            ])->all(),
            'despesa' => $usuarios->map(fn (Usuario $usuario) => [
                'usuarioId' => $usuario->id,
                'nome' => $usuario->nome,
                'cor' => $usuario->cor,
                'valor' => $despesasPagasPorUsuario->get($usuario->id, collect())
                    ->reduce(fn (Money $carry, array $item) => $carry->plus($item['despesa']->valor), Money::zero())->cents,
            ])->all(),
        ];
    }
}
