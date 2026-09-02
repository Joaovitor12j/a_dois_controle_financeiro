import Dropdown from '@/Components/Dropdown';
import type { Conta, FormaPagamento, TipoFormaPagamento } from '@/types';
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
} from '@headlessui/react';
import { useState } from 'react';

const formatadorDeMes = new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
    year: 'numeric',
});

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const rotuloTipo: Record<TipoFormaPagamento, string> = {
    debito: 'Débito',
    dinheiro: 'Dinheiro',
    pix: 'Pix',
    credito: 'Crédito',
    vale: 'Vale',
    beneficio: 'Benefício',
};

const corTipo: Record<TipoFormaPagamento, string> = {
    debito: 'bg-tinta/10 text-tinta',
    dinheiro: 'bg-verde/10 text-verde-escuro',
    pix: 'bg-ouro/20 text-ouro',
    credito: 'bg-vinho/10 text-vinho-escuro',
    vale: 'bg-ouro/10 text-ouro',
    beneficio: 'bg-ouro/10 text-ouro',
};

function Seta({ aberta }: { aberta: boolean }) {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="none"
            className={`h-4 w-4 shrink-0 stroke-tinta-claro transition-transform duration-150 ${aberta ? 'rotate-180' : ''}`}
        >
            <path
                d="M5 7.5 10 12.5 15 7.5"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

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

function BotaoAdicionar({
    aoClicar,
    children,
}: {
    aoClicar: () => void;
    children: string;
}) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            className="inline-flex items-center gap-1 rounded-lg border border-dashed border-tinta/20 px-3 py-1.5 text-sm font-medium text-tinta-claro transition duration-150 ease-in-out hover:border-ouro hover:bg-papel hover:text-ouro focus:outline-none focus:ring-2 focus:ring-ouro"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
                className="h-3.5 w-3.5"
            >
                <path
                    d="M10 4.5v11M4.5 10h11"
                    stroke="currentColor"
                    strokeWidth="1.6"
                    strokeLinecap="round"
                />
            </svg>
            {children}
        </button>
    );
}

function ItemMenuConta({
    tom = 'padrao',
    onClick,
    children,
}: {
    tom?: 'padrao' | 'perigo';
    onClick: () => void;
    children: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out hover:bg-papel focus:bg-papel focus:outline-none ${
                tom === 'perigo' ? 'text-vinho' : 'text-tinta'
            }`}
        >
            {children}
        </button>
    );
}

function MenuConta({
    aoRenomear,
    aoExcluir,
}: {
    aoRenomear: () => void;
    aoExcluir: () => void;
}) {
    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    aria-label="Mais ações da conta"
                    title="Mais ações"
                    className="rounded-lg p-1.5 text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        className="h-5 w-5"
                    >
                        <circle cx="10" cy="4.5" r="1.4" />
                        <circle cx="10" cy="10" r="1.4" />
                        <circle cx="10" cy="15.5" r="1.4" />
                    </svg>
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content align="right" contentClasses="py-1 bg-white">
                <ItemMenuConta onClick={aoRenomear}>Renomear</ItemMenuConta>
                <ItemMenuConta tom="perigo" onClick={aoExcluir}>
                    Excluir
                </ItemMenuConta>
            </Dropdown.Content>
        </Dropdown>
    );
}

function AvatarConta({
    nome,
    logoUrl,
    cor,
}: {
    nome: string;
    logoUrl: string;
    cor: string;
}) {
    const [logoFalhou, setLogoFalhou] = useState(false);
    const inicial = nome.trim().charAt(0).toUpperCase();

    if (logoFalhou) {
        return (
            <span
                aria-hidden="true"
                className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold text-papel"
                style={{ backgroundColor: cor }}
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
            className="h-12 w-12 shrink-0 rounded-full object-cover"
            onError={() => setLogoFalhou(true)}
        />
    );
}

function LinhaFormaPagamento({
    formaPagamento,
    aoEditar,
    aoExcluir,
}: {
    formaPagamento: FormaPagamento;
    aoEditar: () => void;
    aoExcluir: () => void;
}) {
    return (
        <li className="py-2.5">
            <div className="flex items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-3">
                    <span
                        className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ${corTipo[formaPagamento.tipo]}`}
                    >
                        {rotuloTipo[formaPagamento.tipo]}
                    </span>

                    <span className="min-w-0 truncate text-sm font-medium text-tinta">
                        {formaPagamento.nome}
                    </span>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    {formaPagamento.saldo_inicial && (
                        <span className="mr-1 text-sm tabular-nums text-tinta-claro">
                            {formatadorDeMoeda.format(
                                formaPagamento.saldo_inicial.valor / 100,
                            )}
                        </span>
                    )}

                    <BotaoEditar
                        rotulo="Editar forma de pagamento"
                        aoClicar={aoEditar}
                    />
                    <BotaoExcluir
                        rotulo="Excluir forma de pagamento"
                        aoClicar={aoExcluir}
                    />
                </div>
            </div>

            {formaPagamento.cartao_credito && (
                <dl className="mt-2 grid grid-cols-2 gap-3">
                    <div>
                        <dt className="text-xs text-tinta-claro">Limite</dt>
                        <dd className="text-sm font-medium tabular-nums text-tinta">
                            {formatadorDeMoeda.format(
                                formaPagamento.cartao_credito.limite_total /
                                    100,
                            )}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Usado na abertura
                        </dt>
                        <dd className="text-sm font-medium tabular-nums text-tinta">
                            {formatadorDeMoeda.format(
                                formaPagamento.cartao_credito
                                    .limite_usado_abertura / 100,
                            )}
                        </dd>
                    </div>
                </dl>
            )}
        </li>
    );
}

export default function CartaoConta({
    conta,
    cor,
    aoRenomear,
    aoExcluir,
    aoCriarFormaPagamento,
    aoEditarFormaPagamento,
    aoExcluirFormaPagamento,
}: {
    conta: Conta;
    cor: string;
    aoRenomear: () => void;
    aoExcluir: () => void;
    aoCriarFormaPagamento: () => void;
    aoEditarFormaPagamento: (formaPagamento: FormaPagamento) => void;
    aoExcluirFormaPagamento: (formaPagamento: FormaPagamento) => void;
}) {
    const contaVazia = conta.formas_pagamento.length === 0;

    return (
        <article className="flex flex-col self-start rounded-xl border border-tinta/10 bg-white shadow-sm shadow-tinta/5 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-tinta/20 hover:shadow-lg hover:shadow-tinta/5 motion-reduce:transform-none motion-reduce:transition-none">
            <div className="flex items-start gap-4 p-5">
                <AvatarConta nome={conta.nome} logoUrl={conta.logo_url} cor={cor} />

                <div className="min-w-0 flex-1">
                    <h2 className="truncate font-display text-xl font-semibold text-tinta">
                        {conta.nome}
                    </h2>

                    <p className="mt-0.5 text-xs text-tinta-claro/70">
                        aberta em {formatadorDeMes.format(new Date(conta.created_at))}
                    </p>
                </div>

                <MenuConta aoRenomear={aoRenomear} aoExcluir={aoExcluir} />
            </div>

            {contaVazia ? (
                <div className="flex flex-col items-start gap-3 border-t border-tinta/10 px-5 py-6">
                    <p className="text-sm text-tinta-claro">
                        Nenhuma forma de pagamento cadastrada nesta conta
                        ainda.
                    </p>

                    <BotaoAdicionar aoClicar={aoCriarFormaPagamento}>
                        Forma de pagamento
                    </BotaoAdicionar>
                </div>
            ) : (
                <div className="border-t border-tinta/10">
                    <Disclosure>
                        {({ open }) => (
                            <>
                                <DisclosureButton className="flex w-full items-center justify-between px-5 py-2.5 text-sm font-medium text-tinta hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro">
                                    <span>
                                        Formas de pagamento (
                                        {conta.formas_pagamento.length})
                                    </span>

                                    <Seta aberta={open} />
                                </DisclosureButton>

                                <DisclosurePanel className="px-5 pb-4">
                                    <ul className="divide-y divide-tinta/10">
                                        {conta.formas_pagamento.map(
                                            (formaPagamento) => (
                                                <LinhaFormaPagamento
                                                    key={formaPagamento.id}
                                                    formaPagamento={
                                                        formaPagamento
                                                    }
                                                    aoEditar={() =>
                                                        aoEditarFormaPagamento(
                                                            formaPagamento,
                                                        )
                                                    }
                                                    aoExcluir={() =>
                                                        aoExcluirFormaPagamento(
                                                            formaPagamento,
                                                        )
                                                    }
                                                />
                                            ),
                                        )}
                                    </ul>

                                    <div className="mt-3">
                                        <BotaoAdicionar
                                            aoClicar={aoCriarFormaPagamento}
                                        >
                                            Forma de pagamento
                                        </BotaoAdicionar>
                                    </div>
                                </DisclosurePanel>
                            </>
                        )}
                    </Disclosure>
                </div>
            )}
        </article>
    );
}
