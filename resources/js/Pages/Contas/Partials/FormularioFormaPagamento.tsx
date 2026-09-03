import Checkbox from '@/Components/Checkbox';
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
    credito: 'Crédito',
    vale: 'Vale',
    beneficio: 'Benefício',
};

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
        recebe_renda: formaPagamento?.recebe_renda ?? false,
        saldo_inicial: '',
        data_saldo_inicial: '',
        limite_total: formaPagamento?.cartao_credito
            ? paraReais(formaPagamento.cartao_credito.limite_total)
            : '',
        limite_usado_abertura: '',
        dia_fechamento: formaPagamento?.cartao_credito
            ? String(formaPagamento.cartao_credito.dia_fechamento)
            : '',
        dia_vencimento: formaPagamento?.cartao_credito
            ? String(formaPagamento.cartao_credito.dia_vencimento)
            : '',
    });

    const ehCredito = data.tipo === 'credito';

    transform((dados) => ({
        ...dados,
        recebe_renda: dados.tipo === 'credito' ? null : dados.recebe_renda,
        saldo_inicial: paraCentavos(dados.saldo_inicial),
        limite_total: paraCentavos(dados.limite_total),
        limite_usado_abertura: paraCentavos(dados.limite_usado_abertura),
        dia_fechamento:
            dados.dia_fechamento === '' ? null : Number(dados.dia_fechamento),
        dia_vencimento:
            dados.dia_vencimento === '' ? null : Number(dados.dia_vencimento),
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
                        ? 'Ajuste o nome desta forma de pagamento.'
                        : 'Débito, dinheiro, pix, crédito, vale ou benefício — o meio por onde o dinheiro passa nesta conta.'}
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

                    {formaPagamento ? (
                        <p className="mt-1.5 text-sm font-medium text-tinta">
                            {rotuloTipo[formaPagamento.tipo]}
                        </p>
                    ) : (
                        <>
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
                                {Object.entries(rotuloTipo).map(
                                    ([valor, rotulo]) => (
                                        <option key={valor} value={valor}>
                                            {rotulo}
                                        </option>
                                    ),
                                )}
                            </SelectInput>

                            <InputError className="mt-2" message={errors.tipo} />
                        </>
                    )}
                </div>

                {!ehCredito && (
                    <div className="mt-4">
                        <label className="flex items-center gap-2">
                            <Checkbox
                                id="recebe_renda"
                                checked={data.recebe_renda}
                                onChange={(evento) =>
                                    setData(
                                        'recebe_renda',
                                        evento.target.checked,
                                    )
                                }
                            />

                            <span className="text-sm text-tinta">
                                Recebe renda
                            </span>
                        </label>

                        <p className="mt-1 text-xs text-tinta-claro">
                            Marque para que esta forma de pagamento possa ser
                            usada para receber renda nesta conta.
                        </p>

                        <InputError
                            className="mt-2"
                            message={errors.recebe_renda}
                        />
                    </div>
                )}

                {!formaPagamento && !ehCredito && (
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

                {ehCredito && (
                    <>
                        <div className="mt-4">
                            <InputLabel
                                htmlFor="limite_total"
                                value="Limite total"
                            />

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

                        {!formaPagamento && (
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
                                        setData(
                                            'dia_fechamento',
                                            evento.target.value,
                                        )
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
                                        setData(
                                            'dia_vencimento',
                                            evento.target.value,
                                        )
                                    }
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.dia_vencimento}
                                />
                            </div>
                        </div>
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
