import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import type { Conta } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function FormularioConta({
    conta,
    aberto,
    aoFechar,
}: {
    conta: Conta | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm({ nome: conta?.nome ?? '' });

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (conta) {
            put(route('contas.update', conta.id), opcoes);
        } else {
            post(route('contas.store'), opcoes);
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
                    {conta ? 'Renomear conta' : 'Nova conta'}
                </h2>

                <p className="mt-1.5 text-sm text-tinta-claro">
                    {conta
                        ? 'O novo nome aparece em todos os lançamentos que já apontam para esta conta.'
                        : 'Dê o nome pelo qual você reconhece essa conta no dia a dia.'}
                </p>

                <div className="mt-6">
                    <InputLabel htmlFor="nome" value="Nome da conta" />

                    <TextInput
                        id="nome"
                        className="mt-1.5 block w-full"
                        value={data.nome}
                        onChange={(evento) => setData('nome', evento.target.value)}
                        placeholder="Nubank, Carteira, Conta salário…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.nome} />
                </div>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {conta ? 'Salvar' : 'Criar conta'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
