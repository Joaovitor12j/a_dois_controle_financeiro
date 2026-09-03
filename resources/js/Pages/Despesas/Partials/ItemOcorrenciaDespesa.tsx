import type { OcorrenciaDespesa, StatusDespesa } from '@/types';
import { dataVencimento, formatarDiaMes } from './vencimento';

export const COLUNAS_GRADE =
    'grid-cols-[minmax(0,1fr)_118px_168px_112px_108px_100px]';

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const rotuloStatus: Record<StatusDespesa, string> = {
    vencida: 'Vencida',
    pendente: 'Pendente',
    paga: 'Paga',
};

const corStatus: Record<StatusDespesa, string> = {
    vencida: 'bg-vinho text-papel',
    pendente: 'bg-ouro/[0.16] text-[#8A6A2F]',
    paga: 'bg-verde/10 text-verde-escuro',
};

const corPontoStatus: Record<StatusDespesa, string> = {
    vencida: 'bg-papel',
    pendente: 'bg-ouro',
    paga: 'bg-verde',
};

function pad(numero: number): string {
    return String(numero).padStart(2, '0');
}

function formatarDiaMesIso(data: string): string {
    const [ano, mes, dia] = data.slice(0, 10).split('-').map(Number);

    return `${pad(dia)}/${pad(mes)}`;
}

function BotaoAlternarPagamento({
    paga,
    aoClicar,
}: {
    paga: boolean;
    aoClicar: () => void;
}) {
    const rotulo = paga ? 'Desfazer pagamento' : 'Marcar como paga';

    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label={rotulo}
            title={rotulo}
            className={`flex h-[30px] w-7 shrink-0 items-center justify-center rounded-lg transition-colors ${
                paga
                    ? 'text-tinta-claro hover:bg-papel hover:text-tinta'
                    : 'text-verde hover:bg-verde/10'
            }`}
        >
            {paga ? (
                <svg
                    aria-hidden="true"
                    viewBox="0 0 20 20"
                    fill="none"
                    className="h-4 w-4"
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
            ) : (
                <svg
                    aria-hidden="true"
                    viewBox="0 0 20 20"
                    fill="none"
                    className="h-[17px] w-[17px]"
                >
                    <path
                        d="M4.5 10.5 8 14l7.5-8"
                        stroke="currentColor"
                        strokeWidth="1.6"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            )}
        </button>
    );
}

function BotaoEditar({ aoClicar }: { aoClicar: () => void }) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label="Editar despesa"
            title="Editar despesa"
            className="flex h-[30px] w-7 shrink-0 items-center justify-center rounded-lg text-tinta-claro transition-colors hover:bg-papel hover:text-tinta"
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

function BotaoExcluir({ aoClicar }: { aoClicar: () => void }) {
    return (
        <button
            type="button"
            onClick={aoClicar}
            aria-label="Excluir despesa"
            title="Excluir despesa"
            className="flex h-[30px] w-7 shrink-0 items-center justify-center rounded-lg text-vinho transition-colors hover:bg-vinho/5"
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

function descreverQuando(ocorrencia: OcorrenciaDespesa): {
    quando: string;
    nota: string;
} {
    const { despesa, paga, numero_parcela, movimentacao } = ocorrencia;
    const parcelada = despesa.tipo_lancamento === 'parcelada';

    if (paga && movimentacao) {
        const quando = `Pago ${formatarDiaMesIso(movimentacao.data)}`;

        if (parcelada) {
            return {
                quando,
                nota: `parcela ${numero_parcela}/${despesa.numero_parcelas}`,
            };
        }

        const vencimento = dataVencimento(despesa, ocorrencia);

        return {
            quando,
            nota: vencimento ? `vencia ${formatarDiaMes(vencimento)}` : '',
        };
    }

    if (parcelada) {
        return {
            quando: `Parcela ${numero_parcela}/${despesa.numero_parcelas}`,
            nota: '',
        };
    }

    const vencimento = dataVencimento(despesa, ocorrencia);

    if (!vencimento) {
        return { quando: '—', nota: '' };
    }

    return {
        quando: `Vence ${formatarDiaMes(vencimento)}`,
        nota:
            despesa.tipo_lancamento === 'mensal'
                ? `todo dia ${despesa.dia_vencimento}`
                : '',
    };
}

export default function ItemOcorrenciaDespesa({
    ocorrencia,
    variante,
    aoEditar,
    aoExcluir,
    aoMarcarComoPaga,
    aoDesfazerPagamento,
}: {
    ocorrencia: OcorrenciaDespesa;
    variante: 'aPagar' | 'paga';
    aoEditar: () => void;
    aoExcluir: () => void;
    aoMarcarComoPaga: () => void;
    aoDesfazerPagamento: () => void;
}) {
    const { despesa, paga, status, movimentacao } = ocorrencia;
    const { quando, nota } = descreverQuando(ocorrencia);
    const parcelada = despesa.tipo_lancamento === 'parcelada';

    const forma = paga
        ? movimentacao?.forma_pagamento
        : parcelada
          ? despesa.forma_pagamento
          : undefined;

    const pagoPor =
        paga && movimentacao?.forma_pagamento?.conta?.usuario
            ? `pago por ${movimentacao.forma_pagamento.conta.usuario.nome}`
            : '';

    const muted = variante === 'paga';

    return (
        <div
            className={`grid ${COLUNAS_GRADE} items-center gap-3.5 border-t border-tinta/10 px-5 py-3.5 transition-colors hover:bg-papel/60 ${
                status === 'vencida' ? 'bg-vinho/5 shadow-[inset_3px_0_0_#7B3F55]' : ''
            }`}
        >
            <div className="min-w-0">
                <div
                    className={`truncate font-display text-[15px] font-semibold leading-tight ${
                        muted ? 'text-tinta-claro' : 'text-tinta'
                    }`}
                >
                    {despesa.descricao}
                </div>

                <div className="mt-1.5 flex flex-wrap items-center gap-2">
                    <span
                        className={`shrink-0 text-[10px] font-semibold uppercase tracking-wider ${
                            muted ? 'text-tinta-claro/70' : 'text-tinta-claro'
                        }`}
                    >
                        {despesa.tipo_lancamento === 'unica'
                            ? 'Única'
                            : despesa.tipo_lancamento === 'mensal'
                              ? 'Mensal'
                              : 'Parcelada'}
                    </span>

                    <span className="inline-flex shrink-0 items-center rounded-full border border-tinta/15 px-2 py-px text-[11px] font-medium text-tinta-claro">
                        {despesa.contexto === 'conjunta'
                            ? 'Conjunta'
                            : 'Individual'}
                    </span>

                    {despesa.categoria_despesa && (
                        <span className="inline-flex min-w-0 items-center gap-1.5 text-xs text-tinta-claro">
                            <span
                                aria-hidden="true"
                                className="h-[7px] w-[7px] shrink-0 rounded-full"
                                style={{
                                    backgroundColor:
                                        despesa.categoria_despesa.cor,
                                }}
                            />
                            {despesa.categoria_despesa.nome}
                        </span>
                    )}
                </div>
            </div>

            <div className="text-[13px] leading-snug">
                <div
                    className={`font-medium ${muted ? 'text-tinta-claro' : 'text-tinta'}`}
                >
                    {quando}
                </div>
                {nota && (
                    <div className="mt-px text-[11.5px] text-tinta-claro/80">
                        {nota}
                    </div>
                )}
            </div>

            <div className="min-w-0 text-[13px] leading-snug">
                <div
                    className={
                        forma
                            ? `truncate ${muted ? 'text-tinta-claro' : 'text-tinta'}`
                            : 'text-tinta-claro/60'
                    }
                >
                    {forma?.nome ?? '—'}
                </div>
                {pagoPor && (
                    <div className="mt-px text-[11.5px] text-tinta-claro/80">
                        {pagoPor}
                    </div>
                )}
            </div>

            <div
                className={`text-right font-display text-base font-bold tabular-nums ${
                    muted ? 'text-tinta-claro' : 'text-tinta'
                }`}
            >
                {formatadorDeMoeda.format(despesa.valor / 100)}
            </div>

            <div>
                <span
                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11.5px] font-semibold ${corStatus[status]}`}
                >
                    <span
                        aria-hidden="true"
                        className={`h-[5px] w-[5px] shrink-0 rounded-full ${corPontoStatus[status]}`}
                    />
                    {rotuloStatus[status]}
                </span>
            </div>

            <div className="flex items-center justify-end gap-0.5">
                <BotaoAlternarPagamento
                    paga={paga}
                    aoClicar={paga ? aoDesfazerPagamento : aoMarcarComoPaga}
                />
                <BotaoEditar aoClicar={aoEditar} />
                <BotaoExcluir aoClicar={aoExcluir} />
            </div>
        </div>
    );
}
