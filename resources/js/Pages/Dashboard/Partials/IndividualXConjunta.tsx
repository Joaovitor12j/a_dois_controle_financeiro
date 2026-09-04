import { formatarMoeda, formatarPercentual } from '@/lib/money';
import type { IndividualXConjunta as IndividualXConjuntaType } from '@/types';

export default function IndividualXConjunta({
    dados,
}: {
    dados: IndividualXConjuntaType;
}) {
    const total = dados.individual + dados.conjunta;

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="border-b border-tinta/10 px-6 py-4">
                <h2 className="font-display text-[17px] font-semibold text-tinta">
                    Individual x Conjunta
                </h2>
            </div>

            <div className="px-6 py-5">
                <div className="flex h-2 overflow-hidden rounded-full bg-tinta/[0.07]">
                    <span
                        className="block h-2 bg-tinta"
                        style={{ width: total > 0 ? `${(dados.individual / total) * 100}%` : '0%' }}
                    />
                    <span
                        className="block h-2 bg-ouro"
                        style={{ width: total > 0 ? `${(dados.conjunta / total) * 100}%` : '0%' }}
                    />
                </div>

                <div className="mt-2.5 flex items-center gap-2">
                    <span aria-hidden="true" className="h-2 w-2 rounded-full bg-tinta" />
                    <span className="text-sm text-tinta-claro">Individual</span>
                    <span className="text-sm font-semibold tabular-nums text-tinta">
                        {formatarMoeda(dados.individual)}
                    </span>
                    <span className="text-xs text-tinta-claro">
                        {total > 0 ? formatarPercentual((dados.individual / total) * 100) : '—'}
                    </span>
                </div>

                <div className="mt-2.5 flex items-center gap-2">
                    <span aria-hidden="true" className="h-2 w-2 rounded-full bg-ouro" />
                    <span className="text-sm text-tinta-claro">Conjunta</span>
                    <span className="text-sm font-semibold tabular-nums text-tinta">
                        {formatarMoeda(dados.conjunta)}
                    </span>
                    <span className="text-xs text-tinta-claro">
                        {total > 0 ? formatarPercentual((dados.conjunta / total) * 100) : '—'}
                    </span>
                </div>
            </div>
        </div>
    );
}
