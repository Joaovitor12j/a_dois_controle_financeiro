import { competenciaAdjacente, formatarCompetenciaExtenso } from '@/Pages/Dashboard/Partials/SeletorVisualizacao';
import type { ContextoDespesa } from '@/types';

export default function NavegacaoDespesas({
    competencia,
    contexto,
    aoMudarCompetencia,
    aoMudarContexto,
}: {
    competencia: string;
    contexto: ContextoDespesa;
    aoMudarCompetencia: (competencia: string) => void;
    aoMudarContexto: (contexto: ContextoDespesa) => void;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-4 pb-5">
            <div className="flex items-center gap-1.5">
                <button
                    type="button"
                    aria-label="Mês anterior"
                    onClick={() =>
                        aoMudarCompetencia(competenciaAdjacente(competencia, -1))
                    }
                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-tinta/10 bg-white text-tinta-claro transition-colors hover:border-tinta/25 hover:text-tinta"
                >
                    ‹
                </button>

                <span className="min-w-[196px] text-center font-display text-lg font-semibold text-tinta">
                    {formatarCompetenciaExtenso(competencia)}
                </span>

                <button
                    type="button"
                    aria-label="Próximo mês"
                    onClick={() =>
                        aoMudarCompetencia(competenciaAdjacente(competencia, 1))
                    }
                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-tinta/10 bg-white text-tinta-claro transition-colors hover:border-tinta/25 hover:text-tinta"
                >
                    ›
                </button>
            </div>

            <div className="inline-flex items-center gap-0.5 rounded-xl border border-tinta/10 bg-white p-[3px]">
                <button
                    type="button"
                    onClick={() => aoMudarContexto('individual')}
                    className={`rounded-lg px-4 py-1.5 text-[13.5px] font-medium transition-colors ${
                        contexto === 'individual'
                            ? 'bg-verde-escuro text-papel'
                            : 'text-tinta-claro hover:text-tinta'
                    }`}
                >
                    Individual
                </button>
                <button
                    type="button"
                    onClick={() => aoMudarContexto('conjunta')}
                    className={`rounded-lg px-4 py-1.5 text-[13.5px] font-medium transition-colors ${
                        contexto === 'conjunta'
                            ? 'bg-verde-escuro text-papel'
                            : 'text-tinta-claro hover:text-tinta'
                    }`}
                >
                    Conjunta
                </button>
            </div>
        </div>
    );
}
