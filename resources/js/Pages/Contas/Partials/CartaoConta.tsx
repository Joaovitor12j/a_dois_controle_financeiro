import type { Conta } from '@/types';

const formatadorDeMes = new Intl.DateTimeFormat('pt-BR', {
    month: 'long',
    year: 'numeric',
});

export default function CartaoConta({
    conta,
    cor,
    aoRenomear,
    aoExcluir,
}: {
    conta: Conta;
    cor: string;
    aoRenomear: () => void;
    aoExcluir: () => void;
}) {
    const inicial = conta.nome.trim().charAt(0).toUpperCase();

    return (
        <article className="flex flex-col rounded-xl border border-tinta/10 bg-white transition duration-200 ease-out hover:-translate-y-0.5 hover:border-tinta/20 hover:shadow-lg hover:shadow-tinta/5 motion-reduce:transform-none motion-reduce:transition-none">
            <div className="flex flex-1 items-start gap-4 p-5">
                <span
                    aria-hidden="true"
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold text-papel"
                    style={{ backgroundColor: cor }}
                >
                    {inicial}
                </span>

                <div className="min-w-0 flex-1">
                    <h2 className="truncate font-display text-xl font-semibold text-tinta">
                        {conta.nome}
                    </h2>

                    <p className="mt-1 text-sm text-tinta-claro">
                        aberta em {formatadorDeMes.format(new Date(conta.created_at))}
                    </p>
                </div>
            </div>

            <div className="flex gap-1 border-t border-tinta/10 px-3 py-2">
                <button
                    type="button"
                    onClick={aoRenomear}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
                >
                    Renomear
                </button>

                <button
                    type="button"
                    onClick={aoExcluir}
                    className="rounded-lg px-3 py-1.5 text-sm font-medium text-vinho transition duration-150 ease-in-out hover:bg-vinho/5 focus:outline-none focus:ring-2 focus:ring-vinho"
                >
                    Excluir
                </button>
            </div>
        </article>
    );
}
