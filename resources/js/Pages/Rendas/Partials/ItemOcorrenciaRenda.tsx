import type { OcorrenciaRenda, Renda } from '@/types';

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const MES_ABREV = [
    'jan',
    'fev',
    'mar',
    'abr',
    'mai',
    'jun',
    'jul',
    'ago',
    'set',
    'out',
    'nov',
    'dez',
];

function formatarDataCurta(data: string): string {
    const [, mes, dia] = data.slice(0, 10).split('-');

    return `${dia}/${mes}`;
}

function mesAno(data: string): string {
    const [ano, mes] = data.slice(0, 7).split('-');

    return `${MES_ABREV[Number(mes) - 1]}/${ano}`;
}

export function diaPrevisto(renda: Renda): number {
    if (renda.tipo_recorrencia === 'unica') {
        return Number(renda.data_recebimento!.slice(8, 10));
    }

    return renda.dia_recebimento!;
}

export function estaAtrasada(
    renda: Renda,
    recebida: boolean,
    competencia: string,
): boolean {
    if (recebida) {
        return false;
    }

    const hoje = new Date();
    const [ano, mes] = competencia.split('-').map(Number);
    const indice = ano * 12 + mes;
    const indiceHoje = hoje.getFullYear() * 12 + (hoje.getMonth() + 1);

    if (indice < indiceHoje) {
        return true;
    }

    if (indice > indiceHoje) {
        return false;
    }

    return diaPrevisto(renda) < hoje.getDate();
}

function BotaoEditar({
    rotulo,
    aoClicar,
}: {
    rotulo: string;
    aoClicar: () => void;
}) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label={rotulo}
            title={rotulo}
            className="shrink-0 rounded-lg p-1.5 text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta focus:outline-none focus:ring-2 focus:ring-ouro"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
                className="h-4 w-4"
            >
                <path
                    d="M13.5 3.5a1.5 1.5 0 0 1 2.12 2.12L6.5 14.75l-3 .75.75-3 9.25-8.5Z"
                    stroke="currentColor"
                    strokeWidth="1.4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </button>
    );
}

function BotaoExcluir({
    rotulo,
    aoClicar,
}: {
    rotulo: string;
    aoClicar: () => void;
}) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label={rotulo}
            title={rotulo}
            className="shrink-0 rounded-lg p-1.5 text-vinho transition duration-150 ease-in-out hover:bg-vinho/5 focus:outline-none focus:ring-2 focus:ring-vinho"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="none"
                className="h-4 w-4"
            >
                <path
                    d="M4 6h12M8 6V4.5A1.5 1.5 0 0 1 9.5 3h1A1.5 1.5 0 0 1 12 4.5V6m2 0-.6 9a1.5 1.5 0 0 1-1.5 1.4H8.1A1.5 1.5 0 0 1 6.6 15L6 6"
                    stroke="currentColor"
                    strokeWidth="1.4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </button>
    );
}

export default function ItemOcorrenciaRenda({
    ocorrencia,
    aoEditar,
    aoExcluir,
    aoMarcarComoRecebida,
    aoDesfazerRecebimento,
}: {
    ocorrencia: OcorrenciaRenda;
    aoEditar: () => void;
    aoExcluir: () => void;
    aoMarcarComoRecebida: () => void;
    aoDesfazerRecebimento: () => void;
}) {
    const { renda, recebida, movimentacao, formas_pagamento_elegiveis } =
        ocorrencia;

    const atrasada = estaAtrasada(renda, recebida, ocorrencia.competencia);

    const tipoLabel = renda.tipo_recorrencia === 'unica' ? 'Única' : 'Mensal';

    let quando: string;
    if (recebida && movimentacao) {
        quando = `Recebida em ${formatarDataCurta(movimentacao.data)}`;
    } else if (renda.tipo_recorrencia === 'unica') {
        quando = `Recebimento ${formatarDataCurta(renda.data_recebimento!)}`;
    } else {
        const sufixo = renda.data_fim
            ? ` · até ${mesAno(renda.data_fim)}`
            : ` · desde ${mesAno(renda.data_inicio!)}`;
        quando = `Todo dia ${renda.dia_recebimento}${sufixo}`;
    }

    let statusLabel: string;
    let statusClasse: string;
    let statusPontoClasse: string;
    if (recebida) {
        statusLabel = 'Recebida';
        statusClasse = 'bg-verde/10 text-verde-escuro';
        statusPontoClasse = 'bg-verde';
    } else if (atrasada) {
        statusLabel = 'Atrasada';
        statusClasse = 'bg-vinho text-papel';
        statusPontoClasse = 'bg-papel';
    } else {
        statusLabel = 'A receber';
        statusClasse = 'bg-ouro/20 text-ouro';
        statusPontoClasse = 'bg-ouro';
    }

    const formaUsada = recebida ? movimentacao?.forma_pagamento : null;
    let formaRotulo: string;
    let formaClasse = 'text-tinta-claro';
    if (recebida) {
        formaRotulo = formaUsada?.nome ?? '—';
    } else if (formas_pagamento_elegiveis.length === 0) {
        formaRotulo = 'nenhuma forma recebe renda';
        formaClasse = 'text-vinho';
    } else if (formas_pagamento_elegiveis.length === 1) {
        formaRotulo = formas_pagamento_elegiveis[0].nome;
    } else {
        formaRotulo = `${formas_pagamento_elegiveis.length} formas elegíveis`;
    }

    const contaNome = recebida
        ? (movimentacao?.forma_pagamento?.conta?.nome ?? renda.conta.nome)
        : renda.conta.nome;

    let divergencia = '';
    if (recebida && movimentacao) {
        const diferenca = movimentacao.valor - renda.valor;
        divergencia =
            diferenca === 0
                ? 'igual ao programado'
                : `${formatadorDeMoeda.format(Math.abs(diferenca) / 100)} ${diferenca > 0 ? 'acima' : 'abaixo'} do programado ${formatadorDeMoeda.format(renda.valor / 100)}`;
    }

    let cardClasse =
        'flex flex-col self-start rounded-xl border border-tinta/10 bg-white shadow-sm shadow-tinta/5 transition duration-200 hover:border-tinta/20 hover:shadow-md';
    if (recebida) {
        cardClasse =
            'flex flex-col self-start rounded-xl border border-tinta/[0.08] bg-white/60 transition duration-200 hover:border-tinta/15';
    } else if (atrasada) {
        cardClasse =
            'flex flex-col self-start rounded-xl border border-tinta/10 border-l-[3px] border-l-vinho bg-vinho/5 shadow-sm shadow-tinta/5 transition duration-200 hover:border-tinta/20 hover:shadow-md';
    }

    return (
        <article className={cardClasse}>
            <div className={recebida ? 'p-4 pb-3' : 'p-5 pb-3.5'}>
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="flex items-center gap-1.5">
                            <span
                                aria-hidden="true"
                                className="h-[7px] w-[7px] shrink-0 rounded-full"
                                style={{
                                    backgroundColor: renda.categoria_renda.cor,
                                }}
                            />
                            <span
                                className={`truncate text-xs ${recebida ? 'text-tinta-claro/80' : 'text-tinta-claro'}`}
                            >
                                {renda.categoria_renda.nome}
                            </span>
                        </div>

                        <h3
                            className={`mt-1 truncate font-display font-semibold ${recebida ? 'text-[17px] text-tinta-claro' : 'text-lg text-tinta'}`}
                        >
                            {renda.descricao}
                        </h3>
                    </div>

                    <span
                        className={`inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11.5px] font-semibold ${statusClasse}`}
                    >
                        <span
                            aria-hidden="true"
                            className={`h-[5px] w-[5px] shrink-0 rounded-full ${statusPontoClasse}`}
                        />
                        {statusLabel}
                    </span>
                </div>

                <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-tinta-claro/70">
                        {tipoLabel}
                    </span>
                    <span
                        className={`text-xs ${recebida ? 'text-tinta-claro/80' : 'text-tinta-claro'}`}
                    >
                        {quando}
                    </span>
                </div>
            </div>

            <div
                className={`flex items-end justify-between gap-3.5 border-t border-tinta/[0.08] ${recebida ? 'px-4 py-3.5' : 'px-5 py-3.5'}`}
            >
                <div>
                    <p className="text-[10.5px] font-semibold uppercase tracking-wide text-tinta-claro">
                        {recebida ? 'Recebido' : 'Valor programado'}
                    </p>
                    <p
                        className={`mt-0.5 font-display font-bold leading-none tabular-nums ${recebida ? 'text-[22px] text-verde-escuro' : 'text-2xl text-tinta'}`}
                    >
                        {formatadorDeMoeda.format(
                            (recebida && movimentacao
                                ? movimentacao.valor
                                : renda.valor) / 100,
                        )}
                    </p>
                    {recebida && (
                        <p className="mt-1 text-[11.5px] leading-tight text-tinta-claro/70">
                            {divergencia}
                        </p>
                    )}
                </div>

                <div className="min-w-0 text-right">
                    <p className="text-[10.5px] font-semibold uppercase tracking-wide text-tinta-claro">
                        {recebida ? 'Caiu em' : 'Cai em'}
                    </p>
                    <p className="mt-0.5 truncate text-sm font-medium text-tinta">
                        {contaNome}
                    </p>
                    <p className={`mt-0.5 truncate text-xs ${formaClasse}`}>
                        {formaRotulo}
                    </p>
                </div>
            </div>

            <div className="flex items-center justify-between gap-2 border-t border-tinta/[0.08] px-4 py-2.5">
                {recebida ? (
                    <button
                        type="button"
                        onClick={aoDesfazerRecebimento}
                        className="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[12.5px] font-medium text-tinta-claro transition duration-150 ease-in-out hover:bg-papel hover:text-tinta"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 20 20"
                            fill="none"
                            className="h-[15px] w-[15px]"
                        >
                            <path
                                d="M4 5.5v4h4"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                            <path
                                d="M16.5 12a6.5 6.5 0 0 0-11-4.6L4 9"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        Desfazer recebimento
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={aoMarcarComoRecebida}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-verde/35 px-3.5 py-1.5 text-sm font-semibold text-verde-escuro transition duration-150 ease-in-out hover:border-verde hover:bg-verde/5"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 20 20"
                            fill="none"
                            className="h-[15px] w-[15px]"
                        >
                            <path
                                d="M10 4v9M6.5 9.5 10 13l3.5-3.5M4.5 16h11"
                                stroke="currentColor"
                                strokeWidth="1.6"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        Registrar recebimento
                    </button>
                )}

                <div className="flex shrink-0 items-center gap-0.5">
                    <BotaoEditar rotulo="Editar renda" aoClicar={aoEditar} />
                    <BotaoExcluir
                        rotulo="Excluir renda"
                        aoClicar={aoExcluir}
                    />
                </div>
            </div>
        </article>
    );
}
