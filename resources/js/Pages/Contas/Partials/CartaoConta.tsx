import type { Conta, FormaPagamento, TipoFormaPagamento } from '@/types';
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
} from '@headlessui/react';

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
};

const corTipo: Record<TipoFormaPagamento, string> = {
    debito: 'bg-tinta/10 text-tinta',
    dinheiro: 'bg-verde/10 text-verde-escuro',
    pix: 'bg-ouro/20 text-ouro',
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
        <li className="flex items-center gap-3 py-2.5">
            <span
                className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ${corTipo[formaPagamento.tipo]}`}
            >
                {rotuloTipo[formaPagamento.tipo]}
            </span>

            <span className="min-w-0 flex-1 truncate text-sm font-medium text-tinta">
                {formaPagamento.nome}
            </span>

            {formaPagamento.saldo_inicial && (
                <span className="shrink-0 text-sm text-tinta-claro">
                    {formatadorDeMoeda.format(
                        formaPagamento.saldo_inicial.valor / 100,
                    )}
                </span>
            )}

            <button
                type="button"
                onClick={aoEditar}
                className="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
            >
                Editar
            </button>

            <button
                type="button"
                onClick={aoExcluir}
                className="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-vinho transition duration-150 ease-in-out hover:bg-vinho/5 focus:outline-none focus:ring-2 focus:ring-vinho"
            >
                Excluir
            </button>
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
    const inicial = conta.nome.trim().charAt(0).toUpperCase();

    return (
        <article className="flex flex-col rounded-xl border border-tinta/10 bg-white transition duration-200 ease-out hover:-translate-y-0.5 hover:border-tinta/20 hover:shadow-lg hover:shadow-tinta/5 motion-reduce:transform-none motion-reduce:transition-none">
            <div className="flex flex-1 items-start gap-4 p-5">
                <span
                    aria-hidden="true"
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold text-papel"
                    style={{ backgroundColor: cor }}
                >
                    {inicial}
                </span>

                <div className="min-w-0 flex-1">
                    <h2 className="truncate font-display text-xl font-semibold text-tinta">
                        {conta.nome}
                    </h2>

                    <p className="mt-1 text-sm text-tinta-claro">
                        aberta em {formatadorDeMes.format(new Date(conta.created_at))}
                    </p>
                </div>
            </div>

            <div className="border-t border-tinta/10">
                <Disclosure>
                    {({ open }) => (
                        <>
                            <DisclosureButton className="flex w-full items-center justify-between px-5 py-2.5 text-sm font-medium text-tinta hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro">
                                <span>
                                    Formas de pagamento
                                    {conta.formas_pagamento.length > 0 &&
                                        ` (${conta.formas_pagamento.length})`}
                                </span>

                                <Seta aberta={open} />
                            </DisclosureButton>

                            <DisclosurePanel className="px-5 pb-4">
                                {conta.formas_pagamento.length === 0 ? (
                                    <div className="flex flex-col items-start gap-2 py-2">
                                        <p className="text-sm text-tinta-claro">
                                            Nenhuma forma de pagamento
                                            cadastrada nesta conta.
                                        </p>

                                        <button
                                            type="button"
                                            onClick={aoCriarFormaPagamento}
                                            className="text-sm font-medium text-ouro hover:underline"
                                        >
                                            + Forma de pagamento
                                        </button>
                                    </div>
                                ) : (
                                    <>
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

                                        <button
                                            type="button"
                                            onClick={aoCriarFormaPagamento}
                                            className="mt-2 text-sm font-medium text-ouro hover:underline"
                                        >
                                            + Forma de pagamento
                                        </button>
                                    </>
                                )}
                            </DisclosurePanel>
                        </>
                    )}
                </Disclosure>

                <Disclosure>
                    {({ open }) => (
                        <>
                            <DisclosureButton className="flex w-full items-center justify-between border-t border-tinta/10 px-5 py-2.5 text-sm font-medium text-tinta hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro">
                                <span>Cartões de crédito</span>

                                <Seta aberta={open} />
                            </DisclosureButton>

                            <DisclosurePanel className="px-5 pb-4">
                                <p className="py-2 text-sm text-tinta-claro">
                                    Em breve.
                                </p>
                            </DisclosurePanel>
                        </>
                    )}
                </Disclosure>
            </div>

            <div className="flex gap-1 border-t border-tinta/10 px-3 py-2">
                <button
                    type="button"
                    onClick={aoRenomear}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
                >
                    Renomear
                </button>

                <button
                    type="button"
                    onClick={aoExcluir}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium text-vinho transition duration-150 ease-in-out hover:bg-vinho/5 focus:outline-none focus:ring-2 focus:ring-vinho"
                >
                    Excluir
                </button>
            </div>
        </article>
    );
}
