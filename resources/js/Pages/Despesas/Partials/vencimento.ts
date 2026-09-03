import type { Despesa, OcorrenciaDespesa } from '@/types';

function pad(numero: number): string {
    return String(numero).padStart(2, '0');
}

export function formatarDiaMes(data: Date): string {
    return `${pad(data.getDate())}/${pad(data.getMonth() + 1)}`;
}

export function dataVencimento(
    despesa: Despesa,
    ocorrencia: OcorrenciaDespesa,
): Date | null {
    if (despesa.tipo_lancamento === 'unica' && despesa.data_vencimento) {
        const [ano, mes, dia] = despesa.data_vencimento
            .slice(0, 10)
            .split('-')
            .map(Number);

        return new Date(ano, mes - 1, dia);
    }

    if (despesa.tipo_lancamento === 'mensal' && despesa.dia_vencimento) {
        const [ano, mes] = ocorrencia.competencia.split('-').map(Number);
        const diasNoMes = new Date(ano, mes, 0).getDate();

        return new Date(ano, mes - 1, Math.min(despesa.dia_vencimento, diasNoMes));
    }

    return null;
}
