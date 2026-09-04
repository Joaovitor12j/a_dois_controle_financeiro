import FiltrosDespesa from '@/Components/FiltrosDespesa';
import FormularioDespesa from '@/Pages/Despesas/Partials/FormularioDespesa';
import Alertas from '@/Pages/Dashboard/Partials/Alertas';
import ContribuicaoPessoa from '@/Pages/Dashboard/Partials/ContribuicaoPessoa';
import EvolucaoSaldo from '@/Pages/Dashboard/Partials/EvolucaoSaldo';
import ListaCategorias from '@/Pages/Dashboard/Partials/ListaCategorias';
import Pendencias from '@/Pages/Dashboard/Partials/Pendencias';
import ResumoPeriodo from '@/Pages/Dashboard/Partials/ResumoPeriodo';
import SeletorVisualizacao, {
    formatarCompetenciaExtenso,
} from '@/Pages/Dashboard/Partials/SeletorVisualizacao';
import FormularioRenda from '@/Pages/Rendas/Partials/FormularioRenda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type {
    DashboardProps,
    FiltrosDespesaValores,
    ModoVisualizacao,
} from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Dashboard({
    modo,
    competencia,
    despesaRotulo,
    resumo,
    serieSaldo,
    despesaPorCategoria,
    receitaPorCategoria,
    pendencias,
    alertas,
    contribuicao,
    categoriasDespesa,
    formasPagamento,
    contas,
    categoriasRenda,
    filtros,
    formasPagamentoFiltro,
}: DashboardProps) {
    const usuario = usePage().props.auth.usuario!;
    const [novaDespesa, setNovaDespesa] = useState({ aberto: false, aberturas: 0 });
    const [novaRenda, setNovaRenda] = useState({ aberto: false, aberturas: 0 });

    const navegar = (parcial: { modo?: ModoVisualizacao; competencia?: string }) => {
        const [ano, mes] = (parcial.competencia ?? competencia).split('-');

        router.get(
            route('dashboard'),
            { modo: parcial.modo ?? modo, ano, mes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const resumoProps = [
        'resumo',
        'serieSaldo',
        'despesaPorCategoria',
        'receitaPorCategoria',
        'pendencias',
        'alertas',
        'contribuicao',
        'filtros',
    ];

    const aplicarFiltros = (parcial: FiltrosDespesaValores) => {
        const [ano, mes] = competencia.split('-');

        router.get(
            route('dashboard'),
            { modo, ano, mes, ...filtros, ...parcial },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: resumoProps,
            },
        );
    };

    const limparFiltros = () => {
        const [ano, mes] = competencia.split('-');

        router.get(
            route('dashboard'),
            { modo, ano, mes },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: resumoProps,
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm font-medium text-tinta-claro">
                        Olá, {usuario.nome.split(' ')[0]}
                    </p>
                    <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                        {formatarCompetenciaExtenso(competencia)}
                    </h1>
                </div>
            }
        >
            <Head title="Visão geral" />

            <SeletorVisualizacao
                modo={modo}
                competencia={competencia}
                aoMudarModo={(novoModo) => navegar({ modo: novoModo })}
                aoMudarCompetencia={(novaCompetencia) =>
                    navegar({ competencia: novaCompetencia })
                }
            />

            <div className="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-8 sm:px-6 lg:px-8">
                <FiltrosDespesa
                    filtros={filtros}
                    categoriasDespesa={categoriasDespesa}
                    formasPagamento={formasPagamentoFiltro}
                    aoMudar={aplicarFiltros}
                    aoLimpar={limparFiltros}
                />

                <ResumoPeriodo
                    resumo={resumo}
                    despesaRotulo={despesaRotulo}
                    aoAbrirNovaDespesa={() =>
                        setNovaDespesa((atual) => ({
                            aberto: true,
                            aberturas: atual.aberturas + 1,
                        }))
                    }
                    aoAbrirNovaRenda={() =>
                        setNovaRenda((atual) => ({
                            aberto: true,
                            aberturas: atual.aberturas + 1,
                        }))
                    }
                />

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                    <div className="lg:col-span-7">
                        <EvolucaoSaldo
                            serie={serieSaldo}
                            saldoAtual={resumo.saldo}
                            competencia={competencia}
                        />
                    </div>
                    <div className="lg:col-span-5">
                        <ListaCategorias
                            titulo={`${despesaRotulo} por categoria`}
                            itens={despesaPorCategoria}
                            total={resumo.despesa}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <ListaCategorias
                            titulo="Receita por categoria"
                            itens={receitaPorCategoria}
                            total={resumo.receita}
                        />
                    </div>

                    {contribuicao && (
                        <div className="lg:col-span-4">
                            <ContribuicaoPessoa contribuicao={contribuicao} />
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
                    <div className="lg:col-span-8">
                        <Pendencias pendencias={pendencias} />
                    </div>
                    <div className="lg:col-span-4">
                        <Alertas alertas={alertas} />
                    </div>
                </div>
            </div>

            <FormularioDespesa
                key={`nova-despesa-${novaDespesa.aberturas}`}
                despesa={null}
                categoriasDespesa={categoriasDespesa}
                formasPagamento={formasPagamento}
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
                aberto={novaRenda.aberto}
                aoFechar={() => setNovaRenda((atual) => ({ ...atual, aberto: false }))}
            />
        </AuthenticatedLayout>
    );
}
