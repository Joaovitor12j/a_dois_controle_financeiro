import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import type { Conta } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function ConfirmarExclusaoConta({
    conta,
    aberto,
    aoFechar,
}: {
    conta: Conta | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const [excluindo, setExcluindo] = useState(false);

    const excluir = () => {
        if (!conta) {
            return;
        }

        router.delete(route('contas.destroy', conta.id), {
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
                    Excluir {conta?.nome}?
                </h2>

                <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                    Formas de pagamento e cartões ligados a esta conta também
                    serão excluídos. Os lançamentos que apontam para eles perdem
                    o registro de onde o dinheiro saiu.
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
                        Excluir conta
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
