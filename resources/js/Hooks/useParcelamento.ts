import { useMemo } from 'react';

export type ModoEntradaParcelamento = 'total' | 'parcela';

interface UseParcelamentoResultado {
    valorParcela: number;
    valorTotalExibido: number;
    diferencaArredondamento: number;
}

export function useParcelamento(
    valorDigitado: number,
    numeroParcelas: number,
    modo: ModoEntradaParcelamento,
): UseParcelamentoResultado {
    return useMemo(() => {
        if (!numeroParcelas || numeroParcelas < 1) {
            return { valorParcela: 0, valorTotalExibido: 0, diferencaArredondamento: 0 };
        }

        if (modo === 'parcela') {
            return {
                valorParcela: valorDigitado,
                valorTotalExibido: valorDigitado * numeroParcelas,
                diferencaArredondamento: 0,
            };
        }

        const valorParcela = Math.round(valorDigitado / numeroParcelas);
        const valorTotalExibido = valorParcela * numeroParcelas;

        return {
            valorParcela,
            valorTotalExibido,
            diferencaArredondamento: valorTotalExibido - valorDigitado,
        };
    }, [valorDigitado, numeroParcelas, modo]);
}
