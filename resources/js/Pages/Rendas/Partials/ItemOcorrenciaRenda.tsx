import type { OcorrenciaRenda, TipoRecorrencia } from '@/types';

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const rotuloTipoRecorrencia: Record<TipoRecorrencia, string> = {
    unica: 'Única',
    mensal: 'Mensal',
};

const corTipoRecorrencia: Record<TipoRecorrencia, string> = {
    unica: 'bg-tinta/10 text-tinta',
    mensal: 'bg-ouro/20 text-ouro',
};

function formatarData(data: string | null): string | null {
    if (!data) {
        return null;
    }

    const [ano, mes, dia] = data.slice(0, 10).split('-');

    return `${dia}/${mes}/${ano}`;
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
    const { renda, recebida, movimentacao } = ocorrencia;

    return (
        <article className="flex flex-col gap-3 rounded-xl border border-tinta/10 bg-white p-5 shadow-sm shadow-tinta/5">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h2 className="truncate font-display text-lg font-semibold text-tinta">
                        {renda.descricao}
                    </h2>

                    <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span
                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${corTipoRecorrencia[renda.tipo_recorrencia]}`}
                        >
                            {rotuloTipoRecorrencia[renda.tipo_recorrencia]}
                        </span>

                        {renda.categoria_renda && (
                            <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                                <span
                                    aria-hidden="true"
                                    className="h-2 w-2 rounded-full"
                                    style={{
                                        backgroundColor:
                                            renda.categoria_renda.cor,
                                    }}
                                />
                                {renda.categoria_renda.nome}
                            </span>
                        )}

                        <span
                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                recebida
                                    ? 'bg-verde/10 text-verde'
                                    : 'bg-tinta/10 text-tinta-claro'
                            }`}
                        >
                            {recebida ? 'Recebida' : 'Pendente'}
                        </span>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <BotaoEditar rotulo="Editar renda" aoClicar={aoEditar} />
                    <BotaoExcluir rotulo="Excluir renda" aoClicar={aoExcluir} />
                </div>
            </div>

            <dl className="grid grid-cols-2 gap-3 border-t border-tinta/10 pt-3">
                <div>
                    <dt className="text-xs text-tinta-claro">Valor</dt>
                    <dd className="text-sm font-medium tabular-nums text-tinta">
                        {formatadorDeMoeda.format(
                            (recebida && movimentacao
                                ? movimentacao.valor
                                : renda.valor) / 100,
                        )}
                    </dd>
                </div>

                {renda.tipo_recorrencia === 'unica' && (
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Recebimento previsto
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            {formatarData(renda.data_recebimento) ?? '—'}
                        </dd>
                    </div>
                )}

                {renda.tipo_recorrencia === 'mensal' && (
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Recebimento previsto
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            todo dia {renda.dia_recebimento}
                        </dd>
                    </div>
                )}

                {recebida && movimentacao && (
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Recebimento
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            {formatarData(movimentacao.data) ?? '—'}
                        </dd>
                    </div>
                )}

                {recebida && movimentacao?.forma_pagamento && (
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Forma de pagamento
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            {movimentacao.forma_pagamento.nome}
                        </dd>
                    </div>
                )}

                {recebida && movimentacao?.forma_pagamento?.conta?.usuario && (
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Recebido por
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            {movimentacao.forma_pagamento.conta.usuario.nome}
                        </dd>
                    </div>
                )}
            </dl>

            {recebida ? (
                <button
                    type="button"
                    onClick={aoDesfazerRecebimento}
                    className="rounded-lg border border-tinta/15 px-3 py-1.5 text-sm font-medium text-tinta-claro transition-colors hover:bg-papel hover:text-tinta"
                >
                    Desfazer recebimento
                </button>
            ) : (
                <button
                    type="button"
                    onClick={aoMarcarComoRecebida}
                    className="rounded-lg bg-verde/10 px-3 py-1.5 text-sm font-medium text-verde-escuro transition-colors hover:bg-verde/20"
                >
                    Marcar como recebida
                </button>
            )}
        </article>
    );
}
