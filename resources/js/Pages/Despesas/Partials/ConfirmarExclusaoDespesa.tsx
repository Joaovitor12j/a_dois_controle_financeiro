import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import type { Despesa } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function ConfirmarExclusaoDespesa({
    despesa,
    aberto,
    aoFechar,
}: {
    despesa: Despesa | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const [excluindo, setExcluindo] = useState(false);

    const excluir = () => {
        if (!despesa) {
            return;
        }

        router.delete(route('despesas.destroy', despesa.id), {
            preserveScroll: true,
            onStart: () => setExcluindo(true),
            onFinish: () => setExcluindo(false),
            onSuccess: aoFechar,
        });
    };

    return (
        <Modal show={aberto} onClose={aoFechar} maxWidth="md">
            <div className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    Excluir {despesa?.descricao}?
                </h2>

                <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                    Esta ação não pode ser desfeita.
                </p>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={aoFechar} disabled={excluindo}>
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        type="button"
                        onClick={excluir}
                        disabled={excluindo}
                    >
                        Excluir despesa
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
