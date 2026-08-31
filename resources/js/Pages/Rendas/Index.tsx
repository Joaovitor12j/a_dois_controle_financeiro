import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CategoriaRenda, ContaResumo, Renda, TipoRecorrencia } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmarExclusaoRenda from './Partials/ConfirmarExclusaoRenda';
import FormularioRenda from './Partials/FormularioRenda';

interface AlvoDeFormulario {
    aberto: boolean;
    renda: Renda | null;
}

interface AlvoDeExclusao {
    aberto: boolean;
    renda: Renda | null;
}

const formularioFechado: AlvoDeFormulario = { aberto: false, renda: null };
const exclusaoFechada: AlvoDeExclusao = { aberto: false, renda: null };

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const rotuloTipoRecorrencia: Record<TipoRecorrencia, string> = {
    unica: 'Única',
    mensal: 'Mensal',
};

const corTipoRecorrencia: Record<TipoRecorrencia, string> = {
    unica: 'bg-tinta/10 text-tinta',
    mensal: 'bg-ouro/20 text-ouro',
};

function BotaoEditar({
    rotulo,
    aoClicar,
}: {
    rotulo: string;
    aoClicar: () => void;
}) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label={rotulo}
            title={rotulo}
            className="shrink-0 rounded-lg p-1.5 text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
                className="h-4 w-4"
            >
                <path
                    d="M13.5 3.5a1.5 1.5 0 0 1 2.12 2.12L6.5 14.75l-3 .75.75-3 9.25-8.5Z"
                    stroke="currentColor"
                    strokeWidth="1.4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </button>
    );
}

function BotaoExcluir({
    rotulo,
    aoClicar,
}: {
    rotulo: string;
    aoClicar: () => void;
}) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label={rotulo}
            title={rotulo}
            className="shrink-0 rounded-lg p-1.5 text-vinho transition duration-150 ease-in-out hover:bg-vinho/5 focus:outline-none focus:ring-2 focus:ring-vinho"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
                className="h-4 w-4"
            >
                <path
                    d="M4 6h12M8 6V4.5A1.5 1.5 0 0 1 9.5 3h1A1.5 1.5 0 0 1 12 4.5V6m2 0-.6 9a1.5 1.5 0 0 1-1.5 1.4H8.1A1.5 1.5 0 0 1 6.6 15L6 6"
                    stroke="currentColor"
                    strokeWidth="1.4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </button>
    );
}

export default function Index({
    rendas,
    contas,
    categoriasRenda,
}: {
    rendas: Renda[];
    contas: ContaResumo[];
    categoriasRenda: CategoriaRenda[];
}) {
    const [formulario, setFormulario] =
        useState<AlvoDeFormulario>(formularioFechado);
    const [exclusao, setExclusao] = useState<AlvoDeExclusao>(exclusaoFechada);
    const [aberturas, setAberturas] = useState(0);

    const abrirFormulario = (renda: Renda | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, renda });
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
                            Rendas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            {rendas.length === 0
                                ? 'Nenhuma renda ainda'
                                : rendas.length === 1
                                  ? '1 renda cadastrada'
                                  : `${rendas.length} rendas cadastradas`}
                        </p>
                    </div>

                    {rendas.length > 0 && (
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
                {rendas.length === 0 ? (
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
                            e a uma categoria. Cadastre a primeira para
                            começar a acompanhar o que entra.
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
                    <div className="overflow-x-auto rounded-xl border border-tinta/10 bg-white shadow-sm shadow-tinta/5">
                        <table className="min-w-full divide-y divide-tinta/10">
                            <thead>
                                <tr className="text-left text-xs font-semibold uppercase tracking-wide text-tinta-claro">
                                    <th className="px-5 py-3">Descrição</th>
                                    <th className="px-5 py-3">Categoria</th>
                                    <th className="px-5 py-3">Conta</th>
                                    <th className="px-5 py-3 text-right">
                                        Valor
                                    </th>
                                    <th className="px-5 py-3">Recorrência</th>
                                    <th className="px-5 py-3">
                                        <span className="sr-only">Ações</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-tinta/10">
                                {rendas.map((renda) => (
                                    <tr key={renda.id} className="text-sm">
                                        <td className="px-5 py-3.5 font-medium text-tinta">
                                            {renda.descricao}
                                        </td>

                                        <td className="px-5 py-3.5">
                                            <span className="inline-flex items-center gap-2 text-tinta-claro">
                                                <span
                                                    aria-hidden="true"
                                                    title={
                                                        renda.categoria_renda
                                                            .icone
                                                    }
                                                    className="h-2.5 w-2.5 shrink-0 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            renda
                                                                .categoria_renda
                                                                .cor,
                                                    }}
                                                />
                                                {renda.categoria_renda.nome}
                                            </span>
                                        </td>

                                        <td className="px-5 py-3.5 text-tinta-claro">
                                            {renda.conta.nome}
                                        </td>

                                        <td className="px-5 py-3.5 text-right tabular-nums text-tinta">
                                            {formatadorDeMoeda.format(
                                                renda.valor / 100,
                                            )}
                                        </td>

                                        <td className="px-5 py-3.5">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${corTipoRecorrencia[renda.tipo_recorrencia]}`}
                                            >
                                                {
                                                    rotuloTipoRecorrencia[
                                                        renda.tipo_recorrencia
                                                    ]
                                                }
                                            </span>
                                        </td>

                                        <td className="px-5 py-3.5">
                                            <div className="flex items-center justify-end gap-1">
                                                <BotaoEditar
                                                    rotulo="Editar renda"
                                                    aoClicar={() =>
                                                        abrirFormulario(renda)
                                                    }
                                                />
                                                <BotaoExcluir
                                                    rotulo="Excluir renda"
                                                    aoClicar={() =>
                                                        setExclusao({
                                                            aberto: true,
                                                            renda,
                                                        })
                                                    }
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
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
        </AuthenticatedLayout>
    );
}
