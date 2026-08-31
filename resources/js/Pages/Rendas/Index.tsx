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

const iconesPorNome: Record<string, JSX.Element> = {
    wallet: (
        <>
            <rect
                x="3"
                y="6"
                width="14"
                height="10"
                rx="2"
                stroke="currentColor"
                strokeWidth="1.4"
            />
            <path
                d="M3 8.5h14"
                stroke="currentColor"
                strokeWidth="1.4"
            />
            <circle cx="14" cy="12" r="1" fill="currentColor" />
        </>
    ),
    briefcase: (
        <>
            <rect
                x="3"
                y="7"
                width="14"
                height="9"
                rx="1.5"
                stroke="currentColor"
                strokeWidth="1.4"
            />
            <path
                d="M7.5 7V5.5A1.5 1.5 0 0 1 9 4h2a1.5 1.5 0 0 1 1.5 1.5V7"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path d="M3 11h14" stroke="currentColor" strokeWidth="1.4" />
        </>
    ),
    'trending-up': (
        <>
            <path
                d="M3 13l5-5 3 3 6-6"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M13 5h4v4"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </>
    ),
    gift: (
        <>
            <rect
                x="3"
                y="9"
                width="14"
                height="8"
                rx="1"
                stroke="currentColor"
                strokeWidth="1.4"
            />
            <path d="M3 9h14" stroke="currentColor" strokeWidth="1.4" />
            <path d="M10 9v8" stroke="currentColor" strokeWidth="1.4" />
            <path
                d="M10 9c-1.5 0-3-1-3-2.5S8.2 4 9.3 4C10.5 4 10 6.5 10 9Z"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinejoin="round"
            />
            <path
                d="M10 9c1.5 0 3-1 3-2.5S11.8 4 10.7 4C9.5 4 10 6.5 10 9Z"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinejoin="round"
            />
        </>
    ),
    'rotate-ccw': (
        <>
            <path
                d="M4 8a6 6 0 1 1 1.5 6"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinecap="round"
                fill="none"
            />
            <path
                d="M4 4v4h4"
                stroke="currentColor"
                strokeWidth="1.4"
                strokeLinecap="round"
                strokeLinejoin="round"
                fill="none"
            />
        </>
    ),
    'more-horizontal': (
        <>
            <circle cx="4" cy="10" r="1.3" fill="currentColor" />
            <circle cx="10" cy="10" r="1.3" fill="currentColor" />
            <circle cx="16" cy="10" r="1.3" fill="currentColor" />
        </>
    ),
};

const iconeIndefinido = (
    <>
        <path
            d="M10 3h5.5a1.5 1.5 0 0 1 1.5 1.5V10a1.5 1.5 0 0 1-.44 1.06l-6 6a1.5 1.5 0 0 1-2.12 0l-5.5-5.5a1.5 1.5 0 0 1 0-2.12l6-6A1.5 1.5 0 0 1 10 3Z"
            stroke="currentColor"
            strokeWidth="1.4"
            strokeLinejoin="round"
        />
        <circle cx="13" cy="7" r="1" fill="currentColor" />
    </>
);

function IconeCategoria({ icone, cor }: { icone: string; cor: string }) {
    return (
        <span
            aria-hidden="true"
            title={icone}
            className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
            style={{ backgroundColor: `${cor}1f` }}
        >
            <svg
                viewBox="0 0 20 20"
                fill="none"
                className="h-3.5 w-3.5"
                style={{ color: cor }}
            >
                {iconesPorNome[icone] ?? iconeIndefinido}
            </svg>
        </span>
    );
}

function LogoConta({ nome, logoUrl }: { nome: string; logoUrl: string }) {
    const [logoFalhou, setLogoFalhou] = useState(false);
    const inicial = nome.trim().charAt(0).toUpperCase();

    if (logoFalhou) {
        return (
            <span
                aria-hidden="true"
                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-tinta/10 text-[10px] font-bold text-tinta"
            >
                {inicial}
            </span>
        );
    }

    return (
        <img
            src={logoUrl}
            alt=""
            aria-hidden="true"
            className="h-6 w-6 shrink-0 rounded-full object-cover"
            onError={() => setLogoFalhou(true)}
        />
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
                                                <IconeCategoria
                                                    icone={
                                                        renda.categoria_renda
                                                            .icone
                                                    }
                                                    cor={
                                                        renda.categoria_renda
                                                            .cor
                                                    }
                                                />
                                                {renda.categoria_renda.nome}
                                            </span>
                                        </td>

                                        <td className="px-5 py-3.5 text-tinta-claro">
                                            <span className="inline-flex items-center gap-2">
                                                <LogoConta
                                                    nome={renda.conta.nome}
                                                    logoUrl={
                                                        renda.conta.logo_url
                                                    }
                                                />
                                                {renda.conta.nome}
                                            </span>
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
