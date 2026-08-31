import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import type { CartaoCredito } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

function paraCentavos(valorEmReais: string): number | null {
    const normalizado = valorEmReais.trim().replace(',', '.');

    if (normalizado === '') {
        return null;
    }

    const numero = Number(normalizado);

    return Number.isFinite(numero) ? Math.round(numero * 100) : null;
}

function paraReais(valorEmCentavos: number): string {
    return (valorEmCentavos / 100).toFixed(2).replace('.', ',');
}

export default function FormularioCartaoCredito({
    contaId,
    cartaoCredito,
    aberto,
    aoFechar,
}: {
    contaId: string | null;
    cartaoCredito: CartaoCredito | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const { data, setData, post, put, processing, errors, clearErrors, transform } =
        useForm({
            conta_id: contaId ?? '',
            nome: cartaoCredito?.nome ?? '',
            limite_total: cartaoCredito
                ? paraReais(cartaoCredito.limite_total)
                : '',
            limite_usado_abertura: '',
            dia_fechamento: cartaoCredito
                ? String(cartaoCredito.dia_fechamento)
                : '',
            dia_vencimento: cartaoCredito
                ? String(cartaoCredito.dia_vencimento)
                : '',
        });

    transform((dados) => ({
        ...dados,
        limite_total: paraCentavos(dados.limite_total),
        limite_usado_abertura: paraCentavos(dados.limite_usado_abertura),
        dia_fechamento: dados.dia_fechamento === '' ? null : Number(dados.dia_fechamento),
        dia_vencimento: dados.dia_vencimento === '' ? null : Number(dados.dia_vencimento),
    }));

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (cartaoCredito) {
            put(route('cartoes-credito.update', cartaoCredito.id), opcoes);
        } else {
            post(route('cartoes-credito.store'), opcoes);
        }
    };

    const fechar = () => {
        clearErrors();
        aoFechar();
    };

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <form onSubmit={submit} className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    {cartaoCredito
                        ? 'Editar cartão de crédito'
                        : 'Novo cartão de crédito'}
                </h2>

                <p className="mt-1.5 text-sm text-tinta-claro">
                    {cartaoCredito
                        ? 'Ajuste o nome, o limite ou o ciclo de fatura deste cartão.'
                        : 'Limite e ciclo de fatura do cartão nesta conta.'}
                </p>

                <div className="mt-6">
                    <InputLabel htmlFor="nome" value="Nome" />

                    <TextInput
                        id="nome"
                        className="mt-1.5 block w-full"
                        value={data.nome}
                        onChange={(evento) =>
                            setData('nome', evento.target.value)
                        }
                        placeholder="Nubank, Inter, Itaú…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.nome} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="limite_total" value="Limite total" />

                    <TextInput
                        id="limite_total"
                        className="mt-1.5 block w-full"
                        inputMode="decimal"
                        value={data.limite_total}
                        onChange={(evento) =>
                            setData('limite_total', evento.target.value)
                        }
                        placeholder="0,00"
                    />

                    <InputError
                        className="mt-2"
                        message={errors.limite_total}
                    />
                </div>

                {!cartaoCredito && (
                    <div className="mt-4">
                        <InputLabel
                            htmlFor="limite_usado_abertura"
                            value="Limite já usado na abertura (opcional)"
                        />

                        <TextInput
                            id="limite_usado_abertura"
                            className="mt-1.5 block w-full"
                            inputMode="decimal"
                            value={data.limite_usado_abertura}
                            onChange={(evento) =>
                                setData(
                                    'limite_usado_abertura',
                                    evento.target.value,
                                )
                            }
                            placeholder="0,00"
                        />

                        <InputError
                            className="mt-2"
                            message={errors.limite_usado_abertura}
                        />
                    </div>
                )}

                <div className="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel
                            htmlFor="dia_fechamento"
                            value="Dia de fechamento"
                        />

                        <TextInput
                            id="dia_fechamento"
                            className="mt-1.5 block w-full"
                            type="number"
                            min={1}
                            max={31}
                            value={data.dia_fechamento}
                            onChange={(evento) =>
                                setData('dia_fechamento', evento.target.value)
                            }
                        />

                        <InputError
                            className="mt-2"
                            message={errors.dia_fechamento}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="dia_vencimento"
                            value="Dia de vencimento"
                        />

                        <TextInput
                            id="dia_vencimento"
                            className="mt-1.5 block w-full"
                            type="number"
                            min={1}
                            max={31}
                            value={data.dia_vencimento}
                            onChange={(evento) =>
                                setData('dia_vencimento', evento.target.value)
                            }
                        />

                        <InputError
                            className="mt-2"
                            message={errors.dia_vencimento}
                        />
                    </div>
                </div>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {cartaoCredito ? 'Salvar' : 'Criar cartão de crédito'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
