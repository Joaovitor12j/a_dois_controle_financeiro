import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatarCompetenciaExtenso } from '@/Pages/Dashboard/Partials/SeletorVisualizacao';
import type {
    CategoriaDespesa,
    ContextoDespesa,
    Despesa,
    FormaPagamento,
    OcorrenciaDespesa,
} from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarDesfazerPagamento from './Partials/ConfirmarDesfazerPagamento';
import ConfirmarExclusaoDespesa from './Partials/ConfirmarExclusaoDespesa';
import FormularioDespesa from './Partials/FormularioDespesa';
import ItemOcorrenciaDespesa, {
    COLUNAS_GRADE,
} from './Partials/ItemOcorrenciaDespesa';
import MarcarComoPagaDespesa from './Partials/MarcarComoPagaDespesa';
import NavegacaoDespesas from './Partials/NavegacaoDespesas';
import { dataVencimento } from './Partials/vencimento';

interface AlvoDeFormulario {
    aberto: boolean;
    despesa: Despesa | null;
}

interface AlvoDeExclusao {
    aberto: boolean;
    despesa: Despesa | null;
}

interface AlvoDeOcorrencia {
    aberto: boolean;
    despesa: Despesa | null;
    competencia: string;
}

const formularioFechado: AlvoDeFormulario = { aberto: false, despesa: null };
const exclusaoFechada: AlvoDeExclusao = { aberto: false, despesa: null };
const ocorrenciaFechada: AlvoDeOcorrencia = {
    aberto: false,
    despesa: null,
    competencia: '',
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
    contexto,
    categoriasDespesa,
    formasPagamento,
}: {
    ocorrencias: OcorrenciaDespesa[];
    competencia: string;
    contexto: ContextoDespesa;
    categoriasDespesa: CategoriaDespesa[];
    formasPagamento: FormaPagamento[];
}) {
    const [formulario, setFormulario] =
        useState<AlvoDeFormulario>(formularioFechado);
    const [exclusao, setExclusao] = useState<AlvoDeExclusao>(exclusaoFechada);
    const [marcarComoPaga, setMarcarComoPaga] =
        useState<AlvoDeOcorrencia>(ocorrenciaFechada);
    const [desfazerPagamento, setDesfazerPagamento] =
        useState<AlvoDeOcorrencia>(ocorrenciaFechada);
    const [aberturas, setAberturas] = useState(0);

    const abrirFormulario = (despesa: Despesa | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, despesa });
    };

    const fecharFormulario = () =>
        setFormulario((atual) => ({ ...atual, aberto: false }));

    const fecharExclusao = () =>
        setExclusao((atual) => ({ ...atual, aberto: false }));

    const fecharMarcarComoPaga = () =>
        setMarcarComoPaga((atual) => ({ ...atual, aberto: false }));

    const fecharDesfazerPagamento = () =>
        setDesfazerPagamento((atual) => ({ ...atual, aberto: false }));

    const navegar = (parcial: {
        contexto?: ContextoDespesa;
        competencia?: string;
    }) => {
        const [ano, mes] = (parcial.competencia ?? competencia).split('-');

        router.get(
            route('despesas.index'),
            { contexto: parcial.contexto ?? contexto, ano, mes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const aPagar = ocorrencias
        .filter((o) => o.status !== 'paga')
        .map((o) => ({
            ocorrencia: o,
            ordem: dataVencimento(o.despesa, o)?.getTime() ?? Infinity,
        }))
        .sort((a, b) => a.ordem - b.ordem)
        .map((x) => x.ocorrencia);

    const pagas = ocorrencias
        .filter((o) => o.status === 'paga')
        .sort((a, b) =>
            (b.movimentacao?.data ?? '').localeCompare(
                a.movimentacao?.data ?? '',
            ),
        );

    const vencidas = aPagar.filter((o) => o.status === 'vencida').length;
    const totalAPagar = aPagar.reduce((t, o) => t + o.despesa.valor, 0);
    const totalPagas = pagas.reduce((t, o) => t + o.despesa.valor, 0);

    const temDespesas = ocorrencias.length > 0;
    const temPagas = pagas.length > 0;

    const escopoRotulo =
        contexto === 'conjunta'
            ? 'Despesas conjuntas do casal'
            : 'Suas despesas individuais';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Despesas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            {escopoRotulo}
                        </p>

                        {temDespesas && (
                            <div className="mt-3.5 flex flex-wrap items-start gap-8">
                                <div>
                                    <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                                        A pagar neste mês
                                    </p>
                                    <p className="mt-1 font-display text-[28px] font-bold leading-none text-vinho-escuro tabular-nums">
                                        {formatadorDeMoeda.format(
                                            totalAPagar / 100,
                                        )}
                                    </p>
                                    <p className="mt-1 text-xs text-tinta-claro/80">
                                        {vencidas > 0
                                            ? `${plural(vencidas, 'despesa vencida', 'despesas vencidas')} de ${aPagar.length}.`
                                            : `${plural(aPagar.length, 'despesa em aberto', 'despesas em aberto')}, nenhuma vencida.`}
                                    </p>
                                </div>

                                {temPagas && (
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                                            Já pago
                                        </p>
                                        <p className="mt-1 font-display text-[28px] font-bold leading-none text-verde tabular-nums">
                                            {formatadorDeMoeda.format(
                                                totalPagas / 100,
                                            )}
                                        </p>
                                        <p className="mt-1 text-xs text-tinta-claro/80">
                                            {plural(
                                                pagas.length,
                                                'despesa quitada',
                                                'despesas quitadas',
                                            )}{' '}
                                            neste mês.
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    <PrimaryButton
                        type="button"
                        onClick={() => abrirFormulario(null)}
                        className="!rounded-xl !bg-verde-escuro hover:!bg-verde"
                    >
                        Nova despesa
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Despesas" />

            <div className="mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8">
                <NavegacaoDespesas
                    competencia={competencia}
                    contexto={contexto}
                    aoMudarCompetencia={(novaCompetencia) =>
                        navegar({ competencia: novaCompetencia })
                    }
                    aoMudarContexto={(novoContexto) =>
                        navegar({ contexto: novoContexto })
                    }
                />

                {!temDespesas ? (
                    <div className="rounded-xl border border-dashed border-tinta/20 bg-white px-10 py-14 text-center">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-ouro">
                            {formatarCompetenciaExtenso(competencia)}
                        </p>

                        <h2 className="mx-auto mt-2.5 max-w-[24ch] font-display text-xl font-bold text-tinta">
                            {contexto === 'conjunta'
                                ? 'Nenhuma despesa conjunta neste mês'
                                : 'Nenhuma despesa individual neste mês'}
                        </h2>

                        <p className="mx-auto mt-2.5 max-w-[52ch] text-sm leading-relaxed text-tinta-claro">
                            {contexto === 'conjunta'
                                ? 'Nada lançado no contexto conjunto para este mês. Despesas individuais suas podem existir — troque o contexto acima para vê-las.'
                                : 'Nada lançado no seu contexto individual para este mês. As despesas conjuntas do casal ficam no outro contexto.'}
                        </p>

                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                            className="mt-6 !rounded-xl !bg-verde-escuro hover:!bg-verde"
                        >
                            Cadastrar despesa
                        </PrimaryButton>
                    </div>
                ) : (
                    <div>
                        <div className="overflow-hidden rounded-xl border border-tinta/10 bg-white shadow-sm shadow-tinta/5">
                            <div className="flex items-baseline justify-between gap-4 border-b border-tinta/10 px-5 py-3.5">
                                <h2 className="font-display text-[17px] font-semibold text-tinta">
                                    A pagar
                                </h2>
                                <span className="text-[13px] tabular-nums text-tinta-claro">
                                    {plural(aPagar.length, 'despesa', 'despesas')}
                                    {vencidas > 0
                                        ? ` · ${plural(vencidas, 'vencida', 'vencidas')}`
                                        : ''}{' '}
                                    · {formatadorDeMoeda.format(totalAPagar / 100)}
                                </span>
                            </div>

                            <div className="overflow-x-auto">
                                <div className="min-w-[880px]">
                                    <div
                                        className={`grid ${COLUNAS_GRADE} items-center gap-3.5 border-b border-tinta/10 bg-papel px-5 py-2.5 text-[10.5px] font-semibold uppercase tracking-wider text-tinta-claro`}
                                    >
                                        <span>Despesa</span>
                                        <span>Quando</span>
                                        <span>Forma de pagamento</span>
                                        <span className="text-right">
                                            Valor
                                        </span>
                                        <span>Status</span>
                                        <span className="text-right">
                                            Ações
                                        </span>
                                    </div>

                                    {aPagar.map((ocorrencia) => (
                                        <ItemOcorrenciaDespesa
                                            key={`${ocorrencia.despesa.id}-${ocorrencia.competencia}`}
                                            ocorrencia={ocorrencia}
                                            variante="aPagar"
                                            aoEditar={() =>
                                                abrirFormulario(
                                                    ocorrencia.despesa,
                                                )
                                            }
                                            aoExcluir={() =>
                                                setExclusao({
                                                    aberto: true,
                                                    despesa: ocorrencia.despesa,
                                                })
                                            }
                                            aoMarcarComoPaga={() =>
                                                setMarcarComoPaga({
                                                    aberto: true,
                                                    despesa: ocorrencia.despesa,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                })
                                            }
                                            aoDesfazerPagamento={() =>
                                                setDesfazerPagamento({
                                                    aberto: true,
                                                    despesa: ocorrencia.despesa,
                                                    competencia:
                                                        ocorrencia.competencia,
                                                })
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>

                        {temPagas && (
                            <div className="ml-6 mt-6 overflow-hidden rounded-xl border border-tinta/10 bg-white/60">
                                <div className="flex items-baseline justify-between gap-4 border-b border-tinta/10 px-5 py-3">
                                    <h2 className="font-display text-base font-semibold text-tinta-claro">
                                        Pagas
                                    </h2>
                                    <span className="text-[13px] tabular-nums text-tinta-claro/80">
                                        {plural(
                                            pagas.length,
                                            'despesa',
                                            'despesas',
                                        )}{' '}
                                        · {formatadorDeMoeda.format(totalPagas / 100)}
                                    </span>
                                </div>

                                <div className="overflow-x-auto">
                                    <div className="min-w-[880px]">
                                        {pagas.map((ocorrencia) => (
                                            <ItemOcorrenciaDespesa
                                                key={`${ocorrencia.despesa.id}-${ocorrencia.competencia}`}
                                                ocorrencia={ocorrencia}
                                                variante="paga"
                                                aoEditar={() =>
                                                    abrirFormulario(
                                                        ocorrencia.despesa,
                                                    )
                                                }
                                                aoExcluir={() =>
                                                    setExclusao({
                                                        aberto: true,
                                                        despesa:
                                                            ocorrencia.despesa,
                                                    })
                                                }
                                                aoMarcarComoPaga={() =>
                                                    setMarcarComoPaga({
                                                        aberto: true,
                                                        despesa:
                                                            ocorrencia.despesa,
                                                        competencia:
                                                            ocorrencia.competencia,
                                                    })
                                                }
                                                aoDesfazerPagamento={() =>
                                                    setDesfazerPagamento({
                                                        aberto: true,
                                                        despesa:
                                                            ocorrencia.despesa,
                                                        competencia:
                                                            ocorrencia.competencia,
                                                    })
                                                }
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>

            <FormularioDespesa
                key={`${formulario.despesa?.id ?? 'nova'}-${aberturas}`}
                despesa={formulario.despesa}
                categoriasDespesa={categoriasDespesa}
                formasPagamento={formasPagamento}
                aberto={formulario.aberto}
                aoFechar={fecharFormulario}
            />

            <ConfirmarExclusaoDespesa
                despesa={exclusao.despesa}
                aberto={exclusao.aberto}
                aoFechar={fecharExclusao}
            />

            <MarcarComoPagaDespesa
                key={`${marcarComoPaga.despesa?.id ?? 'nenhuma'}-${marcarComoPaga.competencia}`}
                despesa={marcarComoPaga.despesa}
                competencia={marcarComoPaga.competencia}
                formasPagamento={formasPagamento}
                aberto={marcarComoPaga.aberto}
                aoFechar={fecharMarcarComoPaga}
            />

            <ConfirmarDesfazerPagamento
                despesa={desfazerPagamento.despesa}
                competencia={desfazerPagamento.competencia}
                aberto={desfazerPagamento.aberto}
                aoFechar={fecharDesfazerPagamento}
            />
        </AuthenticatedLayout>
    );
}
