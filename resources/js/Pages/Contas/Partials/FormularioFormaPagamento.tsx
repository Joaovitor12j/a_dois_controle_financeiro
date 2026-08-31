import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import type { FormaPagamento, TipoFormaPagamento } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const rotuloTipo: Record<TipoFormaPagamento, string> = {
    debito: 'Débito',
    dinheiro: 'Dinheiro',
    pix: 'Pix',
};

function paraCentavos(valorEmReais: string): number | null {
    const normalizado = valorEmReais.trim().replace(',', '.');

    if (normalizado === '') {
        return null;
    }

    const numero = Number(normalizado);

    return Number.isFinite(numero) ? Math.round(numero * 100) : null;
}

export default function FormularioFormaPagamento({
    contaId,
    formaPagamento,
    aberto,
    aoFechar,
}: {
    contaId: string | null;
    formaPagamento: FormaPagamento | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        clearErrors,
        transform,
    } = useForm({
        conta_id: contaId ?? '',
        nome: formaPagamento?.nome ?? '',
        tipo: formaPagamento?.tipo ?? ('debito' as TipoFormaPagamento),
        saldo_inicial: '',
        data_saldo_inicial: '',
    });

    transform((dados) => ({
        ...dados,
        saldo_inicial: paraCentavos(dados.saldo_inicial),
    }));

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (formaPagamento) {
            put(route('formas-pagamento.update', formaPagamento.id), opcoes);
        } else {
            post(route('formas-pagamento.store'), opcoes);
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
                    {formaPagamento
                        ? 'Editar forma de pagamento'
                        : 'Nova forma de pagamento'}
                </h2>

                <p className="mt-1.5 text-sm text-tinta-claro">
                    {formaPagamento
                        ? 'Ajuste o nome ou o tipo desta forma de pagamento.'
                        : 'Débito, dinheiro ou pix — o meio por onde o dinheiro passa nesta conta.'}
                </p>

                <FormErrorSummary errors={errors} />

                <div className="mt-6">
                    <InputLabel htmlFor="nome" value="Nome" />

                    <TextInput
                        id="nome"
                        className="mt-1.5 block w-full"
                        value={data.nome}
                        onChange={(evento) =>
                            setData('nome', evento.target.value)
                        }
                        placeholder="Cartão de débito, Carteira, Pix Nubank…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.nome} />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="tipo" value="Tipo" />

                    <SelectInput
                        id="tipo"
                        className="mt-1.5 block w-full"
                        value={data.tipo}
                        onChange={(evento) =>
                            setData(
                                'tipo',
                                evento.target.value as TipoFormaPagamento,
                            )
                        }
                    >
                        {Object.entries(rotuloTipo).map(([valor, rotulo]) => (
                            <option key={valor} value={valor}>
                                {rotulo}
                            </option>
                        ))}
                    </SelectInput>

                    <InputError className="mt-2" message={errors.tipo} />
                </div>

                {!formaPagamento && (
                    <>
                        <div className="mt-4">
                            <InputLabel
                                htmlFor="saldo_inicial"
                                value="Saldo inicial (opcional)"
                            />

                            <TextInput
                                id="saldo_inicial"
                                className="mt-1.5 block w-full"
                                inputMode="decimal"
                                value={data.saldo_inicial}
                                onChange={(evento) =>
                                    setData(
                                        'saldo_inicial',
                                        evento.target.value,
                                    )
                                }
                                placeholder="0,00"
                            />

                            <InputError
                                className="mt-2"
                                message={errors.saldo_inicial}
                            />
                        </div>

                        {data.saldo_inicial.trim() !== '' && (
                            <div className="mt-4">
                                <InputLabel
                                    htmlFor="data_saldo_inicial"
                                    value="Data do saldo inicial"
                                />

                                <TextInput
                                    id="data_saldo_inicial"
                                    type="date"
                                    className="mt-1.5 block w-full"
                                    value={data.data_saldo_inicial}
                                    onChange={(evento) =>
                                        setData(
                                            'data_saldo_inicial',
                                            evento.target.value,
                                        )
                                    }
                                    required
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.data_saldo_inicial}
                                />
                            </div>
                        )}
                    </>
                )}

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {formaPagamento ? 'Salvar' : 'Criar forma de pagamento'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
