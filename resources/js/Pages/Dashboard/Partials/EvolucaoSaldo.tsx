import { formatarMoeda } from '@/lib/money';
import type { EventoDia, PontoSerieSaldo, StatusPeriodo } from '@/types';
import { useState } from 'react';

const LARGURA = 690;
const ALTURA = 216;
const MARGEM_ESQUERDA = 64;
const TOPO = 16;
const BASE = 176;

function escala(pontos: PontoSerieSaldo[]) {
    const dias = pontos.map((p) => p.dia);
    const valores = pontos.map((p) => p.valor);

    const diaMin = Math.min(...dias);
    const diaMax = Math.max(...dias);
    const valorMin = Math.min(0, ...valores);
    const valorMax = Math.max(0, ...valores);
    const amplitude = valorMax - valorMin || 1;

    const x = (dia: number) =>
        MARGEM_ESQUERDA +
        ((dia - diaMin) / (diaMax - diaMin || 1)) * (LARGURA - MARGEM_ESQUERDA - 10);
    const y = (valor: number) =>
        BASE - ((valor - valorMin) / amplitude) * (BASE - TOPO);

    return { x, y, diaMin, diaMax, valorMin, valorMax, amplitude };
}

function caminho(pontos: PontoSerieSaldo[], x: (d: number) => number, y: (v: number) => number) {
    return pontos
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${x(p.dia).toFixed(1)} ${y(p.valor).toFixed(1)}`)
        .join(' ');
}

function ticksYSemSobreposicao(valorMax: number, valorMin: number): number[] {
    const amplitude = valorMax - valorMin || 1;
    const candidatos = [valorMax, (valorMax + valorMin) / 2, valorMin];

    return candidatos.filter((valor, indice) => {
        if (indice === 0 || indice === candidatos.length - 1) {
            return true;
        }

        const distanciaDoMax = Math.abs(valorMax - valor) / amplitude;
        const distanciaDoMin = Math.abs(valor - valorMin) / amplitude;

        return distanciaDoMax > 0.15 && distanciaDoMin > 0.15;
    });
}

export default function EvolucaoSaldo({
    serie,
    eventosPorDia,
    saldoAtual,
    competencia,
    statusPeriodo,
    temDespesaParcelada,
}: {
    serie: PontoSerieSaldo[];
    eventosPorDia: Record<number, EventoDia[]>;
    saldoAtual: number;
    competencia: string;
    statusPeriodo: StatusPeriodo;
    temDespesaParcelada: boolean;
}) {
    const [diaHover, setDiaHover] = useState<number | null>(null);

    if (serie.length === 0) {
        return null;
    }

    const realizado = serie.filter((p) => p.tipo === 'realizado');
    const projetado = serie.filter((p) => p.tipo === 'projetado');
    const { x, y, diaMin, diaMax, valorMin, valorMax } = escala(serie);

    const linhaRealizada = realizado.length > 0 ? caminho(realizado, x, y) : '';
    const linhaProjetada = projetado.length > 0 ? caminho(projetado, x, y) : '';
    const areaRealizada =
        realizado.length > 0
            ? `${linhaRealizada} L${x(realizado[realizado.length - 1].dia).toFixed(1)} ${BASE} L${x(realizado[0].dia).toFixed(1)} ${BASE} Z`
            : '';

    const [ano, mes] = competencia.split('-');
    const ultimoDia = new Date(Number(ano), Number(mes), 0).getDate();
    const hoje = new Date().getDate();

    const ticksY = ticksYSemSobreposicao(valorMax, valorMin);
    const ticksX = [1, Math.round(ultimoDia / 2), ultimoDia].filter(
        (dia, i, lista) => lista.indexOf(dia) === i,
    );

    const ultimoPontoProjetado = projetado[projetado.length - 1] ?? null;
    const eventosDoDia = diaHover !== null ? (eventosPorDia[diaHover] ?? []) : [];
    const larguraDia = (LARGURA - MARGEM_ESQUERDA - 10) / ((diaMax - diaMin) || 1);

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-6 pt-5">
                <div>
                    <div className="flex items-baseline gap-3">
                        <h2 className="font-display text-[17px] font-semibold text-tinta">
                            Evolução do saldo
                        </h2>
                        <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                            <span className="h-0.5 w-4 rounded bg-verde" />
                            Realizado
                        </span>
                        <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                            <span className="h-0.5 w-4 rounded border-t border-dashed border-tinta-claro" />
                            Previsto
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-tinta-claro">
                        01/{mes}/{ano} → {ultimoDia}/{mes}/{ano}
                    </p>
                </div>
                <span className="text-xl font-semibold tabular-nums text-tinta">
                    {formatarMoeda(saldoAtual)}
                </span>
            </div>

            <div className="relative px-4 pb-5 pt-2">
                <svg
                    viewBox={`0 0 ${LARGURA} ${ALTURA}`}
                    className="block h-[216px] w-full"
                >
                    {ticksY.map((valor, i) => (
                        <g key={i}>
                            <line
                                x1={MARGEM_ESQUERDA}
                                y1={y(valor)}
                                x2={LARGURA}
                                y2={y(valor)}
                                stroke="rgba(20,32,46,0.08)"
                            />
                            <text
                                x={MARGEM_ESQUERDA - 8}
                                y={y(valor) + 4}
                                fontSize={11}
                                fill="#3A4B5F"
                                textAnchor="end"
                            >
                                {formatarMoeda(valor)}
                            </text>
                        </g>
                    ))}

                    {statusPeriodo === 'atual' && hoje >= diaMin && hoje <= diaMax && (
                        <line
                            x1={x(hoje)}
                            y1={TOPO}
                            x2={x(hoje)}
                            y2={BASE}
                            stroke="#14202E"
                            strokeOpacity={0.25}
                            strokeWidth={1}
                            strokeDasharray="3 3"
                        />
                    )}

                    {areaRealizada && (
                        <path d={areaRealizada} fill="rgba(47,111,94,0.12)" />
                    )}

                    {linhaRealizada && (
                        <path
                            d={linhaRealizada}
                            fill="none"
                            stroke="#2F6F5E"
                            strokeWidth={2.5}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    )}

                    {linhaProjetada && (
                        <path
                            d={linhaProjetada}
                            fill="none"
                            stroke="#3A4B5F"
                            strokeOpacity={0.5}
                            strokeWidth={2}
                            strokeDasharray="5 5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    )}

                    <circle
                        cx={x(diaMax)}
                        cy={y(serie[serie.length - 1].valor)}
                        r={4}
                        fill="#2F6F5E"
                        stroke="#ffffff"
                        strokeWidth={2}
                    />

                    {ultimoPontoProjetado && (
                        <text
                            x={Math.min(x(ultimoPontoProjetado.dia) + 6, LARGURA - 4)}
                            y={y(ultimoPontoProjetado.valor) - 6}
                            fontSize={11}
                            fontWeight={600}
                            fill="#3A4B5F"
                            textAnchor={x(ultimoPontoProjetado.dia) > LARGURA - 60 ? 'end' : 'start'}
                        >
                            {formatarMoeda(ultimoPontoProjetado.valor)}
                        </text>
                    )}

                    {ticksX.map((dia) => (
                        <text
                            key={dia}
                            x={x(dia)}
                            y={196}
                            fontSize={11}
                            fill="#3A4B5F"
                            textAnchor={
                                dia === diaMax ? 'end' : dia === diaMin ? 'start' : 'middle'
                            }
                        >
                            {dia}
                        </text>
                    ))}

                    {Array.from({ length: diaMax - diaMin + 1 }, (_, i) => diaMin + i).map(
                        (dia) => (
                            <rect
                                key={dia}
                                x={x(dia) - larguraDia / 2}
                                y={TOPO}
                                width={larguraDia}
                                height={BASE - TOPO}
                                fill="transparent"
                                onMouseEnter={() => setDiaHover(dia)}
                                onMouseLeave={() =>
                                    setDiaHover((atual) => (atual === dia ? null : atual))
                                }
                            />
                        ),
                    )}

                    {diaHover !== null && (
                        <line
                            x1={x(diaHover)}
                            y1={TOPO}
                            x2={x(diaHover)}
                            y2={BASE}
                            stroke="#14202E"
                            strokeOpacity={0.15}
                            strokeWidth={1}
                        />
                    )}
                </svg>

                {diaHover !== null && eventosDoDia.length > 0 && (
                    <div
                        className="pointer-events-none absolute z-10 w-56 rounded-lg border border-tinta/10 bg-white p-3 shadow-lg"
                        style={{
                            left: `${Math.min(Math.max((x(diaHover) / LARGURA) * 100, 12), 78)}%`,
                            top: 8,
                        }}
                    >
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                            Dia {diaHover}
                        </p>
                        <div className="mt-1.5 flex flex-col gap-1">
                            {eventosDoDia.map((evento, i) => (
                                <div key={i} className="flex items-center justify-between gap-2">
                                    <span className="truncate text-xs text-tinta">
                                        {evento.descricao}
                                    </span>
                                    <span
                                        className={`shrink-0 text-xs font-semibold tabular-nums ${
                                            evento.valor < 0 ? 'text-vinho' : 'text-verde'
                                        }`}
                                    >
                                        {formatarMoeda(evento.valor)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {temDespesaParcelada && (
                    <p className="mt-2 text-xs text-tinta-claro">
                        Despesa parcelada entra nos totais do período, mas não aparece nesta
                        evolução nem em pendências — ela não tem data de vencimento própria.
                    </p>
                )}
            </div>
        </div>
    );
}
