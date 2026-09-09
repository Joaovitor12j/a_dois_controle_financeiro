<?php

namespace App\Services\Financeiro;

use App\Domain\Financeiro\CalculadoraCompetenciaDespesa;
use App\Domain\Financeiro\CalculadoraOcorrenciaRenda;
use App\Domain\ValueObjects\Competencia;
use App\Domain\ValueObjects\Money;
use App\Enums\FiltroStatusPagamento;
use App\Enums\TipoLancamentoDespesa;
use App\Enums\TipoRecorrencia;
use App\Models\Despesa;
use App\Models\Fatura;
use App\Models\FormaPagamento;
use App\Models\Movimentacao;
use App\Models\Renda;
use App\Models\Scopes\DonoScope;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class DashboardService
{
    private const PISO_SIGNIFICANCIA_CENTS = 5000;

    private const MAX_CATEGORIAS_VISIVEIS = 4;

    private const COR_OUTRAS = '#3A4B5F';

    public function __construct(
        private readonly CalculadoraCompetenciaDespesa $calculadoraDespesa,
        private readonly CalculadoraOcorrenciaRenda $calculadoraRenda,
    ) {}

    /**
     * @param  array{categoria_despesa_id?: string, tipo?: string, forma_pagamento_id?: string, status?: string, busca?: string}  $filtros
     * @return array<string, mixed>
     */
    public function obterResumo(string $modo, Competencia $competencia, array $filtros = [], ?string $pessoaId = null): array
    {
        $usuarios = $this->usuariosDoEscopo($modo);

        $rendas = $this->rendasNoPeriodo($modo, $usuarios, $competencia, $pessoaId);
        $despesas = $this->despesasNoPeriodo($modo, $competencia, $filtros);
        $faturas = $this->faturasNoPeriodo($modo, $competencia);

        $atual = $this->resumirPeriodo($rendas, $despesas, $faturas, $competencia, $pessoaId);
        $competenciaAnterior = $competencia->anterior();
        $anterior = $this->resumirPeriodo(
            $this->rendasNoPeriodo($modo, $usuarios, $competenciaAnterior, $pessoaId),
            $this->despesasNoPeriodo($modo, $competenciaAnterior, $filtros),
            $this->faturasNoPeriodo($modo, $competenciaAnterior),
            $competenciaAnterior,
            $pessoaId,
        );

        return [
            'modo' => $modo,
            'competencia' => (string) $competencia,
            'despesaRotulo' => $modo === 'casal' ? 'Despesa conjunta' : 'Despesa',
            'resumo' => [
                'saldo' => $atual['saldo']->cents,
                'saldoDelta' => $this->variacao($atual['saldo'], $anterior['saldo']),
                'receita' => $atual['receita']->cents,
                'receitaDelta' => $this->variacao($atual['receita'], $anterior['receita']),
                'despesa' => $atual['despesa']->cents,
                'despesaDelta' => $this->variacao($atual['despesa'], $anterior['despesa']),
                'resultado' => $atual['resultado']->cents,
                'resultadoDelta' => $this->variacao($atual['resultado'], $anterior['resultado']),
                'statusPeriodo' => $atual['statusPeriodo'],
                'temDespesaParcelada' => $despesas->contains(fn (Despesa $d) => $d->tipo_lancamento === TipoLancamentoDespesa::Parcelada),
            ],
            'despesaPorCategoria' => $this->agruparPorCategoriaDespesa($despesas, $competencia),
            'receitaPorCategoria' => $this->agruparPorCategoriaRenda($rendas),
            'despesaPorFormaPagamento' => $this->agruparPorFormaPagamento($despesas, $competencia),
            'individualXConjunta' => $modo === 'individual' ? $this->individualXConjunta($despesas) : null,
            'pendencias' => $this->pendencias($despesas, $rendas, $faturas, $competencia),
            'primeiroUso' => $this->primeiroUso(),
            'usuariosCasal' => $this->usuariosCasalResumo(),
        ];
    }

    /** @return list<array{id: string, nome: string, cor: string}> */
    private function usuariosCasalResumo(): array
    {
        return $this->usuariosDoEscopo('casal')
            ->map(fn (Usuario $usuario) => ['id' => $usuario->id, 'nome' => $usuario->nome, 'cor' => $usuario->corUsuario->cor])
            ->all();
    }

    /**
     * @param  array{categoria_despesa_id?: string, tipo?: string, forma_pagamento_id?: string, status?: string, busca?: string}  $filtros
     * @return array{serie: list<array{dia: int, valor: int, tipo: string}>, eventosPorDia: array<int, list<array{descricao: string, valor: int}>>}
     */
    public function obterSerieSaldo(string $modo, Competencia $competencia, array $filtros = [], ?string $pessoaId = null): array
    {
        $usuarios = $this->usuariosDoEscopo($modo);
        $rendas = $this->rendasNoPeriodo($modo, $usuarios, $competencia, $pessoaId);
        $despesas = $this->despesasNoPeriodo($modo, $competencia, $filtros);
        $faturas = $this->faturasNoPeriodo($modo, $competencia);

        $resumo = $this->resumirPeriodo($rendas, $despesas, $faturas, $competencia, $pessoaId);

        return [
            'serie' => $resumo['serie'],
            'eventosPorDia' => $resumo['eventosPorDia'],
        ];
    }

    /**
     * @param  array{categoria_despesa_id?: string, tipo?: string, forma_pagamento_id?: string, status?: string, busca?: string}  $filtros
     * @return array{receita: list<array{usuarioId: string, nome: string, cor: string, valor: int}>, despesa: list<array{usuarioId: string, nome: string, cor: string, valor: int}>}|null
     */
    public function obterContribuicaoPorPessoa(string $modo, Competencia $competencia, array $filtros = []): ?array
    {
        if ($modo !== 'casal') {
            return null;
        }

        $usuarios = $this->usuariosDoEscopo($modo);
        $rendas = $this->rendasNoPeriodo($modo, $usuarios, $competencia, null);
        $despesas = $this->despesasNoPeriodo($modo, $competencia, $filtros);

        return $this->contribuicaoPorPessoa($usuarios, $rendas, $despesas, $competencia);
    }

    /** @return list<array{competencia: string, receita: int, despesa: int}> */
    public function tendencia6Meses(string $modo, Competencia $competencia): array
    {
        $usuarios = $this->usuariosDoEscopo($modo);
        $meses = [];

        for ($deslocamento = 5; $deslocamento >= 0; $deslocamento--) {
            $comp = $competencia->somarMeses(-$deslocamento);
            $rendas = $this->rendasNoPeriodo($modo, $usuarios, $comp, null);
            $despesas = $this->despesasNoPeriodo($modo, $comp, []);

            $meses[] = [
                'competencia' => (string) $comp,
                'receita' => $rendas->reduce(fn (Money $carry, Renda $r) => $carry->plus($r->valor), Money::zero())->cents,
                'despesa' => $despesas->reduce(fn (Money $carry, Despesa $d) => $carry->plus($d->valor), Money::zero())->cents,
            ];
        }

        return $meses;
    }

    /** @return Collection<int, Usuario> */
    private function usuariosDoEscopo(string $modo): Collection
    {
        if ($modo !== 'casal') {
            return Usuario::query()->with('corUsuario')->where('id', Auth::id())->get();
        }

        /** @var array<int, array{email: string}> $iniciais */
        $iniciais = config('usuarios.iniciais');

        return Usuario::query()
            ->with('corUsuario')
            ->whereIn('email', array_column($iniciais, 'email'))
            ->get();
    }

    /** @param Collection<int, Usuario> $usuarios
     * @return Collection<int, Renda> */
    private function rendasNoPeriodo(string $modo, Collection $usuarios, Competencia $competencia, ?string $pessoaId): Collection
    {
        $query = $modo === 'casal'
            ? Renda::withoutGlobalScope(DonoScope::class)->whereIn('usuario_id', $usuarios->pluck('id'))
            : Renda::query();

        return $query->with(['categoriaRenda', 'movimentacoes'])
            ->get()
            ->filter(fn (Renda $renda) => $this->calculadoraRenda->existeNaCompetencia($renda, $competencia))
            ->when($pessoaId !== null, fn (Collection $c) => $c->filter(fn (Renda $r) => $r->usuario_id === $pessoaId))
            ->values();
    }

    /**
     * @param  array{categoria_despesa_id?: string, tipo?: string, forma_pagamento_id?: string, status?: string, busca?: string}  $filtros
     * @return Collection<int, Despesa>
     */
    private function despesasNoPeriodo(string $modo, Competencia $competencia, array $filtros = []): Collection
    {
        $despesas = Despesa::with([
            'categoriaDespesa',
            'formaPagamento' => fn ($query) => $query->withTrashed(),
            'movimentacoes.formaPagamento' => fn ($query) => $query->withTrashed(),
            'movimentacoes.formaPagamento.conta' => fn ($query) => $query->withoutGlobalScope(DonoScope::class),
        ])
            ->when(isset($filtros['categoria_despesa_id']), fn ($query) => $query->where('categoria_despesa_id', $filtros['categoria_despesa_id']))
            ->when(isset($filtros['tipo']), fn ($query) => $query->where('tipo_lancamento', $filtros['tipo']))
            ->when(! empty($filtros['busca']), fn ($query) => $query->whereRaw(
                'unaccent(descricao) ilike unaccent(?)',
                ['%'.$filtros['busca'].'%'],
            ))
            ->get()
            ->filter(fn (Despesa $despesa) => $this->calculadoraDespesa->existeNaCompetencia($despesa, $competencia));

        if ($modo === 'casal') {
            $despesas = $despesas->filter(fn (Despesa $despesa) => $despesa->ehConjunta());
        }

        return $despesas
            ->filter(fn (Despesa $despesa) => $this->passaFiltroFormaPagamento($despesa, $competencia, $filtros['forma_pagamento_id'] ?? null))
            ->filter(fn (Despesa $despesa) => $this->passaFiltroStatus($despesa, $competencia, $filtros['status'] ?? null))
            ->values();
    }

    /**
     * Fatura é sempre individual (dono deriva do cartão) — só entra no modo individual, mesmo
     * princípio de despesa individual não contar no modo casal.
     *
     * @return Collection<int, Fatura>
     */
    private function faturasNoPeriodo(string $modo, Competencia $competencia): Collection
    {
        if ($modo === 'casal') {
            return new Collection;
        }

        return Fatura::query()
            ->where('competencia', $competencia->paraData())
            ->with([
                'cartaoCredito' => fn ($query) => $query->with([
                    'formaPagamento' => fn ($query) => $query->withTrashed()->with('conta'),
                ]),
                'movimentacoes',
            ])
            ->get()
            ->filter(fn (Fatura $fatura) => $fatura->cartaoCredito?->formaPagamento?->conta !== null)
            ->values();
    }

    private function movimentacaoDaFatura(Fatura $fatura): ?Movimentacao
    {
        return $fatura->movimentacoes->first(fn (Movimentacao $movimentacao) => $movimentacao->despesa_id === null);
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

    private function movimentacaoDaCompetencia(Despesa|Renda $entidade, Competencia $competencia): ?Movimentacao
    {
        return $entidade->movimentacoes->first(
            fn ($movimentacao) => $movimentacao->competencia?->equals($competencia)
        );
    }

    private function pagadorDaMovimentacao(Movimentacao $movimentacao): ?string
    {
        return $movimentacao->formaPagamento?->conta?->usuario_id;
    }

    /** @return list<array{id: string, nome: string}> */
    public function opcoesFormaPagamento(string $modo, Competencia $competencia): array
    {
        return $this->despesasNoPeriodo($modo, $competencia)
            ->map(function (Despesa $despesa) use ($competencia): ?FormaPagamento {
                if ($despesa->ehParcelada()) {
                    return $despesa->formaPagamento;
                }

                $movimentacao = $this->movimentacaoDaCompetencia($despesa, $competencia);

                return $movimentacao instanceof Movimentacao ? $movimentacao->formaPagamento : null;
            })
            ->filter()
            ->unique('id')
            ->map(fn (FormaPagamento $forma) => ['id' => $forma->id, 'nome' => $forma->nome])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Renda>  $rendas
     * @param  Collection<int, Despesa>  $despesas
     * @param  Collection<int, Fatura>  $faturas
     * @return array{receita: Money, despesa: Money, resultado: Money, saldo: Money, serie: list<array{dia: int, valor: int, tipo: string}>, eventosPorDia: array<int, list<array{descricao: string, valor: int}>>, statusPeriodo: string}
     */
    private function resumirPeriodo(Collection $rendas, Collection $despesas, Collection $faturas, Competencia $competencia, ?string $pessoaId = null): array
    {
        $receita = $rendas->reduce(fn (Money $carry, Renda $renda) => $carry->plus($renda->valor), Money::zero());
        $despesaTotal = $despesas->reduce(fn (Money $carry, Despesa $despesa) => $carry->plus($despesa->valor), Money::zero());

        $ultimoDia = Carbon::create($competencia->ano, $competencia->mes, 1)->daysInMonth;
        $statusPeriodo = $this->statusPeriodo($competencia);
        $corte = $this->diaDeCorte($statusPeriodo, $ultimoDia);

        $certos = [];
        $projetados = [];

        foreach ($rendas as $renda) {
            $movimentacao = $this->movimentacaoDaCompetencia($renda, $competencia);

            if ($movimentacao !== null) {
                $certos[] = ['dia' => min(Carbon::parse($movimentacao->data)->day, $ultimoDia), 'valor' => $renda->valor, 'descricao' => $renda->descricao];

                continue;
            }

            $dia = min($this->calculadoraRenda->diaDoEvento($renda), $ultimoDia);
            $projetados[] = ['dia' => $dia, 'valor' => $renda->valor, 'descricao' => $renda->descricao];
        }

        foreach ($despesas as $despesa) {
            if ($despesa->tipo_lancamento === TipoLancamentoDespesa::Parcelada) {
                continue;
            }

            $movimentacao = $this->movimentacaoDaCompetencia($despesa, $competencia);
            $valorNegativo = $despesa->valor->negated();

            if ($movimentacao !== null) {
                if ($movimentacao->formaPagamento?->ehCredito()) {
                    continue;
                }

                if ($pessoaId === null || $this->pagadorDaMovimentacao($movimentacao) === $pessoaId) {
                    $certos[] = ['dia' => min(Carbon::parse($movimentacao->data)->day, $ultimoDia), 'valor' => $valorNegativo, 'descricao' => $despesa->descricao];
                }

                continue;
            }

            $diaVencimento = $despesa->tipo_lancamento === TipoLancamentoDespesa::Unica
                ? Carbon::parse($despesa->data_vencimento)->day
                : $despesa->dia_vencimento;

            $projetados[] = ['dia' => min($diaVencimento, $ultimoDia), 'valor' => $valorNegativo, 'descricao' => $despesa->descricao];
        }

        foreach ($faturas as $fatura) {
            $descricao = 'Fatura '.$fatura->cartaoCredito->formaPagamento->nome;
            $movimentacao = $this->movimentacaoDaFatura($fatura);
            $valorNegativo = $fatura->valor->negated();

            if ($movimentacao !== null) {
                $certos[] = ['dia' => min(Carbon::parse($movimentacao->data)->day, $ultimoDia), 'valor' => $valorNegativo, 'descricao' => $descricao];

                continue;
            }

            $projetados[] = ['dia' => min(Carbon::parse($fatura->data_vencimento)->day, $ultimoDia), 'valor' => $valorNegativo, 'descricao' => $descricao];
        }

        [$serieRealizada, $saldoNoCorte] = $this->serieAcumulada($certos, min(1, $corte), $corte, Money::zero());
        [$serieProjetada] = $this->serieAcumulada($projetados, $corte, $ultimoDia, $saldoNoCorte);

        $serie = [
            ...array_map(fn (array $ponto) => [...$ponto, 'tipo' => 'realizado'], $serieRealizada),
            ...array_map(fn (array $ponto) => [...$ponto, 'tipo' => 'projetado'], $serieProjetada),
        ];

        $eventosPorDia = collect([...$certos, ...$projetados])
            ->groupBy('dia')
            ->map(fn ($grupo) => $grupo
                ->map(fn (array $evento) => ['descricao' => $evento['descricao'], 'valor' => $evento['valor']->cents])
                ->values()
                ->all())
            ->all();

        return [
            'receita' => $receita,
            'despesa' => $despesaTotal,
            'resultado' => $receita->minus($despesaTotal),
            'saldo' => $saldoNoCorte,
            'serie' => $serie,
            'eventosPorDia' => $eventosPorDia,
            'statusPeriodo' => $statusPeriodo,
        ];
    }

    private function statusPeriodo(Competencia $competencia): string
    {
        $hoje = Competencia::deData(now());

        if ($competencia->equals($hoje)) {
            return 'atual';
        }

        return $competencia->ehAnterior($hoje) ? 'passado' : 'futuro';
    }

    private function diaDeCorte(string $statusPeriodo, int $ultimoDia): int
    {
        return match ($statusPeriodo) {
            'atual' => now()->day,
            'passado' => $ultimoDia,
            default => 0,
        };
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

    /** @return array{tipo: 'percentual'|'absoluto', valor: float|int}|null */
    private function variacao(Money $atual, Money $anterior): ?array
    {
        if ($atual->isZero() && $anterior->isZero()) {
            return null;
        }

        if ($anterior->absolute()->cents < self::PISO_SIGNIFICANCIA_CENTS) {
            return ['tipo' => 'absoluto', 'valor' => $atual->minus($anterior)->cents];
        }

        return ['tipo' => 'percentual', 'valor' => (($atual->cents - $anterior->cents) / abs($anterior->cents)) * 100];
    }

    /** @param Collection<int, Despesa> $despesas
     * @return list<array{id: string|null, nome: string, cor: string, icone: string, valor: int, valorPago: int, valorPendente: int}> */
    private function agruparPorCategoriaDespesa(Collection $despesas, Competencia $competencia): array
    {
        $itens = $despesas
            ->groupBy('categoria_despesa_id')
            ->map(function (Collection $grupo, string $categoriaId) use ($competencia): array {
                $pago = Money::zero();
                $pendente = Money::zero();

                foreach ($grupo as $despesa) {
                    if ($this->movimentacaoDaCompetencia($despesa, $competencia) !== null) {
                        $pago = $pago->plus($despesa->valor);
                    } else {
                        $pendente = $pendente->plus($despesa->valor);
                    }
                }

                return [
                    'id' => $categoriaId,
                    'nome' => $grupo->first()->categoriaDespesa->nome,
                    'cor' => $grupo->first()->categoriaDespesa->cor,
                    'icone' => $grupo->first()->categoriaDespesa->icone,
                    'valor' => $pago->plus($pendente)->cents,
                    'valorPago' => $pago->cents,
                    'valorPendente' => $pendente->cents,
                ];
            })
            ->sortByDesc('valor')
            ->values()
            ->all();

        return $this->comAgrupamentoOutras($itens);
    }

    /** @param Collection<int, Renda> $rendas
     * @return list<array{id: string|null, nome: string, cor: string, icone: string, valor: int}> */
    private function agruparPorCategoriaRenda(Collection $rendas): array
    {
        $itens = $rendas
            ->groupBy('categoria_renda_id')
            ->map(fn (Collection $grupo, string $categoriaId) => [
                'id' => $categoriaId,
                'nome' => $grupo->first()->categoriaRenda->nome,
                'cor' => $grupo->first()->categoriaRenda->cor,
                'icone' => $grupo->first()->categoriaRenda->icone,
                'valor' => $grupo->reduce(fn (Money $carry, Renda $r) => $carry->plus($r->valor), Money::zero())->cents,
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();

        return $this->comAgrupamentoOutras($itens);
    }

    /**
     * @param  list<array<string, mixed>>  $itens
     * @return list<array<string, mixed>>
     */
    private function comAgrupamentoOutras(array $itens): array
    {
        $restante = array_slice($itens, self::MAX_CATEGORIAS_VISIVEIS);

        if (count($restante) < 2) {
            return $itens;
        }

        $visiveis = array_slice($itens, 0, self::MAX_CATEGORIAS_VISIVEIS);

        $outras = [
            'id' => null,
            'nome' => 'Outras',
            'cor' => self::COR_OUTRAS,
            'icone' => 'more-horizontal',
            'valor' => array_sum(array_column($restante, 'valor')),
        ];

        if (array_key_exists('valorPago', $itens[0] ?? [])) {
            $outras['valorPago'] = array_sum(array_column($restante, 'valorPago'));
            $outras['valorPendente'] = array_sum(array_column($restante, 'valorPendente'));
        }

        return [...$visiveis, $outras];
    }

    /** @param Collection<int, Despesa> $despesas
     * @return list<array{nome: string, valor: int}> */
    private function agruparPorFormaPagamento(Collection $despesas, Competencia $competencia): array
    {
        return $despesas
            ->map(function (Despesa $despesa) use ($competencia): array {
                if ($despesa->ehParcelada()) {
                    $forma = $despesa->formaPagamento;
                } else {
                    $movimentacao = $this->movimentacaoDaCompetencia($despesa, $competencia);
                    $forma = $movimentacao instanceof Movimentacao ? $movimentacao->formaPagamento : null;
                }

                return ['despesa' => $despesa, 'forma' => $forma];
            })
            ->groupBy(fn (array $item) => $item['forma'] instanceof FormaPagamento ? $item['forma']->id : 'sem-forma')
            ->map(fn ($grupo) => [
                'nome' => $grupo->first()['forma'] instanceof FormaPagamento ? $grupo->first()['forma']->nome : 'Sem forma definida',
                'valor' => $grupo->reduce(fn (Money $carry, array $item) => $carry->plus($item['despesa']->valor), Money::zero())->cents,
            ])
            ->sortByDesc('valor')
            ->values()
            ->all();
    }

    /** @param Collection<int, Despesa> $despesas
     * @return array{individual: int, conjunta: int} */
    private function individualXConjunta(Collection $despesas): array
    {
        return [
            'individual' => $despesas->filter(fn (Despesa $d) => ! $d->ehConjunta())
                ->reduce(fn (Money $carry, Despesa $d) => $carry->plus($d->valor), Money::zero())->cents,
            'conjunta' => $despesas->filter(fn (Despesa $d) => $d->ehConjunta())
                ->reduce(fn (Money $carry, Despesa $d) => $carry->plus($d->valor), Money::zero())->cents,
        ];
    }

    private function primeiroUso(): bool
    {
        return ! Despesa::query()->exists() && ! Renda::query()->exists();
    }

    /**
     * @param  Collection<int, Despesa>  $despesas
     * @param  Collection<int, Renda>  $rendas
     * @param  Collection<int, Fatura>  $faturas
     * @return list<array{id: string, tipo: string, descricao: string, contexto: string|null, tipoLancamento: string|null, categoriaDespesaId: string|null, data: string, valor: int, dias: int, nivel: string}>
     */
    private function pendencias(Collection $despesas, Collection $rendas, Collection $faturas, Competencia $competencia): array
    {
        $hoje = Carbon::today();

        $itensDespesa = $despesas
            ->filter(fn (Despesa $d) => $d->tipo_lancamento !== TipoLancamentoDespesa::Parcelada)
            ->filter(fn (Despesa $d) => $this->movimentacaoDaCompetencia($d, $competencia) === null)
            ->map(fn (Despesa $d) => [
                'id' => $d->id,
                'tipo' => 'despesa',
                'descricao' => $d->descricao,
                'contexto' => $d->contexto->value,
                'tipoLancamento' => $d->tipo_lancamento->value,
                'categoriaDespesaId' => $d->categoria_despesa_id,
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
                'tipoLancamento' => null,
                'categoriaDespesaId' => null,
                'data' => $this->dataOcorrenciaRenda($r, $competencia),
                'valor' => $r->valor->cents,
            ]);

        $itensFatura = $faturas
            ->filter(fn (Fatura $f) => $this->movimentacaoDaFatura($f) === null)
            ->map(fn (Fatura $f) => [
                'id' => $f->id,
                'tipo' => 'fatura',
                'descricao' => 'Fatura '.$f->cartaoCredito->formaPagamento->nome,
                'contexto' => null,
                'tipoLancamento' => null,
                'categoriaDespesaId' => null,
                'data' => Carbon::parse($f->data_vencimento)->toDateString(),
                'valor' => $f->valor->cents,
            ]);

        return $itensDespesa->concat($itensRenda)->concat($itensFatura)
            ->map(function (array $item) use ($hoje): array {
                $dias = (int) $hoje->diffInDays(Carbon::parse($item['data']), false);

                return [
                    ...$item,
                    'dias' => $dias,
                    'nivel' => match (true) {
                        $dias < 0 => 'vencida',
                        $dias <= 7 => 'vence_em_breve',
                        default => 'no_prazo',
                    },
                ];
            })
            ->sortBy('data')
            ->values()
            ->all();
    }

    private function vencimentoDespesa(Despesa $despesa, Competencia $competencia): string
    {
        return $this->calculadoraDespesa->vencimento($despesa, $competencia)->toDateString();
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
                'cor' => $usuario->corUsuario->cor,
                'valor' => $rendasRecebidasPorUsuario->get($usuario->id, collect())
                    ->reduce(fn (Money $carry, Renda $r) => $carry->plus($r->valor), Money::zero())->cents,
            ])->all(),
            'despesa' => $usuarios->map(fn (Usuario $usuario) => [
                'usuarioId' => $usuario->id,
                'nome' => $usuario->nome,
                'cor' => $usuario->corUsuario->cor,
                'valor' => $despesasPagasPorUsuario->get($usuario->id, collect())
                    ->reduce(fn (Money $carry, array $item) => $carry->plus($item['despesa']->valor), Money::zero())->cents,
            ])->all(),
        ];
    }
}
