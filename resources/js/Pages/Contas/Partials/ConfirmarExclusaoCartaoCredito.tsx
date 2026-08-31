import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import type { CartaoCredito } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function ConfirmarExclusaoCartaoCredito({
    cartaoCredito,
    aberto,
    aoFechar,
}: {
    cartaoCredito: CartaoCredito | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const [excluindo, setExcluindo] = useState(false);

    const excluir = () => {
        if (!cartaoCredito) {
            return;
        }

        router.delete(route('cartoes-credito.destroy', cartaoCredito.id), {
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
                    Excluir {cartaoCredito?.nome}?
                </h2>

                <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                    Os lançamentos que apontam para este cartão perdem o
                    registro de onde a compra foi feita.
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
                        Excluir cartão de crédito
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
