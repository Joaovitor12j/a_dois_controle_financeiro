import FormErrorSummary from '@/Components/FormErrorSummary';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type OpcaoCor = {
    id: string;
    nome: string;
    hex: string;
    compativeis: string[];
};

export default function AtualizarCorForm({
    corAtual,
    corParceiro,
    paleta,
    corDoCasal,
    className = '',
}: {
    corAtual: string;
    corParceiro: string | null;
    paleta: OpcaoCor[];
    corDoCasal: string | null;
    className?: string;
}) {
    const { data, setData, patch, errors, processing } = useForm({
        cor: corAtual,
    });

    const opcaoIndisponivel = (opcao: OpcaoCor): string | null => {
        if (corParceiro === null) {
            return null;
        }

        if (opcao.hex.toLowerCase() === corParceiro.toLowerCase()) {
            return 'Esta cor já é usada pelo seu parceiro.';
        }

        const corParceiroReconhecida = paleta.some(
            (candidata) =>
                candidata.hex.toLowerCase() === corParceiro.toLowerCase(),
        );

        if (
            corParceiroReconhecida &&
            !opcao.compativeis.includes(corParceiro)
        ) {
            return 'Esta cor não combina com a cor do seu parceiro.';
        }

        return null;
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="font-display text-lg font-semibold text-tinta">
                    Sua cor
                </h2>

                <p className="mt-1 text-sm text-tinta-claro">
                    Identifica você no cabeçalho e nos gráficos do painel. A
                    cor precisa combinar com a do seu parceiro.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div className="flex flex-wrap gap-3">
                    {paleta.map((opcao) => {
                        const motivoIndisponivel = opcaoIndisponivel(opcao);
                        const selecionada =
                            data.cor.toLowerCase() ===
                            opcao.hex.toLowerCase();

                        return (
                            <button
                                key={opcao.id}
                                type="button"
                                aria-label={opcao.nome}
                                aria-pressed={selecionada}
                                aria-disabled={
                                    motivoIndisponivel !== null
                                }
                                title={motivoIndisponivel ?? opcao.nome}
                                disabled={motivoIndisponivel !== null}
                                onClick={() => setData('cor', opcao.hex)}
                                className={
                                    'relative flex h-10 w-10 items-center justify-center rounded-full border-2 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-ouro motion-reduce:transition-none ' +
                                    (selecionada
                                        ? 'border-ouro ring-2 ring-ouro ring-offset-2 ring-offset-papel'
                                        : 'border-transparent') +
                                    (motivoIndisponivel
                                        ? ' cursor-not-allowed opacity-40'
                                        : ' hover:border-tinta-claro/40')
                                }
                                style={{ backgroundColor: opcao.hex }}
                            >
                                {selecionada && (
                                    <svg
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        className="h-5 w-5 text-papel"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M4 10.5l3.5 3.5L16 5.5"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                    </svg>
                                )}

                                {!selecionada && motivoIndisponivel && (
                                    <svg
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        className="h-4 w-4 text-papel"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="4.5"
                                            y="9"
                                            width="11"
                                            height="8"
                                            rx="1.5"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                        />
                                        <path
                                            d="M7 9V6.5a3 3 0 016 0V9"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                            strokeLinecap="round"
                                        />
                                    </svg>
                                )}
                            </button>
                        );
                    })}
                </div>

                <div className="flex flex-wrap gap-6 rounded-lg border border-tinta/10 bg-papel p-4">
                    <div className="flex flex-col items-center gap-1.5">
                        <span
                            className="h-8 w-8 rounded-full border border-tinta/10"
                            style={{ backgroundColor: data.cor }}
                            aria-hidden="true"
                        />
                        <span className="text-xs font-medium text-tinta-claro">
                            Você
                        </span>
                    </div>

                    <div className="flex flex-col items-center gap-1.5">
                        <span
                            className="h-8 w-8 rounded-full border border-tinta/10"
                            style={{
                                backgroundColor: corParceiro ?? 'transparent',
                            }}
                            aria-hidden="true"
                        />
                        <span className="text-xs font-medium text-tinta-claro">
                            Parceiro
                        </span>
                    </div>

                    <div className="flex flex-col items-center gap-1.5">
                        <span
                            className="h-8 w-8 rounded-full border border-tinta/10"
                            style={{
                                backgroundColor: corDoCasal ?? 'transparent',
                            }}
                            aria-hidden="true"
                        />
                        <span className="text-xs font-medium text-tinta-claro">
                            Nosso
                        </span>
                    </div>
                </div>

                <InputError message={errors.cor} />

                <FormErrorSummary errors={errors} />

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        Salvar alterações
                    </PrimaryButton>
                </div>
            </form>
        </section>
    );
}
