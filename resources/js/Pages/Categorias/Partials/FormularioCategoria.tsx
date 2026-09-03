import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import type { IconeCategoria } from '@/lib/icones-categoria';
import type { CategoriaDespesa, CategoriaRenda } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import SeletorIcone from './SeletorIcone';

type Categoria = CategoriaRenda | CategoriaDespesa;
type TipoCategoria = 'renda' | 'despesa';

const rotaPorTipo: Record<TipoCategoria, string> = {
    renda: 'categorias-renda',
    despesa: 'categorias-despesa',
};

const corPadrao = '#2F6F5E';

export default function FormularioCategoria({
    tipo,
    categoria,
    aberto,
    aoFechar,
}: {
    tipo: TipoCategoria;
    categoria: Categoria | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const { data, setData, post, put, processing, errors, clearErrors } =
        useForm({
            nome: categoria?.nome ?? '',
            cor: categoria?.cor ?? corPadrao,
            icone: categoria?.icone ?? 'home',
        });

    const rota = rotaPorTipo[tipo];

    const submit: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = { preserveScroll: true, onSuccess: aoFechar };

        if (categoria) {
            put(route(`${rota}.update`, categoria.id), opcoes);
        } else {
            post(route(`${rota}.store`), opcoes);
        }
    };

    const fechar = () => {
        clearErrors();
        aoFechar();
    };

    const rotulo = tipo === 'renda' ? 'de renda' : 'de despesa';

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <form onSubmit={submit} className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    {categoria
                        ? `Editar categoria ${rotulo}`
                        : `Nova categoria ${rotulo}`}
                </h2>

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
                        placeholder="Moradia, Salário, Lazer…"
                        autoComplete="off"
                        maxLength={255}
                        isFocused
                    />

                    <InputError className="mt-2" message={errors.nome} />
                </div>

                <div className="mt-6">
                    <InputLabel htmlFor="cor" value="Cor" />

                    <div className="mt-1.5 flex items-center gap-3">
                        <TextInput
                            id="cor"
                            type="color"
                            className="h-11 w-14 cursor-pointer p-1"
                            value={data.cor}
                            onChange={(evento) =>
                                setData('cor', evento.target.value)
                            }
                        />

                        <span className="font-mono text-sm uppercase text-tinta-claro">
                            {data.cor}
                        </span>
                    </div>

                    <InputError className="mt-2" message={errors.cor} />
                </div>

                <div className="mt-6">
                    <InputLabel value="Ícone" />

                    <SeletorIcone
                        valor={data.icone}
                        aoSelecionar={(icone: IconeCategoria) =>
                            setData('icone', icone)
                        }
                    />

                    <InputError className="mt-2" message={errors.icone} />
                </div>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton disabled={processing}>
                        {categoria ? 'Salvar' : 'Criar categoria'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
