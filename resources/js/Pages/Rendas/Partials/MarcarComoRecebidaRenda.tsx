import BlocoCondicional from '@/Components/BlocoCondicional';
import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import type { FormaPagamento, Renda } from '@/types';
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

export default function MarcarComoRecebidaRenda({
    renda,
    competencia,
    formasPagamentoElegiveis,
    aberto,
    aoFechar,
}: {
    renda: Renda | null;
    competencia: string;
    formasPagamentoElegiveis: FormaPagamento[];
    aberto: boolean;
    aoFechar: () => void;
}) {
    const {
        data,
        setData,
        patch,
        processing,
        errors,
        clearErrors,
        reset,
        transform,
    } = useForm({
        competencia,
        forma_pagamento_id: '',
        data_recebimento: '',
        valor: renda ? paraReais(renda.valor) : '',
    });

    const nenhumaElegivel = formasPagamentoElegiveis.length === 0;
    const precisaEscolher = formasPagamentoElegiveis.length > 1;

    transform((dados) => ({
        ...dados,
        valor: paraCentavos(dados.valor),
    }));

    const submeter: FormEventHandler = (evento) => {
        evento.preventDefault();

        if (!renda) {
            return;
        }

        patch(route('rendas.marcar-como-recebida', renda.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                aoFechar();
            },
        });
    };

    const fechar = () => {
        clearErrors();
        aoFechar();
    };

    if (nenhumaElegivel) {
        return (
            <Modal show={aberto} onClose={fechar} maxWidth="md">
                <div className="p-6 sm:p-8">
                    <h2 className="font-display text-xl font-semibold text-tinta">
                        Marcar {renda?.descricao} como recebida
                    </h2>

                    <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                        Nenhuma forma de pagamento da conta recebe renda.
                        Configure uma antes de marcar o recebimento.
                    </p>

                    <div className="mt-8 flex justify-end">
                        <SecondaryButton onClick={fechar}>
                            Fechar
                        </SecondaryButton>
                    </div>
                </div>
            </Modal>
        );
    }

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <form onSubmit={submeter} className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    Marcar {renda?.descricao} como recebida
                </h2>

                <FormErrorSummary errors={errors} />

                <div className="mt-6">
                    <InputLabel htmlFor="valor" value="Valor recebido" />

                    <TextInput
                        id="valor"
                        className="mt-1.5 block w-full"
                        inputMode="decimal"
                        value={data.valor}
                        onChange={(evento) =>
                            setData('valor', evento.target.value)
                        }
                        placeholder="0,00"
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.valor} />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="data_recebimento"
                        value="Data de recebimento"
                    />

                    <TextInput
                        id="data_recebimento"
                        type="date"
                        className="mt-1.5 block w-full"
                        value={data.data_recebimento}
                        onChange={(evento) =>
                            setData('data_recebimento', evento.target.value)
                        }
                    />

                    <InputError
                        className="mt-2"
                        message={errors.data_recebimento}
                    />
                </div>

                <BlocoCondicional aberto={precisaEscolher}>
                    <div className="mt-4">
                        <InputLabel
                            htmlFor="forma_pagamento_id"
                            value="Forma de pagamento"
                        />

                        <SelectInput
                            id="forma_pagamento_id"
                            className="mt-1.5 block w-full"
                            value={data.forma_pagamento_id}
                            onChange={(evento) =>
                                setData(
                                    'forma_pagamento_id',
                                    evento.target.value,
                                )
                            }
                        >
                            <option value="" disabled>
                                Selecione…
                            </option>
                            {formasPagamentoElegiveis.map((forma) => (
                                <option key={forma.id} value={forma.id}>
                                    {forma.nome}
                                </option>
                            ))}
                        </SelectInput>

                        <InputError
                            className="mt-2"
                            message={errors.forma_pagamento_id}
                        />
                    </div>
                </BlocoCondicional>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        Marcar como recebida
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
