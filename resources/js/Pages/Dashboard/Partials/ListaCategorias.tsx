import { formatarMoeda, formatarPercentual } from '@/lib/money';
import type { CategoriaResumoItem } from '@/types';

export default function ListaCategorias({
    titulo,
    itens,
    total,
}: {
    titulo: string;
    itens: CategoriaResumoItem[];
    total: number;
}) {
    const maiorValor = Math.max(1, ...itens.map((item) => item.valor));

    return (
        <div className="flex h-full flex-col rounded-xl border border-tinta/10 bg-white">
            <div className="flex items-baseline justify-between border-b border-tinta/10 px-6 py-4">
                <h2 className="font-display text-[17px] font-semibold text-tinta">
                    {titulo}
                </h2>
                <span className="text-sm font-semibold tabular-nums text-tinta">
                    {formatarMoeda(total)}
                </span>
            </div>

            {itens.length === 0 ? (
                <p className="px-6 py-6 text-sm text-tinta-claro">
                    Nada lançado neste período ainda.
                </p>
            ) : (
                <div className="flex flex-col gap-4 px-6 py-5">
                    {itens.map((item) => (
                        <div key={item.nome} className="flex items-center gap-3">
                            <span
                                aria-hidden="true"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                                style={{ backgroundColor: `${item.cor}24` }}
                            >
                                <span
                                    className="h-2.5 w-2.5 rounded-full"
                                    style={{ backgroundColor: item.cor }}
                                />
                            </span>

                            <div className="min-w-0 flex-1">
                                <div className="flex items-baseline justify-between gap-3">
                                    <span className="text-sm font-medium text-tinta">
                                        {item.nome}
                                    </span>
                                    <span className="text-sm font-semibold tabular-nums text-tinta">
                                        {formatarMoeda(item.valor)}
                                    </span>
                                </div>
                                <div className="mt-1.5 flex items-center gap-2.5">
                                    <span className="block h-1.5 flex-1 overflow-hidden rounded-full bg-tinta/[0.07]">
                                        <span
                                            className="block h-1.5 rounded-full"
                                            style={{
                                                backgroundColor: item.cor,
                                                width: `${(item.valor / maiorValor) * 100}%`,
                                            }}
                                        />
                                    </span>
                                    <span className="w-11 shrink-0 text-right text-xs tabular-nums text-tinta-claro">
                                        {total > 0
                                            ? formatarPercentual((item.valor / total) * 100)
                                            : '—'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
