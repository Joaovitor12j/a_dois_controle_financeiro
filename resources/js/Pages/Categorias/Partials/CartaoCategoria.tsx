import { iconeCategoriaComponente } from '@/lib/icones-categoria';
import type { CategoriaDespesa, CategoriaRenda } from '@/types';

export default function CartaoCategoria({
    categoria,
    aoEditar,
    aoExcluir,
}: {
    categoria: CategoriaRenda | CategoriaDespesa;
    aoEditar: () => void;
    aoExcluir: () => void;
}) {
    const Icone = iconeCategoriaComponente(categoria.icone);

    return (
        <div className="flex items-center gap-3 rounded-xl border border-tinta/10 bg-white/60 p-4">
            <span
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                style={{ backgroundColor: `${categoria.cor}26` }}
            >
                <Icone
                    className="h-5 w-5"
                    strokeWidth={1.75}
                    style={{ color: categoria.cor }}
                />
            </span>

            <p className="flex-1 truncate font-medium text-tinta">
                {categoria.nome}
            </p>

            <div className="flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    onClick={aoEditar}
                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-tinta-claro transition hover:bg-tinta/5 hover:text-tinta"
                >
                    Editar
                </button>

                <button
                    type="button"
                    onClick={aoExcluir}
                    className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-vinho transition hover:bg-vinho/5"
                >
                    Excluir
                </button>
            </div>
        </div>
    );
}
