import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import type { CategoriaDespesa, CategoriaRenda } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

type Categoria = CategoriaRenda | CategoriaDespesa;
type TipoCategoria = 'renda' | 'despesa';

const rotaPorTipo: Record<TipoCategoria, string> = {
    renda: 'categorias-renda',
    despesa: 'categorias-despesa',
};

export default function ConfirmarExclusaoCategoria({
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
    const [excluindo, setExcluindo] = useState(false);
    const [erro, setErro] = useState<string | null>(null);

    const excluir = () => {
        if (!categoria) {
            return;
        }

        router.delete(route(`${rotaPorTipo[tipo]}.destroy`, categoria.id), {
            preserveScroll: true,
            onStart: () => {
                setErro(null);
                setExcluindo(true);
            },
            onFinish: () => setExcluindo(false),
            onSuccess: fechar,
            onError: (errors) => setErro(errors.categoria ?? null),
        });
    };

    const fechar = () => {
        setErro(null);
        aoFechar();
    };

    return (
        <Modal show={aberto} onClose={fechar} maxWidth="md">
            <div className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    Excluir {categoria?.nome}?
                </h2>

                <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                    Essa ação não pode ser desfeita.
                </p>

                {erro && (
                    <div
                        role="alert"
                        className="mt-4 rounded-lg border border-vinho/30 bg-vinho/5 p-3 text-sm font-medium text-vinho"
                    >
                        {erro}
                    </div>
                )}

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={fechar} disabled={excluindo}>
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        type="button"
                        onClick={excluir}
                        disabled={excluindo}
                    >
                        Excluir categoria
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
