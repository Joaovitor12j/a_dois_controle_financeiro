import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import type { FormaPagamento } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

function rotuloCartao(cartao: FormaPagamento): string {
    return cartao.conta ? `${cartao.conta.nome} - ${cartao.nome}` : cartao.nome;
}

export default function GerarFatura({ cartoes }: { cartoes: FormaPagamento[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        forma_pagamento_id: '',
        competencia: '',
    });

    const submeter: FormEventHandler = (evento) => {
        evento.preventDefault();

        post(route('faturas.store'), {
            preserveScroll: true,
            onSuccess: () => reset('competencia'),
        });
    };

    return (
        <form
            onSubmit={submeter}
            className="rounded-xl border border-tinta/10 bg-white p-5"
        >
            <h2 className="font-display text-[17px] font-semibold text-tinta">
                Gerar fatura
            </h2>
            <p className="mt-1 text-sm text-tinta-claro">
                Escolha o cartão e a competência de vencimento. Se já existir
                uma fatura ainda não paga para esse cartão e mês, ela é
                recalculada.
            </p>

            <FormErrorSummary errors={errors} />

            <div className="mt-4 flex flex-wrap items-end gap-3.5">
                <div className="min-w-[220px] flex-1">
                    <InputLabel
                        htmlFor="forma_pagamento_id"
                        value="Cartão de crédito"
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
                        {cartoes.map((cartao) => (
                            <option key={cartao.id} value={cartao.id}>
                                {rotuloCartao(cartao)}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError
                        className="mt-2"
                        message={errors.forma_pagamento_id}
                    />
                </div>

                <div className="min-w-[160px]">
                    <InputLabel htmlFor="competencia" value="Competência" />
                    <TextInput
                        id="competencia"
                        type="month"
                        className="mt-1.5 block w-full"
                        value={data.competencia}
                        onChange={(evento) =>
                            setData('competencia', evento.target.value)
                        }
                    />
                    <InputError className="mt-2" message={errors.competencia} />
                </div>

                <PrimaryButton
                    disabled={
                        processing ||
                        !data.forma_pagamento_id ||
                        !data.competencia
                    }
                    className="!rounded-xl !bg-verde-escuro hover:!bg-verde"
                >
                    Gerar fatura
                </PrimaryButton>
            </div>
        </form>
    );
}
