import {
    ICONES_CATEGORIA,
    IconeCategoria,
    LISTA_ICONES_CATEGORIA,
} from '@/lib/icones-categoria';

export default function SeletorIcone({
    valor,
    aoSelecionar,
}: {
    valor: string;
    aoSelecionar: (icone: IconeCategoria) => void;
}) {
    return (
        <div className="mt-1.5 grid grid-cols-6 gap-2 sm:grid-cols-8">
            {LISTA_ICONES_CATEGORIA.map((icone) => {
                const Icone = ICONES_CATEGORIA[icone];
                const selecionado = icone === valor;

                return (
                    <button
                        key={icone}
                        type="button"
                        onClick={() => aoSelecionar(icone)}
                        aria-pressed={selecionado}
                        aria-label={icone}
                        className={`flex h-10 w-10 items-center justify-center rounded-lg border transition ${
                            selecionado
                                ? 'border-ouro bg-ouro/15 text-tinta'
                                : 'border-tinta/15 bg-white text-tinta-claro hover:border-tinta/30 hover:text-tinta'
                        }`}
                    >
                        <Icone className="h-5 w-5" strokeWidth={1.75} />
                    </button>
                );
            })}
        </div>
    );
}
