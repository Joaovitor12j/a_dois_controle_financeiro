import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CartaoCredito, Conta, FormaPagamento } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CartaoConta from './Partials/CartaoConta';
import ConfirmarExclusaoCartaoCredito from './Partials/ConfirmarExclusaoCartaoCredito';
import ConfirmarExclusaoConta from './Partials/ConfirmarExclusaoConta';
import ConfirmarExclusaoFormaPagamento from './Partials/ConfirmarExclusaoFormaPagamento';
import FormularioCartaoCredito from './Partials/FormularioCartaoCredito';
import FormularioConta from './Partials/FormularioConta';
import FormularioFormaPagamento from './Partials/FormularioFormaPagamento';

interface AlvoDeModal {
    aberto: boolean;
    conta: Conta | null;
}

interface AlvoDeFormularioFormaPagamento {
    aberto: boolean;
    conta: Conta | null;
    formaPagamento: FormaPagamento | null;
}

interface AlvoDeExclusaoFormaPagamento {
    aberto: boolean;
    formaPagamento: FormaPagamento | null;
}

interface AlvoDeFormularioCartaoCredito {
    aberto: boolean;
    conta: Conta | null;
    cartaoCredito: CartaoCredito | null;
}

interface AlvoDeExclusaoCartaoCredito {
    aberto: boolean;
    cartaoCredito: CartaoCredito | null;
}

const modalFechado: AlvoDeModal = { aberto: false, conta: null };

const formularioFormaPagamentoFechado: AlvoDeFormularioFormaPagamento = {
    aberto: false,
    conta: null,
    formaPagamento: null,
};

const exclusaoFormaPagamentoFechada: AlvoDeExclusaoFormaPagamento = {
    aberto: false,
    formaPagamento: null,
};

const formularioCartaoCreditoFechado: AlvoDeFormularioCartaoCredito = {
    aberto: false,
    conta: null,
    cartaoCredito: null,
};

const exclusaoCartaoCreditoFechada: AlvoDeExclusaoCartaoCredito = {
    aberto: false,
    cartaoCredito: null,
};

export default function Index({ contas }: { contas: Conta[] }) {
    const usuario = usePage().props.auth.usuario!;

    const [formulario, setFormulario] = useState<AlvoDeModal>(modalFechado);
    const [exclusao, setExclusao] = useState<AlvoDeModal>(modalFechado);
    const [aberturas, setAberturas] = useState(0);

    const [formularioFormaPagamento, setFormularioFormaPagamento] =
        useState<AlvoDeFormularioFormaPagamento>(
            formularioFormaPagamentoFechado,
        );
    const [exclusaoFormaPagamento, setExclusaoFormaPagamento] =
        useState<AlvoDeExclusaoFormaPagamento>(exclusaoFormaPagamentoFechada);
    const [aberturasFormaPagamento, setAberturasFormaPagamento] = useState(0);

    const [formularioCartaoCredito, setFormularioCartaoCredito] =
        useState<AlvoDeFormularioCartaoCredito>(
            formularioCartaoCreditoFechado,
        );
    const [exclusaoCartaoCredito, setExclusaoCartaoCredito] =
        useState<AlvoDeExclusaoCartaoCredito>(exclusaoCartaoCreditoFechada);
    const [aberturasCartaoCredito, setAberturasCartaoCredito] = useState(0);

    const abrirFormulario = (conta: Conta | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, conta });
    };

    const fecharFormulario = () =>
        setFormulario((atual) => ({ ...atual, aberto: false }));

    const fecharExclusao = () =>
        setExclusao((atual) => ({ ...atual, aberto: false }));

    const abrirFormularioFormaPagamento = (
        conta: Conta,
        formaPagamento: FormaPagamento | null,
    ) => {
        setAberturasFormaPagamento((quantas) => quantas + 1);
        setFormularioFormaPagamento({ aberto: true, conta, formaPagamento });
    };

    const fecharFormularioFormaPagamento = () =>
        setFormularioFormaPagamento((atual) => ({ ...atual, aberto: false }));

    const fecharExclusaoFormaPagamento = () =>
        setExclusaoFormaPagamento((atual) => ({ ...atual, aberto: false }));

    const abrirFormularioCartaoCredito = (
        conta: Conta,
        cartaoCredito: CartaoCredito | null,
    ) => {
        setAberturasCartaoCredito((quantas) => quantas + 1);
        setFormularioCartaoCredito({ aberto: true, conta, cartaoCredito });
    };

    const fecharFormularioCartaoCredito = () =>
        setFormularioCartaoCredito((atual) => ({ ...atual, aberto: false }));

    const fecharExclusaoCartaoCredito = () =>
        setExclusaoCartaoCredito((atual) => ({ ...atual, aberto: false }));

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Contas
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            {contas.length === 0
                                ? 'Nenhuma conta ainda'
                                : contas.length === 1
                                  ? '1 conta sua'
                                  : `${contas.length} contas suas`}
                        </p>
                    </div>

                    {contas.length > 0 && (
                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                        >
                            Nova conta
                        </PrimaryButton>
                    )}
                </div>
            }
        >
            <Head title="Contas" />

            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {contas.length === 0 ? (
                    <div className="flex flex-col items-center rounded-xl border border-dashed border-tinta/20 bg-white/50 px-6 py-16 text-center">
                        <span
                            aria-hidden="true"
                            className="h-20 w-20 rounded-full border-2 border-dashed border-tinta/25"
                        />

                        <h2 className="mt-6 font-display text-2xl font-semibold text-tinta">
                            Nenhuma conta por aqui ainda
                        </h2>

                        <p className="mt-2 max-w-md text-sm leading-relaxed text-tinta-claro">
                            Conta é só o registro de onde o dinheiro passou — não
                            entra em nenhum cálculo. Cadastre as suas para saber,
                            depois, de onde saiu cada gasto.
                        </p>

                        <PrimaryButton
                            type="button"
                            onClick={() => abrirFormulario(null)}
                            className="mt-8"
                        >
                            Abrir a primeira conta
                        </PrimaryButton>
                    </div>
                ) : (
                    <div className="grid items-start gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {contas.map((conta) => (
                            <CartaoConta
                                key={conta.id}
                                conta={conta}
                                cor={usuario.cor}
                                aoRenomear={() => abrirFormulario(conta)}
                                aoExcluir={() =>
                                    setExclusao({ aberto: true, conta })
                                }
                                aoCriarFormaPagamento={() =>
                                    abrirFormularioFormaPagamento(conta, null)
                                }
                                aoEditarFormaPagamento={(formaPagamento) =>
                                    abrirFormularioFormaPagamento(
                                        conta,
                                        formaPagamento,
                                    )
                                }
                                aoExcluirFormaPagamento={(formaPagamento) =>
                                    setExclusaoFormaPagamento({
                                        aberto: true,
                                        formaPagamento,
                                    })
                                }
                                aoCriarCartaoCredito={() =>
                                    abrirFormularioCartaoCredito(conta, null)
                                }
                                aoEditarCartaoCredito={(cartaoCredito) =>
                                    abrirFormularioCartaoCredito(
                                        conta,
                                        cartaoCredito,
                                    )
                                }
                                aoExcluirCartaoCredito={(cartaoCredito) =>
                                    setExclusaoCartaoCredito({
                                        aberto: true,
                                        cartaoCredito,
                                    })
                                }
                            />
                        ))}
                    </div>
                )}
            </div>

            <FormularioConta
                key={`${formulario.conta?.id ?? 'nova'}-${aberturas}`}
                conta={formulario.conta}
                aberto={formulario.aberto}
                aoFechar={fecharFormulario}
            />

            <ConfirmarExclusaoConta
                conta={exclusao.conta}
                aberto={exclusao.aberto}
                aoFechar={fecharExclusao}
            />

            <FormularioFormaPagamento
                key={`${formularioFormaPagamento.formaPagamento?.id ?? formularioFormaPagamento.conta?.id ?? 'nova'}-${aberturasFormaPagamento}`}
                contaId={formularioFormaPagamento.conta?.id ?? null}
                formaPagamento={formularioFormaPagamento.formaPagamento}
                aberto={formularioFormaPagamento.aberto}
                aoFechar={fecharFormularioFormaPagamento}
            />

            <ConfirmarExclusaoFormaPagamento
                formaPagamento={exclusaoFormaPagamento.formaPagamento}
                aberto={exclusaoFormaPagamento.aberto}
                aoFechar={fecharExclusaoFormaPagamento}
            />

            <FormularioCartaoCredito
                key={`${formularioCartaoCredito.cartaoCredito?.id ?? formularioCartaoCredito.conta?.id ?? 'novo'}-${aberturasCartaoCredito}`}
                contaId={formularioCartaoCredito.conta?.id ?? null}
                cartaoCredito={formularioCartaoCredito.cartaoCredito}
                aberto={formularioCartaoCredito.aberto}
                aoFechar={fecharFormularioCartaoCredito}
            />

            <ConfirmarExclusaoCartaoCredito
                cartaoCredito={exclusaoCartaoCredito.cartaoCredito}
                aberto={exclusaoCartaoCredito.aberto}
                aoFechar={fecharExclusaoCartaoCredito}
            />
        </AuthenticatedLayout>
    );
}
