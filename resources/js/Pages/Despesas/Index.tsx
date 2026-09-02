import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CategoriaDespesa, Despesa, FormaPagamento, OcorrenciaDespesa } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarDesfazerPagamento from './Partials/ConfirmarDesfazerPagamento';
import ConfirmarExclusaoDespesa from './Partials/ConfirmarExclusaoDespesa';
import FormularioDespesa from './Partials/FormularioDespesa';
import ItemOcorrenciaDespesa from './Partials/ItemOcorrenciaDespesa';
import MarcarComoPagaDespesa from './Partials/MarcarComoPagaDespesa';

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
    categoriasDespesa,
    formasPagamento,
}: {
    ocorrencias: OcorrenciaDespesa[];
    competencia: string;
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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Despesas
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
                            Nova despesa
                        </PrimaryButton>
                    )}
                </div>
            }
        >
            <Head title="Despesas" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {ocorrencias.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed border-tinta/20 bg-white/50 px-6 py-16 text-center">
                        <span
                            aria-hidden="true"
                            className="h-20 w-20 rounded-full border-2 border-dashed border-tinta/25"
                        />

                        <h2 className="mt-6 font-display text-2xl font-semibold text-tinta">
                            Nenhuma despesa por aqui ainda
                        </h2>

                        <p className="mt-2 max-w-md text-sm leading-relaxed text-tinta-claro">
                            Despesa é toda saída financeira ligada a uma
                            categoria — única, mensal ou parcelada. Cadastre a
                            primeira para começar a acompanhar o que sai.
                        </p>

                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                            className="mt-8 !rounded-xl !bg-verde-escuro hover:!bg-verde"
                        >
                            Cadastrar a primeira despesa
                        </PrimaryButton>
                    </div>
                ) : (
                    <div className="grid items-start gap-6 [grid-template-columns:repeat(auto-fill,minmax(350px,1fr))]">
                        {ocorrencias.map((ocorrencia) => (
                            <ItemOcorrenciaDespesa
                                key={`${ocorrencia.despesa.id}-${ocorrencia.competencia}`}
                                ocorrencia={ocorrencia}
                                aoEditar={() =>
                                    abrirFormulario(ocorrencia.despesa)
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
                                        competencia: ocorrencia.competencia,
                                    })
                                }
                                aoDesfazerPagamento={() =>
                                    setDesfazerPagamento({
                                        aberto: true,
                                        despesa: ocorrencia.despesa,
                                        competencia: ocorrencia.competencia,
                                    })
                                }
                            />
                        ))}
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
