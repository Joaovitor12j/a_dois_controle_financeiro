import type { ModoVisualizacao } from '@/types';

const nomesMeses = [
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro',
];

export function competenciaAdjacente(
    competencia: string,
    deslocamento: number,
): string {
    const [ano, mes] = competencia.split('-').map(Number);
    const data = new Date(ano, mes - 1 + deslocamento, 1);

    return `${data.getFullYear()}-${String(data.getMonth() + 1).padStart(2, '0')}`;
}

export function formatarCompetenciaExtenso(competencia: string): string {
    const [ano, mes] = competencia.split('-').map(Number);

    return `${nomesMeses[mes - 1]} de ${ano}`;
}

export default function SeletorVisualizacao({
    modo,
    competencia,
    aoMudarModo,
    aoMudarCompetencia,
}: {
    modo: ModoVisualizacao;
    competencia: string;
    aoMudarModo: (modo: ModoVisualizacao) => void;
    aoMudarCompetencia: (competencia: string) => void;
}) {
    return (
        <div className="border-b border-tinta/10 bg-white">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div className="flex items-center gap-4">
                    <span className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                        Visualizando
                    </span>

                    <div className="flex gap-1 rounded-xl bg-papel-sombra p-1">
                        <button
                            type="button"
                            onClick={() => aoMudarModo('individual')}
                            className={`h-8 rounded-lg px-4 text-[13px] font-semibold transition-colors ${
                                modo === 'individual'
                                    ? 'bg-white text-tinta shadow-sm'
                                    : 'text-tinta-claro'
                            }`}
                        >
                            Individual
                        </button>
                        <button
                            type="button"
                            onClick={() => aoMudarModo('casal')}
                            className={`h-8 rounded-lg px-4 text-[13px] font-semibold transition-colors ${
                                modo === 'casal'
                                    ? 'bg-white text-tinta shadow-sm'
                                    : 'text-tinta-claro'
                            }`}
                        >
                            Casal
                        </button>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        aria-label="Período anterior"
                        onClick={() =>
                            aoMudarCompetencia(competenciaAdjacente(competencia, -1))
                        }
                        className="flex h-10 w-10 items-center justify-center rounded-xl border border-tinta/15 bg-white text-tinta-claro transition-colors hover:bg-papel"
                    >
                        ‹
                    </button>

                    <div className="flex h-10 items-center rounded-xl border border-tinta/15 bg-white px-4 text-sm font-medium text-tinta">
                        {formatarCompetenciaExtenso(competencia)}
                    </div>

                    <button
                        type="button"
                        aria-label="Próximo período"
                        onClick={() =>
                            aoMudarCompetencia(competenciaAdjacente(competencia, 1))
                        }
                        className="flex h-10 w-10 items-center justify-center rounded-xl border border-tinta/15 bg-white text-tinta-claro transition-colors hover:bg-papel"
                    >
                        ›
                    </button>
                </div>
            </div>
        </div>
    );
}
