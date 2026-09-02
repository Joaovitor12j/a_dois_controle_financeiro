import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import type { Despesa } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function ConfirmarDesfazerPagamento({
    despesa,
    aberto,
    aoFechar,
}: {
    despesa: Despesa | null;
    aberto: boolean;
    aoFechar: () => void;
}) {
    const [processando, setProcessando] = useState(false);

    const desfazer = () => {
        if (!despesa) {
            return;
        }

        router.patch(route('despesas.desfazer-pagamento', despesa.id), {}, {
            preserveScroll: true,
            onStart: () => setProcessando(true),
            onFinish: () => setProcessando(false),
            onSuccess: aoFechar,
        });
    };

    return (
        <Modal show={aberto} onClose={aoFechar} maxWidth="md">
            <div className="p-6 sm:p-8">
                <h2 className="font-display text-xl font-semibold text-tinta">
                    Desfazer pagamento de {despesa?.descricao}?
                </h2>

                <p className="mt-3 text-sm leading-relaxed text-tinta-claro">
                    A despesa volta a ficar pendente.
                </p>

                <div className="mt-8 flex justify-end gap-3">
                    <SecondaryButton onClick={aoFechar} disabled={processando}>
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton
                        type="button"
                        onClick={desfazer}
                        disabled={processando}
                    >
                        Desfazer pagamento
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
