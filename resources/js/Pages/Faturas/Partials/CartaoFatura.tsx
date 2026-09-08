import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import type { FaturaResumo } from '@/types';
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

function formatarDiaMes(data: string): string {
    const [ano, mes, dia] = data.slice(0, 10).split('-');

    return `${dia}/${mes}/${ano}`;
}

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

function AvatarCartao({
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

export default function CartaoFatura({
    resumo,
    cor,
    aoMarcarComoPaga,
    aoDesfazerPagamento,
}: {
    resumo: FaturaResumo;
    cor: string;
    aoMarcarComoPaga: () => void;
    aoDesfazerPagamento: () => void;
}) {
    const { fatura, cartao, conta, paga, itens } = resumo;

    return (
        <article className="flex flex-col self-start rounded-xl border border-tinta/10 bg-white shadow-sm shadow-tinta/5 transition duration-200 ease-out hover:-translate-y-0.5 hover:border-tinta/20 hover:shadow-lg hover:shadow-tinta/5 motion-reduce:transform-none motion-reduce:transition-none">
            <div className="p-5">
                <div className="flex items-start gap-4">
                    <AvatarCartao
                        nome={conta.nome}
                        logoUrl={conta.logo_url}
                        cor={cor}
                    />

                    <div className="min-w-0 flex-1">
                        <h2 className="truncate font-display text-xl font-semibold text-tinta">
                            {conta.nome} · {cartao.nome}
                        </h2>

                        <p className="mt-0.5 text-xs capitalize text-tinta-claro/70">
                            {formatadorDeMes.format(
                                new Date(`${fatura.competencia}-01T00:00:00`),
                            )}
                        </p>
                    </div>

                    <span
                        className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                            paga
                                ? 'bg-verde/10 text-verde-escuro'
                                : 'bg-ouro/[0.16] text-[#8A6A2F]'
                        }`}
                    >
                        {paga ? 'Paga' : 'Pendente'}
                    </span>
                </div>

                <div className="mt-3.5 flex items-end justify-between gap-3">
                    <div>
                        <p className="text-[10.5px] font-semibold uppercase tracking-wide text-tinta-claro">
                            Valor da fatura
                        </p>
                        <p className="mt-0.5 font-display text-2xl font-bold leading-tight tabular-nums text-vinho-escuro">
                            {formatadorDeMoeda.format(fatura.valor / 100)}
                        </p>
                    </div>

                    <div className="text-right">
                        <p className="text-[10px] font-semibold uppercase tracking-wide text-tinta-claro">
                            Vencimento
                        </p>
                        <p className="mt-0.5 text-sm font-medium text-tinta">
                            {formatarDiaMes(fatura.data_vencimento)}
                        </p>
                    </div>
                </div>

                <div className="mt-4">
                    {paga ? (
                        <SecondaryButton
                            type="button"
                            onClick={aoDesfazerPagamento}
                            className="w-full justify-center"
                        >
                            Desfazer pagamento
                        </SecondaryButton>
                    ) : (
                        <PrimaryButton
                            type="button"
                            onClick={aoMarcarComoPaga}
                            className="w-full !rounded-xl !bg-verde-escuro justify-center hover:!bg-verde"
                        >
                            Marcar como paga
                        </PrimaryButton>
                    )}
                </div>
            </div>

            <div className="border-t border-tinta/10">
                <Disclosure>
                    {({ open }) => (
                        <>
                            <DisclosureButton className="flex w-full items-center justify-between px-5 py-2.5 text-sm font-medium text-tinta hover:bg-papel focus:outline-none focus:ring-2 focus:ring-ouro">
                                <span>
                                    Despesas na fatura ({itens.length})
                                </span>
                                <Seta aberta={open} />
                            </DisclosureButton>

                            <DisclosurePanel className="px-5 pb-4">
                                <ul className="divide-y divide-tinta/10">
                                    {itens.map((item) => (
                                        <li
                                            key={item.despesa.id}
                                            className="flex items-center justify-between gap-3 py-2.5"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-tinta">
                                                    {item.despesa.descricao}
                                                </p>
                                                <p className="text-xs text-tinta-claro">
                                                    {item.numeroParcela
                                                        ? `parcela ${item.numeroParcela}/${item.despesa.numero_parcelas}`
                                                        : item.despesa
                                                                .categoria_despesa
                                                              ?.nome}
                                                </p>
                                            </div>

                                            <span className="shrink-0 text-sm font-medium tabular-nums text-tinta">
                                                {formatadorDeMoeda.format(
                                                    item.valor / 100,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </DisclosurePanel>
                        </>
                    )}
                </Disclosure>
            </div>
        </article>
    );
}
