import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    competenciaAdjacente,
    formatarCompetenciaExtenso,
} from '@/Pages/Dashboard/Partials/SeletorVisualizacao';
import type { CategoriaRenda, ContaResumo, OcorrenciaRenda, Renda } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarDesfazerRecebimento from './Partials/ConfirmarDesfazerRecebimento';
import ConfirmarExclusaoRenda from './Partials/ConfirmarExclusaoRenda';
import FormularioRenda from './Partials/FormularioRenda';
import ItemOcorrenciaRenda, {
    diaPrevisto,
    estaAtrasada,
} from './Partials/ItemOcorrenciaRenda';
import MarcarComoRecebidaRenda from './Partials/MarcarComoRecebidaRenda';

interface AlvoDeFormulario {
    aberto: boolean;
    renda: Renda | null;
}

interface AlvoDeExclusao {
    aberto: boolean;
    renda: Renda | null;
}

interface AlvoDeOcorrencia {
    aberto: boolean;
    renda: Renda | null;
    competencia: string;
    formasPagamentoElegiveis: OcorrenciaRenda['formas_pagamento_elegiveis'];
}

const formularioFechado: AlvoDeFormulario = { aberto: false, renda: null };
const exclusaoFechada: AlvoDeExclusao = { aberto: false, renda: null };
const ocorrenciaFechada: AlvoDeOcorrencia = {
    aberto: false,
    renda: null,
    competencia: '',
    formasPagamentoElegiveis: [],
};

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function plural(n: number, um: string, muitos: string): string {
    return `${n} ${n === 1 ? um : muitos}`;
}

export default function Index({
    ocorrencias,
    competencia,
    contas,
    categoriasRenda,
}: {
    ocorrencias: OcorrenciaRenda[];
    competencia: string;
    contas: ContaResumo[];
    categoriasRenda: CategoriaRenda[];
}) {
    const [formulario, setFormulario] =
        useState<AlvoDeFormulario>(formularioFechado);
    const [exclusao, setExclusao] = useState<AlvoDeExclusao>(exclusaoFechada);
    const [marcarComoRecebida, setMarcarComoRecebida] =
        useState<AlvoDeOcorrencia>(ocorrenciaFechada);
    const [desfazerRecebimento, setDesfazerRecebimento] =
        useState<AlvoDeOcorrencia>(ocorrenciaFechada);
    const [aberturas, setAberturas] = useState(0);

    const abrirFormulario = (renda: Renda | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, renda });
    };

    const fecharFormulario = () =>
        setFormulario((atual) => ({ ...atual, aberto: false }));

    const fecharExclusao = () =>
        setExclusao((atual) => ({ ...atual, aberto: false }));

    const fecharMarcarComoRecebida = () =>
        setMarcarComoRecebida((atual) => ({ ...atual, aberto: false }));

    const fecharDesfazerRecebimento = () =>
        setDesfazerRecebimento((atual) => ({ ...atual, aberto: false }));

    const navegar = (novaCompetencia: string) => {
        const [ano, mes] = novaCompetencia.split('-');

        router.get(
            route('rendas.index'),
            { ano, mes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const [anoCompetencia, mesCompetencia] = competencia
        .split('-')
        .map(Number);
    const hoje = new Date();
    const indiceCompetencia = anoCompetencia * 12 + mesCompetencia;
    const indiceHoje = hoje.getFullYear() * 12 + (hoje.getMonth() + 1);
    const competenciaPassada = indiceCompetencia < indiceHoje;
    const competenciaFutura = indiceCompetencia > indiceHoje;

    const aReceber = ocorrencias
        .filter((o) => !o.recebida)
        .sort((a, b) => {
            const atrasadaA = estaAtrasada(a.renda, a.recebida, a.competencia);
            const atrasadaB = estaAtrasada(b.renda, b.recebida, b.competencia);

            if (atrasadaA !== atrasadaB) {
                return atrasadaA ? -1 : 1;
            }

            return diaPrevisto(a.renda) - diaPrevisto(b.renda);
        });

    const recebidas = ocorrencias
        .filter((o) => o.recebida)
        .sort((a, b) => diaPrevisto(a.renda) - diaPrevisto(b.renda));

    const totalPrevisto = ocorrencias.reduce((t, o) => t + o.renda.valor, 0);
    const totalAReceber = aReceber.reduce((t, o) => t + o.renda.valor, 0);
    const totalRecebido = recebidas.reduce(
        (t, o) => t + (o.movimentacao?.valor ?? 0),
        0,
    );
    const totalProgramadoRecebido = recebidas.reduce(
        (t, o) => t + o.renda.valor,
        0,
    );
    const atrasadas = aReceber.filter((o) =>
        estaAtrasada(o.renda, o.recebida, o.competencia),
    ).length;

    const fatiaRecebido =
        totalPrevisto > 0
            ? Math.round((totalProgramadoRecebido / totalPrevisto) * 1000) /
              10
            : 0;
    const fatiaAReceber =
        totalPrevisto > 0
            ? Math.round((totalAReceber / totalPrevisto) * 1000) / 10
            : 0;

    const difRecebido = totalRecebido - totalProgramadoRecebido;
    const notaRecebido =
        difRecebido === 0
            ? 'igual ao previsto'
            : `${formatadorDeMoeda.format(Math.abs(difRecebido) / 100)} ${difRecebido > 0 ? 'acima' : 'abaixo'} do previsto`;
    const notaAReceber =
        atrasadas > 0
            ? plural(atrasadas, 'atrasada', 'atrasadas')
            : 'nenhuma atrasada';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Rendas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            Recebimento é por competência
                        </p>

                        {ocorrencias.length > 0 && (
                            <div className="mt-4 max-w-xl">
                                <div className="flex flex-wrap items-end justify-between gap-4">
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-wide text-tinta-claro">
                                            Previsto nesta competência
                                        </p>
                                        <p className="mt-1 font-display text-3xl font-bold leading-none tabular-nums text-tinta">
                                            {formatadorDeMoeda.format(
                                                totalPrevisto / 100,
                                            )}
                                        </p>
                                    </div>

                                    <p className="max-w-[24ch] text-right text-xs leading-relaxed text-tinta-claro/70">
                                        soma programada de{' '}
                                        {plural(
                                            ocorrencias.length,
                                            'renda',
                                            'rendas',
                                        )}{' '}
                                        nesta competência
                                    </p>
                                </div>

                                <div className="mt-3.5 flex h-2 overflow-hidden rounded-full bg-tinta/[0.08]">
                                    <span
                                        aria-hidden="true"
                                        className="block h-full bg-verde"
                                        style={{
                                            width: `${fatiaRecebido}%`,
                                        }}
                                    />
                                    <span
                                        aria-hidden="true"
                                        className="block h-full bg-ouro"
                                        style={{
                                            width: `${fatiaAReceber}%`,
                                        }}
                                    />
                                </div>

                                <div className="mt-3 flex flex-wrap items-start gap-7">
                                    <div className="flex items-start gap-2">
                                        <span
                                            aria-hidden="true"
                                            className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-verde"
                                        />
                                        <div>
                                            <p className="text-xs text-tinta-claro">
                                                Já recebido
                                            </p>
                                            <p className="text-[17px] font-semibold tabular-nums text-verde-escuro">
                                                {formatadorDeMoeda.format(
                                                    totalRecebido / 100,
                                                )}
                                            </p>
                                            <p className="mt-0.5 text-[11.5px] leading-tight text-tinta-claro/70">
                                                {plural(
                                                    recebidas.length,
                                                    'renda',
                                                    'rendas',
                                                )}{' '}
                                                · {notaRecebido}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-2">
                                        <span
                                            aria-hidden="true"
                                            className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-ouro"
                                        />
                                        <div>
                                            <p className="text-xs text-tinta-claro">
                                                Ainda a receber
                                            </p>
                                            <p className="text-[17px] font-semibold tabular-nums text-ouro">
                                                {formatadorDeMoeda.format(
                                                    totalAReceber / 100,
                                                )}
                                            </p>
                                            <p className="mt-0.5 text-[11.5px] leading-tight text-tinta-claro/70">
                                                {plural(
                                                    aReceber.length,
                                                    'renda',
                                                    'rendas',
                                                )}{' '}
                                                · {notaAReceber}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {ocorrencias.length > 0 && (
                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                            className="!rounded-xl !bg-verde-escuro hover:!bg-verde"
                        >
                            Nova renda
                        </PrimaryButton>
                    )}
                </div>
            }
        >
            <Head title="Rendas" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-wrap items-center justify-between gap-4 pb-6">
                    <div className="flex items-center gap-1.5">
                        <button
                            type="button"
                            aria-label="Mês anterior"
                            onClick={() =>
                                navegar(competenciaAdjacente(competencia, -1))
                            }
                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-tinta/10 bg-white text-tinta-claro transition-colors hover:border-tinta/25 hover:text-tinta"
                        >
                            ‹
                        </button>

                        <span className="min-w-[196px] text-center font-display text-lg font-semibold text-tinta">
                            {formatarCompetenciaExtenso(competencia)}
                        </span>

                        <button
                            type="button"
                            aria-label="Próximo mês"
                            onClick={() =>
                                navegar(competenciaAdjacente(competencia, 1))
                            }
                            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-tinta/10 bg-white text-tinta-claro transition-colors hover:border-tinta/25 hover:text-tinta"
                        >
                            ›
                        </button>
                    </div>

                    {(competenciaPassada || competenciaFutura) && (
                        <p className="text-xs text-tinta-claro/80">
                            {competenciaFutura
                                ? 'Competência futura: nada recebido ainda.'
                                : 'Competência fechada — pendências aqui contam como atrasadas.'}
                        </p>
                    )}
                </div>

                {ocorrencias.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed border-tinta/20 bg-white px-6 py-16 text-center">
                        <p className="text-[11px] font-semibold uppercase tracking-wide text-ouro">
                            {formatarCompetenciaExtenso(competencia)}
                        </p>

                        <h2 className="mt-2.5 max-w-[24ch] font-display text-2xl font-bold leading-tight text-tinta">
                            Nenhuma renda nesta competência
                        </h2>

                        <p className="mt-2.5 max-w-md text-sm leading-relaxed text-tinta-claro">
                            Renda é toda entrada financeira ligada a uma conta
                            e a uma categoria — única ou mensal. Cadastre a
                            primeira para começar a acompanhar o que entra.
                        </p>

                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                            className="mt-8 !rounded-xl !bg-verde-escuro hover:!bg-verde"
                        >
                            Cadastrar a primeira renda
                        </PrimaryButton>
                    </div>
                ) : (
                    <>
                        {aReceber.length > 0 && (
                            <div>
                                <div className="flex flex-wrap items-baseline justify-between gap-3 pb-3.5">
                                    <h2 className="font-display text-[17px] font-semibold text-tinta">
                                        A receber
                                    </h2>
                                    <span className="text-[13px] tabular-nums text-tinta-claro">
                                        {plural(
                                            aReceber.length,
                                            'renda',
                                            'rendas',
                                        )}
                                        {atrasadas > 0
                                            ? ` · ${plural(atrasadas, 'atrasada', 'atrasadas')}`
                                            : ''}{' '}
                                        ·{' '}
                                        {formatadorDeMoeda.format(
                                            totalAReceber / 100,
                                        )}
                                    </span>
                                </div>

                                <div className="grid items-start gap-5 [grid-template-columns:repeat(auto-fill,minmax(348px,1fr))]">
                                    {aReceber.map((ocorrencia) => (
                                        <ItemOcorrenciaRenda
                                            key={`${ocorrencia.renda.id}-${ocorrencia.competencia}`}
                                            ocorrencia={ocorrencia}
                                            aoEditar={() =>
                                                abrirFormulario(
                                                    ocorrencia.renda,
                                                )
                                            }
                                            aoExcluir={() =>
                                                setExclusao({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                })
                                            }
                                            aoMarcarComoRecebida={() =>
                                                setMarcarComoRecebida({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                    formasPagamentoElegiveis:
                                                        ocorrencia.formas_pagamento_elegiveis,
                                                })
                                            }
                                            aoDesfazerRecebimento={() =>
                                                setDesfazerRecebimento({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                    formasPagamentoElegiveis:
                                                        [],
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {recebidas.length > 0 && (
                            <div className="mt-10 sm:ml-6">
                                <div className="flex flex-wrap items-baseline justify-between gap-3 pb-3.5">
                                    <h2 className="font-display text-base font-semibold text-tinta-claro">
                                        Recebidas
                                    </h2>
                                    <span className="text-[13px] tabular-nums text-tinta-claro/70">
                                        {plural(
                                            recebidas.length,
                                            'renda',
                                            'rendas',
                                        )}{' '}
                                        ·{' '}
                                        {formatadorDeMoeda.format(
                                            totalRecebido / 100,
                                        )}
                                    </span>
                                </div>

                                <div className="grid items-start gap-4 [grid-template-columns:repeat(auto-fill,minmax(330px,1fr))]">
                                    {recebidas.map((ocorrencia) => (
                                        <ItemOcorrenciaRenda
                                            key={`${ocorrencia.renda.id}-${ocorrencia.competencia}`}
                                            ocorrencia={ocorrencia}
                                            aoEditar={() =>
                                                abrirFormulario(
                                                    ocorrencia.renda,
                                                )
                                            }
                                            aoExcluir={() =>
                                                setExclusao({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                })
                                            }
                                            aoMarcarComoRecebida={() =>
                                                setMarcarComoRecebida({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                    formasPagamentoElegiveis:
                                                        ocorrencia.formas_pagamento_elegiveis,
                                                })
                                            }
                                            aoDesfazerRecebimento={() =>
                                                setDesfazerRecebimento({
                                                    aberto: true,
                                                    renda: ocorrencia.renda,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                    formasPagamentoElegiveis:
                                                        [],
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <FormularioRenda
                key={`${formulario.renda?.id ?? 'nova'}-${aberturas}`}
                renda={formulario.renda}
                contas={contas}
                categoriasRenda={categoriasRenda}
                competencia={competencia}
                aberto={formulario.aberto}
                aoFechar={fecharFormulario}
            />

            <ConfirmarExclusaoRenda
                renda={exclusao.renda}
                competencia={competencia}
                aberto={exclusao.aberto}
                aoFechar={fecharExclusao}
            />

            <MarcarComoRecebidaRenda
                key={`${marcarComoRecebida.renda?.id ?? 'nenhuma'}-${marcarComoRecebida.competencia}`}
                renda={marcarComoRecebida.renda}
                competencia={marcarComoRecebida.competencia}
                formasPagamentoElegiveis={
                    marcarComoRecebida.formasPagamentoElegiveis
                }
                aberto={marcarComoRecebida.aberto}
                aoFechar={fecharMarcarComoRecebida}
            />

            <ConfirmarDesfazerRecebimento
                renda={desfazerRecebimento.renda}
                competencia={desfazerRecebimento.competencia}
                aberto={desfazerRecebimento.aberto}
                aoFechar={fecharDesfazerRecebimento}
            />
        </AuthenticatedLayout>
    );
}
