import { formatarMoeda, formatarPercentual } from '@/lib/money';
import type { ResumoPeriodo as ResumoPeriodoType, VariacaoDelta } from '@/types';

function Badge({
    delta,
    bomQuandoSobe,
}: {
    delta: VariacaoDelta | null;
    bomQuandoSobe: boolean;
}) {
    if (delta === null) {
        return null;
    }

    const sobe = delta.valor >= 0;
    const bom = bomQuandoSobe ? sobe : !sobe;
    const texto =
        delta.tipo === 'percentual'
            ? formatarPercentual(delta.valor)
            : formatarMoeda(Math.abs(delta.valor));

    return (
        <span
            className={`rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums ${
                bom ? 'bg-verde/10 text-verde-escuro' : 'bg-vinho/10 text-vinho-escuro'
            }`}
        >
            {sobe ? '↑ ' : '↓ '}
            {texto}
        </span>
    );
}

function rotuloRealizado(statusPeriodo: ResumoPeriodoType['statusPeriodo']): string {
    if (statusPeriodo === 'passado') {
        return 'Realizado no mês';
    }

    return 'Realizado até hoje';
}

export default function ResumoPeriodo({
    resumo,
    despesaRotulo,
}: {
    resumo: ResumoPeriodoType;
    despesaRotulo: string;
}) {
    const ehFuturo = resumo.statusPeriodo === 'futuro';

    return (
        <section className="flex flex-col overflow-hidden rounded-xl border border-tinta/10 bg-white">
            <div className="p-6 sm:p-7">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                    Resumo do período
                </p>

                <div className="mt-4 flex flex-wrap items-start gap-x-11 gap-y-6">
                    <div>
                        <p className="text-sm font-medium text-tinta-claro">
                            {rotuloRealizado(resumo.statusPeriodo)}
                        </p>
                        {ehFuturo ? (
                            <p className="mt-1.5 text-sm text-tinta-claro">
                                Este mês ainda não começou.
                            </p>
                        ) : (
                            <>
                                <p className="mt-1.5 font-display text-3xl font-bold tabular-nums text-tinta">
                                    {formatarMoeda(resumo.saldo)}
                                </p>
                                <div className="mt-2.5">
                                    <Badge delta={resumo.saldoDelta} bomQuandoSobe />
                                </div>
                            </>
                        )}
                    </div>

                    <div className="hidden self-stretch border-l border-tinta/10 sm:block" />

                    <div className="flex flex-wrap gap-10">
                        <div>
                            <p className="text-sm font-medium text-tinta-claro">Renda</p>
                            <p className="mt-1.5 text-xl font-semibold tabular-nums text-verde">
                                {formatarMoeda(resumo.receita)}
                            </p>
                            <div className="mt-2.5">
                                <Badge delta={resumo.receitaDelta} bomQuandoSobe />
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
                                    delta={resumo.despesaDelta}
                                    bomQuandoSobe={false}
                                />
                            </div>
                        </div>

                        <div>
                            <p className="text-sm font-medium text-tinta-claro">Previsto</p>
                            <p
                                className={`mt-1.5 text-xl font-semibold tabular-nums ${
                                    resumo.resultado >= 0 ? 'text-verde' : 'text-vinho'
                                }`}
                            >
                                {formatarMoeda(resumo.resultado)}
                            </p>
                            <div className="mt-2.5">
                                <Badge delta={resumo.resultadoDelta} bomQuandoSobe />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
