import MarcarComoPagaDespesa from '@/Pages/Despesas/Partials/MarcarComoPagaDespesa';
import { formatarMoeda } from '@/lib/money';
import type { FormaPagamento, ModoVisualizacao, PendenciaItem } from '@/types';
import { CircleCheck } from 'lucide-react';
import { useState } from 'react';

function descreverPrazo(dias: number, tipo: PendenciaItem['tipo']): string {
    if (tipo === 'renda') {
        if (dias === 0) return 'Recebe hoje';
        if (dias === 1) return 'Recebe amanhã';
        if (dias > 1) return `Recebe em ${dias} dias`;

        const diasAtras = Math.abs(dias);

        return diasAtras === 1
            ? 'Deveria ter recebido há 1 dia'
            : `Deveria ter recebido há ${diasAtras} dias`;
    }

    if (dias === 0) return 'Vence hoje';
    if (dias === 1) return 'Vence amanhã';
    if (dias > 1) return `Vence em ${dias} dias`;

    const diasAtras = Math.abs(dias);

    return diasAtras === 1 ? 'Venceu há 1 dia' : `Venceu há ${diasAtras} dias`;
}

function corDoNivel(nivel: PendenciaItem['nivel']): string {
    return nivel === 'vencida' ? 'bg-vinho' : nivel === 'vence_em_breve' ? 'bg-ouro' : 'bg-tinta/20';
}

function TotalPorTipo({
    rotulo,
    valor,
    cor,
}: {
    rotulo: string;
    valor: number;
    cor: 'vinho' | 'verde';
}) {
    return (
        <div className="flex items-baseline justify-between">
            <p
                className={`text-[11px] font-semibold uppercase tracking-wider ${
                    cor === 'vinho' ? 'text-vinho' : 'text-verde'
                }`}
            >
                {rotulo}
            </p>
            <span
                className={`text-sm font-semibold tabular-nums ${
                    cor === 'vinho' ? 'text-vinho' : 'text-verde'
                }`}
            >
                {formatarMoeda(valor)}
            </span>
        </div>
    );
}

export default function Pendencias({
    pendencias,
    modo,
    competencia,
    formasPagamento,
    aoClicarItem,
}: {
    pendencias: PendenciaItem[];
    modo: ModoVisualizacao;
    competencia: string;
    formasPagamento: FormaPagamento[];
    aoClicarItem?: (item: PendenciaItem) => void;
}) {
    const [pagando, setPagando] = useState<PendenciaItem | null>(null);
    const [aberturas, setAberturas] = useState(0);

    const despesas = pendencias.filter((item) => item.tipo === 'despesa');
    const rendas = pendencias.filter((item) => item.tipo === 'renda');
    const totalDespesas = despesas.reduce((soma, item) => soma + item.valor, 0);
    const totalRendas = rendas.reduce((soma, item) => soma + item.valor, 0);
    const criticos = pendencias.filter((item) => item.nivel !== 'no_prazo').length;

    const abrirPagamento = (item: PendenciaItem) => {
        setPagando(item);
        setAberturas((atual) => atual + 1);
    };

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
                    <div className="flex items-center gap-2">
                        {criticos > 0 && (
                            <span className="rounded-full bg-vinho/10 px-2 py-0.5 text-[11px] font-semibold text-vinho-escuro">
                                {criticos} crítica{criticos > 1 ? 's' : ''}
                            </span>
                        )}
                        <span className="rounded-full bg-papel-sombra px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                            {pendencias.length}
                        </span>
                    </div>
                </div>
            </div>

            <div className="px-6 py-4">
                <div className="flex flex-col gap-2">
                    <TotalPorTipo rotulo="Despesas a pagar" valor={totalDespesas} cor="vinho" />
                    <TotalPorTipo rotulo="Renda a receber" valor={totalRendas} cor="verde" />
                </div>

                {pendencias.length === 0 ? (
                    <p className="mt-3 text-sm text-tinta-claro">
                        Nenhuma pendência neste período.
                    </p>
                ) : (
                    <div className="mt-2.5 flex flex-col">
                        {pendencias.map((item) => (
                            <div
                                key={`${item.tipo}-${item.id}`}
                                className="flex items-center gap-3.5 border-t border-tinta/[0.08] py-2.5"
                            >
                                <span
                                    aria-hidden="true"
                                    className={`h-7 w-[3px] shrink-0 rounded-full ${corDoNivel(item.nivel)}`}
                                />
                                <button
                                    type="button"
                                    onClick={() => aoClicarItem?.(item)}
                                    disabled={!aoClicarItem}
                                    className="min-w-0 flex-1 truncate text-left text-sm font-medium text-tinta enabled:hover:underline"
                                >
                                    {item.descricao}
                                </button>
                                {modo === 'individual' && item.contexto !== null && (
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                                            item.contexto === 'conjunta'
                                                ? 'bg-ouro/20 text-ouro'
                                                : 'bg-tinta/[0.07] text-tinta-claro'
                                        }`}
                                    >
                                        {item.contexto === 'conjunta' ? 'Conjunta' : 'Individual'}
                                    </span>
                                )}
                                <span className="w-36 shrink-0 text-right text-xs text-tinta-claro">
                                    {descreverPrazo(item.dias, item.tipo)}
                                </span>
                                <span
                                    className={`w-24 shrink-0 text-right text-sm font-semibold tabular-nums ${
                                        item.tipo === 'renda' ? 'text-verde' : 'text-tinta'
                                    }`}
                                >
                                    {formatarMoeda(item.valor)}
                                </span>
                                {item.tipo === 'despesa' && (
                                    <button
                                        type="button"
                                        aria-label={`Marcar ${item.descricao} como paga`}
                                        onClick={() => abrirPagamento(item)}
                                        className="shrink-0 text-tinta-claro transition-colors hover:text-verde-escuro"
                                    >
                                        <CircleCheck className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <MarcarComoPagaDespesa
                key={`pendencia-pagar-${aberturas}`}
                despesa={
                    pagando
                        ? {
                              id: pagando.id,
                              descricao: pagando.descricao,
                              tipo_lancamento: pagando.tipoLancamento ?? 'unica',
                              valor: pagando.valor,
                          }
                        : null
                }
                competencia={competencia}
                contexto={pagando?.contexto ?? 'individual'}
                formasPagamento={formasPagamento}
                aberto={pagando !== null}
                aoFechar={() => setPagando(null)}
            />
        </div>
    );
}
