import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Conta {
    id: string;
    nome: string;
}

export default function Index({ contas }: { contas: Conta[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        nome: '',
    });

    const { delete: destroy } = useForm();

    function criar(evento: FormEvent) {
        evento.preventDefault();
        post(route('contas.store'), { onSuccess: () => reset('nome') });
    }

    return (
        <AuthenticatedLayout
            header={
                <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                    Contas
                </h1>
            }
        >
            <Head title="Contas" />

            <div className="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
                <form
                    onSubmit={criar}
                    className="rounded-xl border border-tinta/10 bg-white p-6"
                >
                    <InputLabel htmlFor="nome" value="Nova conta" />

                    <div className="mt-2 flex gap-3">
                        <TextInput
                            id="nome"
                            value={data.nome}
                            onChange={(evento) =>
                                setData('nome', evento.target.value)
                            }
                            className="block w-full"
                            autoComplete="off"
                        />

                        <PrimaryButton disabled={processing}>
                            Adicionar
                        </PrimaryButton>
                    </div>

                    <InputError message={errors.nome} className="mt-2" />
                </form>

                <div className="rounded-xl border border-tinta/10 bg-white">
                    {contas.length === 0 ? (
                        <p className="p-6 text-tinta/70">
                            Nenhuma conta cadastrada ainda.
                        </p>
                    ) : (
                        <ul className="divide-y divide-tinta/10">
                            {contas.map((conta) => (
                                <li
                                    key={conta.id}
                                    className="flex items-center justify-between p-4"
                                >
                                    <span className="text-tinta">
                                        {conta.nome}
                                    </span>

                                    <DangerButton
                                        onClick={() =>
                                            destroy(
                                                route(
                                                    'contas.destroy',
                                                    conta.id,
                                                ),
                                            )
                                        }
                                    >
                                        Excluir
                                    </DangerButton>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
