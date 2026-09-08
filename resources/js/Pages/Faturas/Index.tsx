import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Fatura, FaturaResumo, FormaPagamento } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CartaoFatura from './Partials/CartaoFatura';
import ConfirmarDesfazerPagamentoFatura from './Partials/ConfirmarDesfazerPagamentoFatura';
import GerarFatura from './Partials/GerarFatura';
import MarcarComoPagaFatura from './Partials/MarcarComoPagaFatura';

interface AlvoDeFatura {
    aberto: boolean;
    fatura: Fatura | null;
}

const alvoFechado: AlvoDeFatura = { aberto: false, fatura: null };

export default function Index({
    faturas,
    cartoes,
    formasPagamento,
}: {
    faturas: FaturaResumo[];
    cartoes: FormaPagamento[];
    formasPagamento: FormaPagamento[];
}) {
    const usuario = usePage().props.auth.usuario!;

    const [marcarComoPaga, setMarcarComoPaga] =
        useState<AlvoDeFatura>(alvoFechado);
    const [desfazerPagamento, setDesfazerPagamento] =
        useState<AlvoDeFatura>(alvoFechado);

    const fecharMarcarComoPaga = () =>
        setMarcarComoPaga((atual) => ({ ...atual, aberto: false }));

    const fecharDesfazerPagamento = () =>
        setDesfazerPagamento((atual) => ({ ...atual, aberto: false }));

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                        Faturas
                    </h1>
                    <p className="mt-1 text-sm text-tinta-claro">
                        Faturas dos seus cartões de crédito — geradas a partir
                        das compras e parcelas lançadas em cada cartão.
                    </p>
                </div>
            }
        >
            <Head title="Faturas" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-7 sm:px-6 lg:px-8">
                {cartoes.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-tinta/20 bg-white px-10 py-14 text-center">
                        <h2 className="mx-auto max-w-[24ch] font-display text-xl font-bold text-tinta">
                            Nenhum cartão de crédito cadastrado
                        </h2>
                        <p className="mx-auto mt-2.5 max-w-[52ch] text-sm leading-relaxed text-tinta-claro">
                            Cadastre um cartão de crédito em Contas para
                            começar a gerar faturas.
                        </p>
                    </div>
                ) : (
                    <GerarFatura cartoes={cartoes} />
                )}

                {faturas.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-tinta/20 bg-white px-10 py-14 text-center">
                        <h2 className="mx-auto max-w-[24ch] font-display text-xl font-bold text-tinta">
                            Nenhuma fatura gerada ainda
                        </h2>
                        <p className="mx-auto mt-2.5 max-w-[52ch] text-sm leading-relaxed text-tinta-claro">
                            Gere a fatura de um cartão e competência acima
                            para vê-la aqui.
                        </p>
                    </div>
                ) : (
                    <div className="grid items-start gap-6 [grid-template-columns:repeat(auto-fill,minmax(350px,1fr))]">
                        {faturas.map((resumo) => (
                            <CartaoFatura
                                key={resumo.fatura.id}
                                resumo={resumo}
                                cor={usuario.cor}
                                aoMarcarComoPaga={() =>
                                    setMarcarComoPaga({
                                        aberto: true,
                                        fatura: resumo.fatura,
                                    })
                                }
                                aoDesfazerPagamento={() =>
                                    setDesfazerPagamento({
                                        aberto: true,
                                        fatura: resumo.fatura,
                                    })
                                }
                            />
                        ))}
                    </div>
                )}
            </div>

            <MarcarComoPagaFatura
                key={`marcar-${marcarComoPaga.fatura?.id ?? 'nenhuma'}`}
                fatura={marcarComoPaga.fatura}
                formasPagamento={formasPagamento}
                aberto={marcarComoPaga.aberto}
                aoFechar={fecharMarcarComoPaga}
            />

            <ConfirmarDesfazerPagamentoFatura
                fatura={desfazerPagamento.fatura}
                aberto={desfazerPagamento.aberto}
                aoFechar={fecharDesfazerPagamento}
            />
        </AuthenticatedLayout>
    );
}
