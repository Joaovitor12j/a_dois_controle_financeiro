import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { CategoriaDespesa, CategoriaRenda } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CartaoCategoria from './Partials/CartaoCategoria';
import ConfirmarExclusaoCategoria from './Partials/ConfirmarExclusaoCategoria';
import FormularioCategoria from './Partials/FormularioCategoria';

type Categoria = CategoriaRenda | CategoriaDespesa;
type Aba = 'renda' | 'despesa';

interface AlvoDeFormulario {
    aberto: boolean;
    categoria: Categoria | null;
}

interface AlvoDeExclusao {
    aberto: boolean;
    categoria: Categoria | null;
}

const formularioFechado: AlvoDeFormulario = { aberto: false, categoria: null };
const exclusaoFechada: AlvoDeExclusao = { aberto: false, categoria: null };

export default function Index({
    categoriasRenda,
    categoriasDespesa,
}: {
    categoriasRenda: CategoriaRenda[];
    categoriasDespesa: CategoriaDespesa[];
}) {
    const [aba, setAba] = useState<Aba>('renda');

    const [formulario, setFormulario] =
        useState<AlvoDeFormulario>(formularioFechado);
    const [aberturas, setAberturas] = useState(0);

    const [exclusao, setExclusao] = useState<AlvoDeExclusao>(exclusaoFechada);

    const abrirFormulario = (categoria: Categoria | null) => {
        setAberturas((quantas) => quantas + 1);
        setFormulario({ aberto: true, categoria });
    };

    const fecharFormulario = () =>
        setFormulario((atual) => ({ ...atual, aberto: false }));

    const fecharExclusao = () =>
        setExclusao((atual) => ({ ...atual, aberto: false }));

    const categorias = aba === 'renda' ? categoriasRenda : categoriasDespesa;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="font-display text-2xl font-bold leading-tight text-tinta">
                            Categorias
                        </h1>

                        <p className="mt-1 text-sm text-tinta-claro">
                            Categorias são compartilhadas entre os dois
                            usuários.
                        </p>
                    </div>

                    <PrimaryButton
                        type="button"
                        onClick={() => abrirFormulario(null)}
                        className="!rounded-xl !bg-verde-escuro hover:!bg-verde"
                    >
                        {aba === 'renda'
                            ? 'Nova categoria de renda'
                            : 'Nova categoria de despesa'}
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Categorias" />

            <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex w-fit gap-1 rounded-xl bg-papel-sombra p-1">
                    <button
                        type="button"
                        onClick={() => setAba('renda')}
                        className={`h-9 rounded-lg px-4 text-sm font-semibold transition-colors ${
                            aba === 'renda'
                                ? 'bg-white text-tinta shadow-sm'
                                : 'text-tinta-claro'
                        }`}
                    >
                        Rendas ({categoriasRenda.length})
                    </button>

                    <button
                        type="button"
                        onClick={() => setAba('despesa')}
                        className={`h-9 rounded-lg px-4 text-sm font-semibold transition-colors ${
                            aba === 'despesa'
                                ? 'bg-white text-tinta shadow-sm'
                                : 'text-tinta-claro'
                        }`}
                    >
                        Despesas ({categoriasDespesa.length})
                    </button>
                </div>

                <div className="mt-6">
                    {categorias.length === 0 ? (
                        <div className="flex flex-col items-center rounded-xl border border-dashed border-tinta/20 bg-white/50 px-6 py-16 text-center">
                            <h2 className="font-display text-xl font-semibold text-tinta">
                                Nenhuma categoria de{' '}
                                {aba === 'renda' ? 'renda' : 'despesa'} ainda
                            </h2>

                            <PrimaryButton
                                type="button"
                                onClick={() => abrirFormulario(null)}
                                className="mt-8 !rounded-xl !bg-verde-escuro hover:!bg-verde"
                            >
                                Criar categoria
                            </PrimaryButton>
                        </div>
                    ) : (
                        <div className="grid gap-3 sm:grid-cols-2">
                            {categorias.map((categoria) => (
                                <CartaoCategoria
                                    key={categoria.id}
                                    categoria={categoria}
                                    aoEditar={() => abrirFormulario(categoria)}
                                    aoExcluir={() =>
                                        setExclusao({
                                            aberto: true,
                                            categoria,
                                        })
                                    }
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <FormularioCategoria
                key={`${formulario.categoria?.id ?? 'nova'}-${aberturas}`}
                tipo={aba}
                categoria={formulario.categoria}
                aberto={formulario.aberto}
                aoFechar={fecharFormulario}
            />

            <ConfirmarExclusaoCategoria
                tipo={aba}
                categoria={exclusao.categoria}
                aberto={exclusao.aberto}
                aoFechar={fecharExclusao}
            />
        </AuthenticatedLayout>
    );
}
