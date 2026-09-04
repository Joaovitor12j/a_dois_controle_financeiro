import BlocoCondicional from '@/Components/BlocoCondicional';
import DateInput from '@/Components/DateInput';
import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import type { Despesa, FormaPagamento } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

function rotuloFormaPagamento(forma: FormaPagamento): string {
    return forma.conta ? `${forma.conta.nome} - ${forma.nome}` : forma.nome;
}

export default function MarcarComoPagaDespesa({
    despesa,
    competencia,
    formasPagamento,
    aberto,
    aoFechar,
}: {
    despesa: Despesa | null;
    competencia: string;
    formasPagamento: FormaPagamento[];
    aberto: boolean;
    aoFechar: () => void;
}) {
    const { data, setData, patch, processing, errors, clearErrors, reset } =
        useForm({
            competencia,
            forma_pagamento_id: '',
            data_pagamento: '',
        });

    const ehParcelada = despesa?.tipo_lancamento === 'parcelada';

    const submeter: FormEventHandler = (evento) => {
        evento.preventDefault();

        if (!despesa) {
            return;
        }

        patch(route('despesas.marcar-como-paga', despesa.id), {
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

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <form onSubmit={submeter} className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    Pagar {despesa?.descricao}
                </h2>

                <FormErrorSummary errors={errors} />

                <div className="mt-6">
                    <InputLabel
                        htmlFor="data_pagamento"
                        value="Data de pagamento"
                    />

                    <DateInput
                        id="data_pagamento"
                        className="mt-1.5 block w-full"
                        value={data.data_pagamento}
                        onChange={(valor) =>
                            setData('data_pagamento', valor)
                        }
                        hasError={Boolean(errors.data_pagamento)}
                        isFocused
                    />

                    <InputError
                        className="mt-2"
                        message={errors.data_pagamento}
                    />
                </div>

                <BlocoCondicional aberto={!ehParcelada}>
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
                                setData('forma_pagamento_id', evento.target.value)
                            }
                        >
                            <option value="" disabled>
                                Selecione…
                            </option>
                            {formasPagamento.map((forma) => (
                                <option key={forma.id} value={forma.id}>
                                    {rotuloFormaPagamento(forma)}
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
                        Marcar como paga
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
