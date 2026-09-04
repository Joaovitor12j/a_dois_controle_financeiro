import InputLabel from '@/Components/InputLabel';
import SelectInput from '@/Components/SelectInput';
import type {
    CategoriaDespesa,
    FiltrosDespesaValores,
    FormaPagamentoResumo,
    StatusPagamentoFiltro,
    TipoLancamentoDespesa,
} from '@/types';
import { SlidersHorizontal, X } from 'lucide-react';

const rotuloTipoLancamento: Record<TipoLancamentoDespesa, string> = {
    unica: 'Única',
    mensal: 'Mensal',
    parcelada: 'Parcelada',
};

const rotuloStatus: Record<StatusPagamentoFiltro, string> = {
    paga: 'Paga',
    pendente: 'Pendente',
};

export default function FiltrosDespesa({
    filtros,
    categoriasDespesa,
    formasPagamento,
    aoMudar,
    aoLimpar,
}: {
    filtros: FiltrosDespesaValores;
    categoriasDespesa: CategoriaDespesa[];
    formasPagamento: FormaPagamentoResumo[];
    aoMudar: (parcial: FiltrosDespesaValores) => void;
    aoLimpar: () => void;
}) {
    const temFiltroAtivo = Object.values(filtros).some((valor) => !!valor);

    return (
        <div className="mb-5 flex flex-wrap items-end gap-3.5 rounded-xl border border-tinta/10 bg-white px-4 py-3.5">
            <div className="flex items-center gap-1.5 pb-2.5 text-tinta-claro">
                <SlidersHorizontal className="h-4 w-4" />
                <span className="text-[11px] font-semibold uppercase tracking-wider">
                    Filtros
                </span>
            </div>

            <div className="min-w-[160px] flex-1">
                <InputLabel
                    htmlFor="filtro-categoria"
                    value="Categoria"
                    className="text-xs"
                />
                <SelectInput
                    id="filtro-categoria"
                    className="mt-1 block w-full"
                    value={filtros.categoria_despesa_id ?? ''}
                    onChange={(evento) =>
                        aoMudar({
                            categoria_despesa_id:
                                evento.target.value || undefined,
                        })
                    }
                >
                    <option value="">Todas</option>
                    {categoriasDespesa.map((categoria) => (
                        <option key={categoria.id} value={categoria.id}>
                            {categoria.nome}
                        </option>
                    ))}
                </SelectInput>
            </div>

            <div className="min-w-[150px] flex-1">
                <InputLabel
                    htmlFor="filtro-tipo"
                    value="Tipo de lançamento"
                    className="text-xs"
                />
                <SelectInput
                    id="filtro-tipo"
                    className="mt-1 block w-full"
                    value={filtros.tipo ?? ''}
                    onChange={(evento) =>
                        aoMudar({
                            tipo:
                                (evento.target.value as TipoLancamentoDespesa) ||
                                undefined,
                        })
                    }
                >
                    <option value="">Todos</option>
                    {Object.entries(rotuloTipoLancamento).map(
                        ([valor, rotulo]) => (
                            <option key={valor} value={valor}>
                                {rotulo}
                            </option>
                        ),
                    )}
                </SelectInput>
            </div>

            <div className="min-w-[170px] flex-1">
                <InputLabel
                    htmlFor="filtro-forma-pagamento"
                    value="Forma de pagamento"
                    className="text-xs"
                />
                <SelectInput
                    id="filtro-forma-pagamento"
                    className="mt-1 block w-full"
                    value={filtros.forma_pagamento_id ?? ''}
                    onChange={(evento) =>
                        aoMudar({
                            forma_pagamento_id:
                                evento.target.value || undefined,
                        })
                    }
                >
                    <option value="">Todas</option>
                    {formasPagamento.map((forma) => (
                        <option key={forma.id} value={forma.id}>
                            {forma.nome}
                        </option>
                    ))}
                </SelectInput>
            </div>

            <div className="min-w-[130px] flex-1">
                <InputLabel
                    htmlFor="filtro-status"
                    value="Status"
                    className="text-xs"
                />
                <SelectInput
                    id="filtro-status"
                    className="mt-1 block w-full"
                    value={filtros.status ?? ''}
                    onChange={(evento) =>
                        aoMudar({
                            status:
                                (evento.target.value as StatusPagamentoFiltro) ||
                                undefined,
                        })
                    }
                >
                    <option value="">Todas</option>
                    {Object.entries(rotuloStatus).map(([valor, rotulo]) => (
                        <option key={valor} value={valor}>
                            {rotulo}
                        </option>
                    ))}
                </SelectInput>
            </div>

            {temFiltroAtivo && (
                <button
                    type="button"
                    onClick={aoLimpar}
                    className="mb-[3px] flex h-11 items-center gap-1.5 rounded-lg px-2 text-[13px] font-medium text-tinta-claro transition-colors hover:text-tinta"
                >
                    <X className="h-3.5 w-3.5" />
                    Limpar filtros
                </button>
            )}
        </div>
    );
}
