import { formatarMoeda, formatarPercentual } from '@/lib/money';
import type { ContribuicaoPessoaItem, ContribuicaoPorPessoa } from '@/types';

function Barra({ itens }: { itens: ContribuicaoPessoaItem[] }) {
    const total = itens.reduce((soma, item) => soma + item.valor, 0);

    return (
        <div className="flex h-2 overflow-hidden rounded-full bg-tinta/[0.07]">
            {itens.map((item) => (
                <span
                    key={item.usuarioId}
                    className="block h-2"
                    style={{
                        backgroundColor: item.cor,
                        width: total > 0 ? `${(item.valor / total) * 100}%` : '0%',
                    }}
                />
            ))}
        </div>
    );
}

function Linha({ item, total }: { item: ContribuicaoPessoaItem; total: number }) {
    return (
        <div className="mt-2.5 flex items-center gap-2">
            <span
                aria-hidden="true"
                className="h-2 w-2 rounded-full"
                style={{ backgroundColor: item.cor }}
            />
            <span className="text-sm text-tinta-claro">{item.nome}</span>
            <span className="text-sm font-semibold tabular-nums text-tinta">
                {formatarMoeda(item.valor)}
            </span>
            <span className="text-xs text-tinta-claro">
                {total > 0 ? formatarPercentual((item.valor / total) * 100) : '—'}
            </span>
        </div>
    );
}

export default function ContribuicaoPessoa({
    contribuicao,
}: {
    contribuicao: ContribuicaoPorPessoa;
}) {
    const totalReceita = contribuicao.receita.reduce((s, i) => s + i.valor, 0);
    const totalDespesa = contribuicao.despesa.reduce((s, i) => s + i.valor, 0);

    return (
        <div className="rounded-xl border border-tinta/10 bg-white">
            <div className="border-b border-tinta/10 px-6 py-4">
                <h2 className="font-display text-[17px] font-semibold text-tinta">
                    Contribuição por pessoa
                </h2>
                <p className="mt-1 text-xs text-tinta-claro">Só aparece no modo Casal</p>
            </div>

            <div className="px-6 py-5">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                    Receita aportada
                </p>
                <Barra itens={contribuicao.receita} />
                {contribuicao.receita.map((item) => (
                    <Linha key={item.usuarioId} item={item} total={totalReceita} />
                ))}

                <div className="my-5 h-px bg-tinta/10" />

                <p className="text-[11px] font-semibold uppercase tracking-wider text-tinta-claro">
                    Despesa conjunta paga
                </p>
                <Barra itens={contribuicao.despesa} />
                {contribuicao.despesa.map((item) => (
                    <Linha key={item.usuarioId} item={item} total={totalDespesa} />
                ))}
            </div>
        </div>
    );
}
