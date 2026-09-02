import { formatarMoeda } from '@/lib/money';
import type { AlertaItem } from '@/types';

export default function Alertas({ alertas }: { alertas: AlertaItem[] }) {
    return (
        <div className="flex h-full flex-col rounded-xl border border-tinta/10 bg-white">
            <div className="flex items-baseline justify-between border-b border-tinta/10 px-6 py-4">
                <h2 className="font-display text-[17px] font-semibold text-tinta">
                    Alertas
                </h2>
                <span className="rounded-full bg-vinho/10 px-2 py-0.5 text-[11px] font-semibold text-vinho-escuro">
                    {alertas.length}
                </span>
            </div>

            {alertas.length === 0 ? (
                <p className="px-6 py-6 text-sm text-tinta-claro">
                    Nenhum alerta neste período.
                </p>
            ) : (
                <div className="flex flex-col">
                    {alertas.map((alerta, i) => (
                        <div
                            key={i}
                            className="flex gap-3 border-b border-tinta/[0.08] px-6 py-3.5 last:border-0"
                        >
                            <span
                                aria-hidden="true"
                                className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${
                                    alerta.nivel === 'vinho' ? 'bg-vinho' : 'bg-ouro'
                                }`}
                            />
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium leading-tight text-tinta">
                                    {alerta.titulo}
                                </p>
                                <p className="mt-0.5 text-xs leading-tight text-tinta-claro">
                                    {alerta.detalhe}
                                </p>
                            </div>
                            <span className="whitespace-nowrap text-sm font-semibold tabular-nums text-tinta">
                                {formatarMoeda(alerta.valor)}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
