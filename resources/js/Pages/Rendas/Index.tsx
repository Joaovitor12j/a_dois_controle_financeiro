import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CategoriaRenda, ContaResumo, OcorrenciaRenda, Renda } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarDesfazerRecebimento from './Partials/ConfirmarDesfazerRecebimento';
import ConfirmarExclusaoRenda from './Partials/ConfirmarExclusaoRenda';
import FormularioRenda from './Partials/FormularioRenda';
import ItemOcorrenciaRenda from './Partials/ItemOcorrenciaRenda';
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

const nomesMeses = [
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro',
];

function formatarCompetencia(competencia: string): string {
    const [ano, mes] = competencia.split('-');

    return `${nomesMeses[Number(mes) - 1]} de ${ano}`;
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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Rendas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            {formatarCompetencia(competencia)}
                        </p>
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
                {ocorrencias.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed border-tinta/20 bg-white/50 px-6 py-16 text-center">
                        <span
                            aria-hidden="true"
                            className="h-20 w-20 rounded-full border-2 border-dashed border-tinta/25"
                        />

                        <h2 className="mt-6 font-display text-2xl font-semibold text-tinta">
                            Nenhuma renda por aqui ainda
                        </h2>

                        <p className="mt-2 max-w-md text-sm leading-relaxed text-tinta-claro">
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
                    <div className="grid items-start gap-6 [grid-template-columns:repeat(auto-fill,minmax(350px,1fr))]">
                        {ocorrencias.map((ocorrencia) => (
                            <ItemOcorrenciaRenda
                                key={`${ocorrencia.renda.id}-${ocorrencia.competencia}`}
                                ocorrencia={ocorrencia}
                                aoEditar={() =>
                                    abrirFormulario(ocorrencia.renda)
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
                                        competencia: ocorrencia.competencia,
                                        formasPagamentoElegiveis:
                                            ocorrencia.formas_pagamento_elegiveis,
                                    })
                                }
                                aoDesfazerRecebimento={() =>
                                    setDesfazerRecebimento({
                                        aberto: true,
                                        renda: ocorrencia.renda,
                                        competencia: ocorrencia.competencia,
                                        formasPagamentoElegiveis: [],
                                    })
                                }
                            />
                        ))}
                    </div>
                )}
            </div>

            <FormularioRenda
                key={`${formulario.renda?.id ?? 'nova'}-${aberturas}`}
                renda={formulario.renda}
                contas={contas}
                categoriasRenda={categoriasRenda}
                aberto={formulario.aberto}
                aoFechar={fecharFormulario}
            />

            <ConfirmarExclusaoRenda
                renda={exclusao.renda}
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
