import type { ContextoDespesa, Despesa, TipoLancamentoDespesa } from '@/types';

const formatadorDeMoeda = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const rotuloContexto: Record<ContextoDespesa, string> = {
    individual: 'Individual',
    conjunta: 'Conjunta',
};

const corContexto: Record<ContextoDespesa, string> = {
    individual: 'bg-tinta/10 text-tinta',
    conjunta: 'bg-verde/10 text-verde-escuro',
};

const rotuloTipoLancamento: Record<TipoLancamentoDespesa, string> = {
    unica: 'Única',
    mensal: 'Mensal',
    parcelada: 'Parcelada',
};

const corTipoLancamento: Record<TipoLancamentoDespesa, string> = {
    unica: 'bg-tinta/10 text-tinta',
    mensal: 'bg-ouro/20 text-ouro',
    parcelada: 'bg-vinho/10 text-vinho-escuro',
};

function formatarData(data: string | null): string | null {
    if (!data) {
        return null;
    }

    const [ano, mes, dia] = data.slice(0, 10).split('-');

    return `${dia}/${mes}/${ano}`;
}

const nomesMeses = [
    'janeiro',
    'fevereiro',
    'março',
    'abril',
    'maio',
    'junho',
    'julho',
    'agosto',
    'setembro',
    'outubro',
    'novembro',
    'dezembro',
];

function formatarMesAno(data: string | null): string | null {
    if (!data) {
        return null;
    }

    const [ano, mes] = data.slice(0, 7).split('-');

    return `${nomesMeses[Number(mes) - 1]}/${ano}`;
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

export default function ItemDespesa({
    despesa,
    aoEditar,
    aoExcluir,
}: {
    despesa: Despesa;
    aoEditar: () => void;
    aoExcluir: () => void;
}) {
    return (
        <article className="flex flex-col gap-3 rounded-xl border border-tinta/10 bg-white p-5 shadow-sm shadow-tinta/5">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <h2 className="truncate font-display text-lg font-semibold text-tinta">
                        {despesa.descricao}
                    </h2>

                    <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span
                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${corContexto[despesa.contexto]}`}
                        >
                            {rotuloContexto[despesa.contexto]}
                        </span>

                        <span
                            className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${corTipoLancamento[despesa.tipo_lancamento]}`}
                        >
                            {rotuloTipoLancamento[despesa.tipo_lancamento]}
                        </span>

                        {despesa.categoria_despesa && (
                            <span className="inline-flex items-center gap-1.5 text-xs text-tinta-claro">
                                <span
                                    aria-hidden="true"
                                    className="h-2 w-2 rounded-full"
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

                <div className="flex shrink-0 items-center gap-1">
                    <BotaoEditar rotulo="Editar despesa" aoClicar={aoEditar} />
                    <BotaoExcluir
                        rotulo="Excluir despesa"
                        aoClicar={aoExcluir}
                    />
                </div>
            </div>

            {despesa.tipo_lancamento === 'unica' && (
                <dl className="grid grid-cols-3 gap-3 border-t border-tinta/10 pt-3">
                    <div>
                        <dt className="text-xs text-tinta-claro">Valor</dt>
                        <dd className="text-sm font-medium tabular-nums text-tinta">
                            {formatadorDeMoeda.format(despesa.valor / 100)}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-xs text-tinta-claro">
                            {despesa.paga ? 'Pagamento' : 'Vencimento'}
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            {formatarData(
                                despesa.paga
                                    ? despesa.data_pagamento
                                    : despesa.data_vencimento,
                            ) ?? '—'}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-xs text-tinta-claro">Status</dt>
                        <dd>
                            <span
                                className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                    despesa.paga
                                        ? 'bg-verde/10 text-verde'
                                        : 'bg-tinta/10 text-tinta-claro'
                                }`}
                            >
                                {despesa.paga ? 'Paga' : 'Pendente'}
                            </span>
                        </dd>
                    </div>
                </dl>
            )}

            {despesa.tipo_lancamento === 'mensal' && (
                <dl className="grid grid-cols-3 gap-3 border-t border-tinta/10 pt-3">
                    <div>
                        <dt className="text-xs text-tinta-claro">
                            Vencimento
                        </dt>
                        <dd className="text-sm font-medium text-tinta">
                            todo dia {despesa.dia_vencimento}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-xs text-tinta-claro">Início</dt>
                        <dd className="text-sm font-medium capitalize text-tinta">
                            {formatarMesAno(despesa.data_inicio) ?? '—'}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-xs text-tinta-claro">Fim</dt>
                        <dd className="text-sm font-medium text-tinta">
                            {formatarData(despesa.data_fim) ?? 'sem data de fim'}
                        </dd>
                    </div>
                </dl>
            )}

            {despesa.tipo_lancamento === 'parcelada' &&
                despesa.numero_parcelas != null && (
                    <dl className="grid grid-cols-2 gap-3 border-t border-tinta/10 pt-3">
                        <div className="col-span-2">
                            <dt className="text-xs text-tinta-claro">
                                Parcelas
                            </dt>
                            <dd className="text-sm font-medium tabular-nums text-tinta">
                                {formatadorDeMoeda.format(despesa.valor / 100)}{' '}
                                × {despesa.numero_parcelas} ={' '}
                                {formatadorDeMoeda.format(
                                    (despesa.valor * despesa.numero_parcelas) /
                                        100,
                                )}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-xs text-tinta-claro">
                                1ª parcela
                            </dt>
                            <dd className="text-sm font-medium text-tinta">
                                {formatarData(despesa.data_primeira_parcela) ??
                                    '—'}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-xs text-tinta-claro">
                                Cartão
                            </dt>
                            <dd className="text-sm font-medium text-tinta">
                                {despesa.forma_pagamento?.nome ?? '—'}
                            </dd>
                        </div>
                    </dl>
                )}
        </article>
    );
}
