import { formatarMoeda } from '@/lib/money';
import type { PendenciaDespesaItem } from '@/types';

function formatarData(data: string): string {
    const [ano, mes, dia] = data.split('-');

    return `${dia}/${mes}`;
}

export default function Pendencias({
    pendencias,
}: {
    pendencias: PendenciaDespesaItem[];
}) {
    const total = pendencias.reduce((soma, item) => soma + item.valor, 0);

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="border-b border-tinta/10 px-6 py-4">
                <div className="flex items-baseline justify-between">
                    <div>
                        <h2 className="font-display text-[17px] font-semibold text-tinta">
                            Pendências do período
                        </h2>
                        <p className="mt-1 text-xs text-tinta-claro">
                            Previsto — ainda não entrou nos valores realizados acima
                        </p>
                    </div>
                    <span className="rounded-full bg-papel-sombra px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                        Previsto
                    </span>
                </div>
            </div>

            <div className="px-6 py-4">
                <div className="flex items-baseline justify-between">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-vinho">
                        Despesas a pagar
                    </p>
                    <span className="text-sm font-semibold tabular-nums text-vinho">
                        {formatarMoeda(total)}
                    </span>
                </div>

                {pendencias.length === 0 ? (
                    <p className="mt-3 text-sm text-tinta-claro">
                        Nenhuma despesa pendente neste período.
                    </p>
                ) : (
                    <div className="mt-2.5 flex flex-col">
                        {pendencias.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-center gap-3.5 border-t border-tinta/[0.08] py-2.5"
                            >
                                <span
                                    aria-hidden="true"
                                    className="h-7 w-[3px] shrink-0 rounded-full bg-vinho"
                                />
                                <span className="flex-1 truncate text-sm font-medium text-tinta">
                                    {item.descricao}
                                </span>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                                        item.contexto === 'conjunta'
                                            ? 'bg-ouro/20 text-ouro'
                                            : 'bg-tinta/[0.07] text-tinta-claro'
                                    }`}
                                >
                                    {item.contexto === 'conjunta' ? 'Conjunta' : 'Individual'}
                                </span>
                                <span className="w-24 shrink-0 text-right text-xs text-tinta-claro">
                                    Vence {formatarData(item.vencimento)}
                                </span>
                                <span className="w-24 shrink-0 text-right text-sm font-semibold tabular-nums text-tinta">
                                    {formatarMoeda(item.valor)}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
