import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CategoriaDespesa, Despesa, FormaPagamento } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarExclusaoDespesa from './Partials/ConfirmarExclusaoDespesa';
import FormularioDespesa from './Partials/FormularioDespesa';
import ItemDespesa from './Partials/ItemDespesa';

interface AlvoDeFormulario {
    aberto: boolean;
    despesa: Despesa | null;
}

interface AlvoDeExclusao {
    aberto: boolean;
    despesa: Despesa | null;
}

const formularioFechado: AlvoDeFormulario = { aberto: false, despesa: null };
const exclusaoFechada: AlvoDeExclusao = { aberto: false, despesa: null };

export default function Index({
    despesas,
    categoriasDespesa,
    formasPagamento,
}: {
    despesas: Despesa[];
    categoriasDespesa: CategoriaDespesa[];
    formasPagamento: FormaPagamento[];
}) {
    const [formulario, setFormulario] =
        useState<AlvoDeFormulario>(formularioFechado);
    const [exclusao, setExclusao] = useState<AlvoDeExclusao>(exclusaoFechada);
    const [aberturas, setAberturas] = useState(0);

    const abrirFormulario = (despesa: Despesa | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, despesa });
    };

    const fecharFormulario = () =>
        setFormulario((atual) => ({ ...atual, aberto: false }));

    const fecharExclusao = () =>
        setExclusao((atual) => ({ ...atual, aberto: false }));

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Despesas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            {despesas.length === 0
                                ? 'Nenhuma despesa ainda'
                                : despesas.length === 1
                                  ? '1 despesa cadastrada'
                                  : `${despesas.length} despesas cadastradas`}
                        </p>
                    </div>

                    {despesas.length > 0 && (
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
                {despesas.length === 0 ? (
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
                        {despesas.map((despesa) => (
                            <ItemDespesa
                                key={despesa.id}
                                despesa={despesa}
                                aoEditar={() => abrirFormulario(despesa)}
                                aoExcluir={() =>
                                    setExclusao({ aberto: true, despesa })
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
        </AuthenticatedLayout>
    );
}
