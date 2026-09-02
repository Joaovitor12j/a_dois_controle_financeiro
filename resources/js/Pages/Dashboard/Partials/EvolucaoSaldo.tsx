import { formatarMoeda } from '@/lib/money';
import type { PontoSerieSaldo } from '@/types';

const LARGURA = 690;
const ALTURA = 216;
const MARGEM_ESQUERDA = 56;
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

    return { x, y, diaMin, diaMax, valorMin, valorMax };
}

function caminho(pontos: PontoSerieSaldo[], x: (d: number) => number, y: (v: number) => number) {
    return pontos
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${x(p.dia).toFixed(1)} ${y(p.valor).toFixed(1)}`)
        .join(' ');
}

export default function EvolucaoSaldo({
    serie,
    saldoAtual,
    competencia,
}: {
    serie: PontoSerieSaldo[];
    saldoAtual: number;
    competencia: string;
}) {
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

    const ticksY = [valorMax, (valorMax + valorMin) / 2, valorMin];
    const ticksX = [1, Math.round(ultimoDia / 2), ultimoDia].filter(
        (dia, i, lista) => lista.indexOf(dia) === i,
    );

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="flex items-baseline justify-between px-6 pt-5">
                <div>
                    <h2 className="font-display text-[17px] font-semibold text-tinta">
                        Evolução do saldo
                    </h2>
                    <p className="mt-1 text-xs text-tinta-claro">
                        01/{mes}/{ano} → {ultimoDia}/{mes}/{ano}
                    </p>
                </div>
                <span className="text-xl font-semibold tabular-nums text-tinta">
                    {formatarMoeda(saldoAtual)}
                </span>
            </div>

            <div className="px-4 pb-5 pt-2">
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
                                x={0}
                                y={y(valor) + 4}
                                fontSize={11}
                                fill="#3A4B5F"
                            >
                                {formatarMoeda(valor)}
                            </text>
                        </g>
                    ))}

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
                </svg>

                <div className="mt-1 flex items-center gap-5 pl-14">
                    <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                        <span className="h-0.5 w-4 rounded bg-verde" />
                        Realizado
                    </span>
                    <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                        <span className="h-0.5 w-4 rounded border-t border-dashed border-tinta-claro" />
                        Projetado com pendências
                    </span>
                </div>
            </div>
        </div>
    );
}
