import { formatarMoeda, formatarPercentual } from '@/lib/money';
import type { ResumoPeriodo as ResumoPeriodoType } from '@/types';

function Badge({
    delta,
    bomQuandoSobe,
}: {
    delta: number | null;
    bomQuandoSobe: boolean;
}) {
    if (delta === null) {
        return null;
    }

    const sobe = delta >= 0;
    const bom = bomQuandoSobe ? sobe : !sobe;

    return (
        <span
            className={`rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums ${
                bom ? 'bg-verde/10 text-verde-escuro' : 'bg-vinho/10 text-vinho-escuro'
            }`}
        >
            {sobe ? '↑ ' : '↓ '}
            {formatarPercentual(delta)}
        </span>
    );
}

export default function ResumoPeriodo({
    resumo,
    despesaRotulo,
    aoAbrirNovaDespesa,
    aoAbrirNovaRenda,
}: {
    resumo: ResumoPeriodoType;
    despesaRotulo: string;
    aoAbrirNovaDespesa: () => void;
    aoAbrirNovaRenda: () => void;
}) {
    return (
        <section className="flex flex-col overflow-hidden rounded-xl border border-tinta/10 bg-white lg:flex-row">
            <div className="flex-1 p-6 sm:p-7">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                    Resumo do período
                </p>

                <div className="mt-4 flex flex-wrap items-start gap-x-11 gap-y-6">
                    <div>
                        <p className="text-sm font-medium text-tinta-claro">
                            Saldo do período
                        </p>
                        <p className="mt-1.5 font-display text-3xl font-bold tabular-nums text-tinta">
                            {formatarMoeda(resumo.saldo)}
                        </p>
                        <div className="mt-2.5">
                            <Badge delta={resumo.saldoDeltaPct} bomQuandoSobe />
                        </div>
                    </div>

                    <div className="hidden self-stretch border-l border-tinta/10 sm:block" />

                    <div className="flex flex-wrap gap-10">
                        <div>
                            <p className="text-sm font-medium text-tinta-claro">
                                Receita
                            </p>
                            <p className="mt-1.5 text-xl font-semibold tabular-nums text-verde">
                                {formatarMoeda(resumo.receita)}
                            </p>
                            <div className="mt-2.5">
                                <Badge delta={resumo.receitaDeltaPct} bomQuandoSobe />
                            </div>
                        </div>

                        <div>
                            <p className="text-sm font-medium text-tinta-claro">
                                {despesaRotulo}
                            </p>
                            <p className="mt-1.5 text-xl font-semibold tabular-nums text-vinho">
                                {formatarMoeda(resumo.despesa)}
                            </p>
                            <div className="mt-2.5">
                                <Badge
                                    delta={resumo.despesaDeltaPct}
                                    bomQuandoSobe={false}
                                />
                            </div>
                        </div>

                        <div>
                            <p className="text-sm font-medium text-tinta-claro">
                                Resultado
                            </p>
                            <p
                                className={`mt-1.5 text-xl font-semibold tabular-nums ${
                                    resumo.resultado >= 0 ? 'text-verde' : 'text-vinho'
                                }`}
                            >
                                {formatarMoeda(resumo.resultado)}
                            </p>
                            <div className="mt-2.5">
                                <Badge delta={resumo.resultadoDeltaPct} bomQuandoSobe />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="w-full shrink-0 border-t border-tinta/10 bg-papel/60 p-6 lg:w-72 lg:border-l lg:border-t-0">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                    Ação rápida
                </p>
                <p className="mt-2 text-sm text-tinta-claro">
                    Lance sem sair da visão geral.
                </p>

                <div className="mt-4 flex flex-col gap-2.5">
                    <button
                        type="button"
                        onClick={aoAbrirNovaDespesa}
                        className="flex h-11 items-center justify-center gap-2 rounded-xl bg-tinta px-5 text-sm font-semibold text-papel transition-colors hover:bg-tinta-claro"
                    >
                        Nova despesa
                    </button>
                    <button
                        type="button"
                        onClick={aoAbrirNovaRenda}
                        className="flex h-11 items-center justify-center gap-2 rounded-xl bg-verde-escuro px-5 text-sm font-semibold text-papel transition-colors hover:bg-verde"
                    >
                        Nova renda
                    </button>
                </div>
            </div>
        </section>
    );
}
