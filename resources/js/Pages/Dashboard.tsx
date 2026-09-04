import FiltrosDespesa from '@/Components/FiltrosDespesa';
import FormularioDespesa from '@/Pages/Despesas/Partials/FormularioDespesa';
import ContribuicaoPessoa from '@/Pages/Dashboard/Partials/ContribuicaoPessoa';
import DespesaPorFormaPagamento from '@/Pages/Dashboard/Partials/DespesaPorFormaPagamento';
import EvolucaoSaldo from '@/Pages/Dashboard/Partials/EvolucaoSaldo';
import IndividualXConjunta from '@/Pages/Dashboard/Partials/IndividualXConjunta';
import ListaCategorias from '@/Pages/Dashboard/Partials/ListaCategorias';
import Pendencias from '@/Pages/Dashboard/Partials/Pendencias';
import ResumoPeriodo from '@/Pages/Dashboard/Partials/ResumoPeriodo';
import SeletorVisualizacao, {
    formatarCompetenciaExtenso,
} from '@/Pages/Dashboard/Partials/SeletorVisualizacao';
import TendenciaMeses from '@/Pages/Dashboard/Partials/TendenciaMeses';
import FormularioRenda from '@/Pages/Rendas/Partials/FormularioRenda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type {
    CategoriaResumoItem,
    DashboardProps,
    FiltrosDespesaValores,
    ModoVisualizacao,
    PendenciaItem,
} from '@/types';
import { Deferred, Head, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

const PROPS_RESUMO = [
    'resumo',
    'serieSaldo',
    'despesaPorCategoria',
    'receitaPorCategoria',
    'despesaPorFormaPagamento',
    'individualXConjunta',
    'pendencias',
    'contribuicao',
    'tendencia6Meses',
    'primeiroUso',
    'filtros',
    'pessoaId',
];

export default function Dashboard({
    modo,
    competencia,
    despesaRotulo,
    resumo,
    serieSaldo,
    despesaPorCategoria,
    receitaPorCategoria,
    despesaPorFormaPagamento,
    individualXConjunta,
    pendencias,
    contribuicao,
    tendencia6Meses,
    primeiroUso,
    usuariosCasal,
    categoriasDespesa,
    formasPagamento,
    contas,
    categoriasRenda,
    filtros,
    pessoaId,
    formasPagamentoFiltro,
}: DashboardProps) {
    const usuario = usePage().props.auth.usuario!;
    const [novaDespesa, setNovaDespesa] = useState({ aberto: false, aberturas: 0 });
    const [novaRenda, setNovaRenda] = useState({ aberto: false, aberturas: 0 });
    const [buscaLocal, setBuscaLocal] = useState(filtros.busca ?? '');
    const buscaTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    const navegar = (parcial: { modo?: ModoVisualizacao; competencia?: string }) => {
        const [ano, mes] = (parcial.competencia ?? competencia).split('-');

        router.get(
            route('dashboard'),
            { modo: parcial.modo ?? modo, ano, mes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const aplicarFiltros = (parcial: FiltrosDespesaValores) => {
        const [ano, mes] = competencia.split('-');

        router.get(
            route('dashboard'),
            {
                modo,
                ano,
                mes,
                ...(pessoaId ? { pessoa_id: pessoaId } : {}),
                ...filtros,
                ...parcial,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: PROPS_RESUMO,
            },
        );
    };

    const limparFiltros = () => {
        const [ano, mes] = competencia.split('-');
        setBuscaLocal('');

        router.get(
            route('dashboard'),
            { modo, ano, mes, ...(pessoaId ? { pessoa_id: pessoaId } : {}) },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: PROPS_RESUMO,
            },
        );
    };

    const aoMudarBusca = (valor: string) => {
        setBuscaLocal(valor);

        if (buscaTimeout.current) {
            clearTimeout(buscaTimeout.current);
        }

        buscaTimeout.current = setTimeout(() => {
            aplicarFiltros({ busca: valor || undefined });
        }, 400);
    };

    const aoMudarPessoa = (novaPessoa: string | null) => {
        const [ano, mes] = competencia.split('-');

        router.get(
            route('dashboard'),
            {
                modo,
                ano,
                mes,
                ...filtros,
                ...(novaPessoa ? { pessoa_id: novaPessoa } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: PROPS_RESUMO,
            },
        );
    };

    const irParaDespesas = (parcial: Record<string, string | undefined>) => {
        const [ano, mes] = competencia.split('-');

        router.get(route('despesas.index'), {
            ano,
            mes,
            contexto: modo === 'casal' ? 'conjunta' : 'individual',
            ...filtros,
            ...parcial,
        });
    };

    const irParaRendas = () => {
        const [ano, mes] = competencia.split('-');

        router.get(route('rendas.index'), { ano, mes });
    };

    const aoClicarCategoriaDespesa = (item: CategoriaResumoItem) => {
        if (item.id === null) {
            return;
        }

        irParaDespesas({ categoria_despesa_id: item.id });
    };

    const aoClicarPendencia = (item: PendenciaItem) => {
        if (item.tipo === 'renda') {
            irParaRendas();

            return;
        }

        irParaDespesas({
            status: 'pendente',
            ...(item.categoriaDespesaId ? { categoria_despesa_id: item.categoriaDespesaId } : {}),
        });
    };

    const temFiltroAtivo = Object.values(filtros).some((valor) => !!valor);
    const semResultadoPorFiltro =
        !primeiroUso &&
        temFiltroAtivo &&
        pendencias.length === 0 &&
        despesaPorCategoria.length === 0 &&
        receitaPorCategoria.length === 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium text-tinta-claro">
                            Olá, {usuario.nome.split(' ')[0]}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={() =>
                                setNovaDespesa((atual) => ({
                                    aberto: true,
                                    aberturas: atual.aberturas + 1,
                                }))
                            }
                            className="flex h-10 items-center justify-center gap-2 rounded-xl bg-tinta px-4 text-sm font-semibold text-papel transition-colors hover:bg-tinta-claro"
                        >
                            Nova despesa
                        </button>
                        <button
                            type="button"
                            onClick={() =>
                                setNovaRenda((atual) => ({
                                    aberto: true,
                                    aberturas: atual.aberturas + 1,
                                }))
                            }
                            className="flex h-10 items-center justify-center gap-2 rounded-xl bg-verde-escuro px-4 text-sm font-semibold text-papel transition-colors hover:bg-verde"
                        >
                            Nova renda
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Visão geral" />

            <SeletorVisualizacao
                modo={modo}
                competencia={competencia}
                usuariosCasal={usuariosCasal}
                pessoaId={pessoaId}
                aoMudarModo={(novoModo) => navegar({ modo: novoModo })}
                aoMudarCompetencia={(novaCompetencia) =>
                    navegar({ competencia: novaCompetencia })
                }
                aoMudarPessoa={aoMudarPessoa}
            />

            {primeiroUso ? (
                <div className="mx-auto flex max-w-7xl flex-col items-center gap-3 px-4 py-24 text-center sm:px-6 lg:px-8">
                    <h2 className="font-display text-2xl font-semibold text-tinta">
                        Comece lançando sua primeira renda ou despesa
                    </h2>
                    <p className="max-w-md text-sm text-tinta-claro">
                        A visão geral aparece assim que houver alguma renda ou despesa
                        cadastrada.
                    </p>
                </div>
            ) : (
                <div className="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-8 sm:px-6 lg:px-8">
                    <FiltrosDespesa
                        filtros={filtros}
                        categoriasDespesa={categoriasDespesa}
                        formasPagamento={formasPagamentoFiltro}
                        aoMudar={aplicarFiltros}
                        aoLimpar={limparFiltros}
                        busca={buscaLocal}
                        aoMudarBusca={aoMudarBusca}
                    />

                    {semResultadoPorFiltro && (
                        <div className="rounded-xl border border-dashed border-tinta/20 bg-white px-6 py-8 text-center">
                            <p className="text-sm font-medium text-tinta">
                                Nenhum resultado para os filtros aplicados.
                            </p>
                            <button
                                type="button"
                                onClick={limparFiltros}
                                className="mt-3 text-sm font-semibold text-tinta underline"
                            >
                                Limpar filtros
                            </button>
                        </div>
                    )}

                    <ResumoPeriodo resumo={resumo} despesaRotulo={despesaRotulo} />

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                        <div className="lg:col-span-7">
                            <Deferred
                                data="serieSaldo"
                                fallback={
                                    <div className="h-[280px] animate-pulse rounded-xl border border-tinta/10 bg-white" />
                                }
                            >
                                {serieSaldo && (
                                    <EvolucaoSaldo
                                        serie={serieSaldo.serie}
                                        eventosPorDia={serieSaldo.eventosPorDia}
                                        saldoAtual={resumo.saldo}
                                        competencia={competencia}
                                        statusPeriodo={resumo.statusPeriodo}
                                        temDespesaParcelada={resumo.temDespesaParcelada}
                                    />
                                )}
                            </Deferred>
                        </div>
                        <div className="lg:col-span-5">
                            <ListaCategorias
                                titulo={`${despesaRotulo} por categoria`}
                                itens={despesaPorCategoria}
                                total={resumo.despesa}
                                aoClicarItem={aoClicarCategoriaDespesa}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                        <div className="lg:col-span-4">
                            <ListaCategorias
                                titulo="Renda por categoria"
                                itens={receitaPorCategoria}
                                total={resumo.receita}
                            />
                        </div>

                        <div className="lg:col-span-4">
                            <DespesaPorFormaPagamento
                                itens={despesaPorFormaPagamento}
                                total={resumo.despesa}
                            />
                        </div>

                        <div className="lg:col-span-4">
                            {modo === 'casal' ? (
                                <Deferred
                                    data="contribuicao"
                                    fallback={
                                        <div className="h-full min-h-[220px] animate-pulse rounded-xl border border-tinta/10 bg-white" />
                                    }
                                >
                                    {contribuicao && (
                                        <ContribuicaoPessoa contribuicao={contribuicao} />
                                    )}
                                </Deferred>
                            ) : (
                                individualXConjunta && (
                                    <IndividualXConjunta dados={individualXConjunta} />
                                )
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                        <div className="lg:col-span-8">
                            <Pendencias
                                pendencias={pendencias}
                                modo={modo}
                                competencia={competencia}
                                formasPagamento={formasPagamento}
                                aoClicarItem={aoClicarPendencia}
                            />
                        </div>
                        <div className="lg:col-span-4">
                            <Deferred
                                data="tendencia6Meses"
                                fallback={
                                    <div className="h-full min-h-[220px] animate-pulse rounded-xl border border-tinta/10 bg-white" />
                                }
                            >
                                {tendencia6Meses && (
                                    <TendenciaMeses
                                        meses={tendencia6Meses}
                                        competenciaSelecionada={competencia}
                                        aoSelecionarMes={(novaCompetencia) =>
                                            navegar({ competencia: novaCompetencia })
                                        }
                                    />
                                )}
                            </Deferred>
                        </div>
                    </div>
                </div>
            )}

            <FormularioDespesa
                key={`nova-despesa-${novaDespesa.aberturas}`}
                despesa={null}
                categoriasDespesa={categoriasDespesa}
                formasPagamento={formasPagamento}
                competencia={competencia}
                contexto={modo === 'casal' ? 'conjunta' : 'individual'}
                aberto={novaDespesa.aberto}
                aoFechar={() =>
                    setNovaDespesa((atual) => ({ ...atual, aberto: false }))
                }
            />

            <FormularioRenda
                key={`nova-renda-${novaRenda.aberturas}`}
                renda={null}
                contas={contas}
                categoriasRenda={categoriasRenda}
                competencia={competencia}
                aberto={novaRenda.aberto}
                aoFechar={() => setNovaRenda((atual) => ({ ...atual, aberto: false }))}
            />
        </AuthenticatedLayout>
    );
}
