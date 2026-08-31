import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface ItemToast {
    id: number;
    tipo: 'success' | 'error';
    mensagem: string;
}

const DURACAO_MS = 5000;
const MENSAGEM_FALHA_GENERICA = 'Não foi possível concluir a ação. Tente novamente.';

let proximoId = 0;

export default function Toast() {
    const [itens, setItens] = useState<ItemToast[]>([]);

    useEffect(() => {
        const remover = (id: number) => {
            setItens((atual) => atual.filter((item) => item.id !== id));
        };

        const adicionar = (tipo: ItemToast['tipo'], mensagem: string) => {
            const id = proximoId++;
            setItens((atual) => [...atual, { id, tipo, mensagem }]);
            setTimeout(() => remover(id), DURACAO_MS);
        };

        const cancelarFlash = router.on('flash', (evento) => {
            const toast = evento.detail.flash.toast;

            if (toast) {
                adicionar(toast.type, toast.message);
            }
        });

        const cancelarInvalid = router.on('invalid', (evento) => {
            adicionar('error', MENSAGEM_FALHA_GENERICA);

            if (import.meta.env.PROD) {
                evento.preventDefault();
            }
        });

        const cancelarException = router.on('exception', (evento) => {
            adicionar('error', MENSAGEM_FALHA_GENERICA);
            evento.preventDefault();
        });

        return () => {
            cancelarFlash();
            cancelarInvalid();
            cancelarException();
        };
    }, []);

    if (itens.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4">
            {itens.map((item) => (
                <div
                    key={item.id}
                    role="alert"
                    className={
                        'pointer-events-auto w-full max-w-md rounded-lg px-4 py-3 text-sm font-medium text-papel shadow-lg ' +
                        (item.tipo === 'success' ? 'bg-verde' : 'bg-vinho')
                    }
                >
                    {item.mensagem}
                </div>
            ))}
        </div>
    );
}
