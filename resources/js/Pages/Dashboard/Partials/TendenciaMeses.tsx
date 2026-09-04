import { formatarMoeda } from '@/lib/money';
import type { TendenciaMesItem } from '@/types';

const nomesMesesCurto = [
    'Jan',
    'Fev',
    'Mar',
    'Abr',
    'Mai',
    'Jun',
    'Jul',
    'Ago',
    'Set',
    'Out',
    'Nov',
    'Dez',
];

function nomeMesCurto(competencia: string): string {
    const [, mes] = competencia.split('-').map(Number);

    return nomesMesesCurto[mes - 1];
}

export default function TendenciaMeses({
    meses,
    competenciaSelecionada,
    aoSelecionarMes,
}: {
    meses: TendenciaMesItem[];
    competenciaSelecionada: string;
    aoSelecionarMes?: (competencia: string) => void;
}) {
    const maiorValor = Math.max(1, ...meses.flatMap((mes) => [mes.receita, mes.despesa]));

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="border-b border-tinta/10 px-6 py-4">
                <h2 className="font-display text-[17px] font-semibold text-tinta">
                    Tendência de 6 meses
                </h2>
                <p className="mt-1 text-xs text-tinta-claro">Renda x despesa, mês a mês</p>
            </div>

            <div className="flex items-end gap-2 px-6 py-5">
                {meses.map((mes) => {
                    const ativo = mes.competencia === competenciaSelecionada;

                    return (
                        <button
                            type="button"
                            key={mes.competencia}
                            onClick={() => aoSelecionarMes?.(mes.competencia)}
                            disabled={!aoSelecionarMes}
                            className={`flex flex-1 flex-col items-center gap-1.5 rounded-lg py-1.5 transition-colors ${
                                ativo ? 'bg-papel' : 'enabled:hover:bg-papel/60'
                            }`}
                        >
                            <div className="flex h-24 items-end gap-1">
                                <span
                                    className="block w-3 rounded-t bg-verde"
                                    style={{ height: `${(mes.receita / maiorValor) * 100}%` }}
                                    title={formatarMoeda(mes.receita)}
                                />
                                <span
                                    className="block w-3 rounded-t bg-vinho"
                                    style={{ height: `${(mes.despesa / maiorValor) * 100}%` }}
                                    title={formatarMoeda(mes.despesa)}
                                />
                            </div>
                            <span
                                className={`text-[11px] font-semibold ${
                                    ativo ? 'text-tinta' : 'text-tinta-claro'
                                }`}
                            >
                                {nomeMesCurto(mes.competencia)}
                            </span>
                        </button>
                    );
                })}
            </div>

            <div className="flex items-center gap-4 border-t border-tinta/10 px-6 py-3">
                <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                    <span className="h-2 w-2 rounded-full bg-verde" />
                    Renda
                </span>
                <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                    <span className="h-2 w-2 rounded-full bg-vinho" />
                    Despesa
                </span>
            </div>
        </div>
    );
}
